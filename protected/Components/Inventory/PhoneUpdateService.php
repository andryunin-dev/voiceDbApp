<?php
namespace App\Components\Inventory;

use App\Components\IpTools;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Cluster;
use App\Models\DataPort;
use App\Models\Office;
use App\Models\PhoneInfo;
use App\Models\Vrf;
use T4\Core\MultiException;
use T4\Orm\Exception;

class PhoneUpdateService
{
    /**
     * @var $phoneInfo PhoneInfo
     */
    private $phoneInfo;
    /**
     * @var $appliance Appliance
     */
    private $appliance;

    /**
     * @param PhoneInfo $phoneInfo
     * @param array $data
     * @throws \Throwable
     */
    public function update(PhoneInfo $phoneInfo, array $data): void
    {
        $this->phoneInfo = $phoneInfo;
        $this->appliance = $phoneInfo->phone;
        if ($phoneInfo->isNew()) {
            $this->createPhone($data);
        } else {
            $this->updatePhone($data);
        }
    }

    /**
     * @param array $data
     * @throws \Throwable
     */
    private function createPhone(array $data)
    {
        try {
            PhoneInfo::getDbConnection()->beginTransaction();
            $this->updatePhone($data);
            PhoneInfo::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            PhoneInfo::getDbConnection()->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws Exception
     * @throws MultiException
     */
    private function updatePhone(array $data)
    {
        $this->updateAppliance($data);
        $this->updatePhoneInfo($data);
    }

    /**
     * @param array $data
     * @throws MultiException|Exception
     */
    private function updateAppliance(array $data): void
    {
        switch ($this->type($data['name'])) {
            case 'phone':
                $this->updatePhoneAppliance( $data);
                break;
            case 'vgcPhone':
                $this->updateVGCPhoneAppliance($data);
                break;
            default:
        }
    }

    /**
     * Update Phone's Appliance data
     *
     * @param array $data
     * @throws MultiException|Exception
     */
    private function updatePhoneAppliance(array $data): void
    {
        $model = $this->model($data);
        $applianceData = [
            'dataSetType' => 'appliance',
            'applianceType' => ApplianceType::PHONE,
            'platformVendor' => $this->vendor(),
            'platformTitle' => $model,
            'chassis' => $model,
            'platformSerial' => $this->platformSerial($data),
            'applianceSoft' => $this->phoneSoft(),
            'softwareVersion' => $this->phoneSoftwareVersion($data),
            'LotusId' => $this->phoneLocation($model)->lotusId,
            'hostname' => $data['name'],
            'ip' => $this->ip($data),
            'vrf_name' => 'global',
            'applianceModules' => [],
        ];
        $this->updateApplianceData($applianceData);
    }

    /**
     * Update VGC Phone's Appliance data
     *
     * @param array $data
     * @throws Exception
     * @throws MultiException
     */
    private function updateVGCPhoneAppliance(array $data): void
    {
        $model = $this->model($data);
        $ip = (new IpTools($data['ipAddress']))->address;
        $applianceData = [
            'dataSetType' => 'appliance',
            'applianceType' => ApplianceType::PHONE,
            'platformVendor' => $this->vendor(),
            'platformTitle' => $model,
            'chassis' => $model,
            'platformSerial' => $data['name'],
            'applianceSoft' => $this->vgcPhoneSoft(),
            'softwareVersion' => $this->vgcPhoneSoftwareVersion(),
            'LotusId' => $this->vgLocation($ip)->lotusId,
            'hostname' => $data['name'],
            'ip' => null,
            'vrf_name' => null,
            'applianceModules' => [],
        ];
        $this->appliance->fill([
            'cluster' => $this->cluster($ip)
        ]);
        $this->updateApplianceData($applianceData);
    }

    /**
     * @param array $data
     * @throws MultiException|Exception
     */
    private function updateApplianceData(array $data): void
    {
        if (!(new DatasetValidator())->validate($data)) {
            throw new Exception("Dataset is not valid [dataset]=" . json_encode($data));
        }
        (new ApplianceUpdateService())->update($this->appliance, $data);
    }

    /**
     * Update PhoneInfo data
     *
     * @param array $data
     * @throws MultiException
     */
    private function updatePhoneInfo(array $data): void
    {
        $this->phoneInfo
            ->fill([
                'name' => $data['name'],
                'model' => $this->model($data),
                'prefix' => preg_replace('~\..+~','',$data['prefix']),
                'phoneDN' => $data['phonedn'],
                'e164mask' => $data['e164mask'],
                'status' => $data['status'],
                'description' => $data['description'],
                'css' => $data['css'],
                'devicePool' => $data['devicepool'],
                'alertingName' => $data['alertingname'],
                'partition' => $data['partition'],
                'timezone' => $data['timezone'],
                'dhcpEnabled' => $this->dhcpEnabled($data['dhcpEnabled']),
                'dhcpServer' => $this->sanitizeIp($data['dhcpServer']),
                'domainName' => $this->domainName($data['domainName']),
                'tftpServer1' => $this->sanitizeIp($data['tftpServer1']),
                'tftpServer2' => $this->sanitizeIp($data['tftpServer2']),
                'defaultRouter' => $this->sanitizeIp($data['defaultRouter']),
                'dnsServer1' => $this->sanitizeIp($data['dnsServer1']),
                'dnsServer2' => $this->sanitizeIp($data['dnsServer2']),
                'callManager1' => $this->callManager($data['callManager1']),
                'callManager2' => $this->callManager($data['callManager2']),
                'callManager3' => $this->callManager($data['callManager3']),
                'callManager4' => $this->callManager($data['callManager4']),
                'vlanId' => (int)$data['vlanId'],
                'userLocale' => $data['userLocale'],
                'publisherIp' => $data['publisherIp'],
                'unknownLocation' => false,
            ]);
        $this->phoneInfo->save();
    }

    /**
     * @return string
     */
    private function vendor(): string
    {
        return 'CISCO';
    }

    /**
     * @return string
     */
    private function phoneSoft(): string
    {
        return 'Phone Soft';
    }

    /**
     * @return string
     */
    private function vgcPhoneSoft(): string
    {
        return '';
    }

    /**
     * @param array $data
     * @return string
     */
    private function phoneSoftwareVersion(array $data): string
    {
        return mb_ereg_match('.*6921', $data['model'])
            ? $data['appLoadID']
            : $data['versionID'];
    }

    /**
     * @return string
     */
    private function vgcPhoneSoftwareVersion(): string
    {
        return '';
    }

    /**
     * @param array $data
     * @return string
     */
    private function model(array $data): string
    {
        $models = [
            '6921' => 'CP-6921',
            '7905' => 'CP-7905G',
            '7911' => 'CP-7911G',
            '7912' => 'CP-7912G',
            '7936' => 'CP-7936',
            '7937' => 'CP-7937',
            '7940' => 'CP-7940G',
            '7942' => 'CP-7942G',
            '7945' => 'CP-7945G',
            '7960' => 'CP-7960G',
            '7965' => 'CP-7965G',
            '7975' => 'CP-7975G',
            '8831' => 'CP-8831',
            '8865' => 'CP-8865',
            '8945' => 'CP-8945',
            '30' => '30 VIP'
        ];
        $model = empty($data['modelNumber']) ? $data['model'] : $data['modelNumber'];
        if (false !== mb_ereg("\d+", $model, $numericModelCode)
            && array_key_exists($numericModelCode[0], $models)
        ) {
            return $models[$numericModelCode[0]];
        }
        if (false !== mb_eregi('communicator', $model)) {
            return 'Communicator';
        }
        $model = trim(mb_eregi_replace('cisco', '', $model));
        $model = mb_eregi_replace(' +', ' ', $model);
        return $model;
    }

    /**
     * @param string $model
     * @return Office
     * @throws MultiException
     */
    private function phoneLocation(string $model): Office
    {
        $virtualAppliances = [
            '30 VIP',
            'Communicator',
            'Unified Client Services Framework'
        ];
        if (in_array($model, $virtualAppliances)) {
            return Office::virtualAppliancesInstance();
        }
        return $this->phoneInfo->isNew()
            ? Office::unknownLocationInstance()
            : $this->appliance->location;
    }

    /**
     * Location of the VGC Phone is determined by the device VG
     *
     * @param string $ip
     * @return Office
     * @throws Exception
     */
    private function vgLocation(string $ip): Office
    {
        $vg = $this->vg($ip);
        if (false === $vg) {
            throw new Exception('Office VGG Phone is not determine');
        }
        return $vg->location;
    }

    /**
     * @param string $ip
     * @return Appliance|false
     */
    private function vg(string $ip)
    {
        $dataPort = DataPort::findByIpVrf($ip, Vrf::instanceGlobalVrf());
        return false === $dataPort ? false : $dataPort->appliance;
    }

    /**
     * @param string $domainName
     * @return string|null
     */
    private function domainName(string $domainName)
    {
        return 'Нет' == $domainName ? '' : $domainName;
    }

    /**
     * @param $dhcpEnabled
     * @return bool
     */
    private function dhcpEnabled($dhcpEnabled): bool
    {
        $dhcpEnabled = mb_strtolower($dhcpEnabled);
        return 'yes' == $dhcpEnabled || 1 == $dhcpEnabled || 'да' == $dhcpEnabled;
    }

    /**
     * @param string $callManager
     * @return string
     */
    private function callManager(string $callManager): string
    {
        return mbereg_replace(' +', ' ', $callManager);
    }

    /**
     * @param $name
     * @return string
     */
    private function type($name): string
    {
        return mb_ereg_match('^vgc|an', mb_strtolower($name))
            ? 'vgcPhone'
            : 'phone';
    }

    /**
     * @param string $ip
     * @return Cluster
     * @throws Exception
     * @throws MultiException
     */
    private function cluster(string $ip): Cluster
    {
        $vg = $this->vg($ip);
        if (false === $vg || !isset($vg->details->hostname)) {
            throw new Exception('Cluster VGG Phone is not determine');
        }
        $vg->fill([
            'cluster' => Cluster::instanceWithTitle($vg->details->hostname)
        ])->save();
        return $vg->cluster;
    }

    /**
     * @param string $ip
     * @return string|null
     */
    private function sanitizeIp(string $ip)
    {
        return empty($ip) || false === (new IpTools($ip))->address
            ? null
            : $ip;
    }

    /**
     * @param array $data
     * @return string
     */
    private function platformSerial(array $data): string
    {
        return empty($data['serialNumber']) ? $data['name'] : $data['serialNumber'];
    }

    /**
     * @param array $data
     * @return string|null
     */
    private function ip(array $data)
    {
        $virtualAppliances = [
            '30 VIP',
            'Communicator',
            'Unified Client Services Framework'
        ];
        return  in_array($this->model($data), $virtualAppliances)
            ? null
            : (new IpTools($data['ipAddress'], $data['subNetMask']))->cidrAddress;
    }
}
