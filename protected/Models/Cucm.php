<?php
namespace App\Models;

use App\Components\AxlClient;
use App\Components\RisPortClient;
use T4\Core\Collection;
use T4\Core\Std;

class Cucm extends Std
{
    const PUBLISHER = 'cmp';
    const SUBSCRIBER = 'cms';
    const MAXRETURNEDDEVICES = 1000; // max 1000
    const ALLMODELS = 255; // All phone's models
    const PHONESTATUS = 'Registered';
    const PHONE = 'Phone';

    protected $appliance;
    protected $managementIp;


    /**
     * @return Collection Phone
     */
    public function getRegisteredPhones()
    {
        // Get list of all subscribers and publisher in the cluster
        $listAllProcessNodes = $this->listAllProcessNodes();

        // Получить данные по телефонам из cucm используя AXL
        $axlPhones = $this->getPhonesFromAxl();

        // Получить данные по зарегистрированным телефонам из cucm используя RisPort
        $registeredPhones = $this->getPhonesFromRis($axlPhones, $listAllProcessNodes);

        // Добавить недостающие поля из AxlPhones в зарегистрированные телефоны из RisPhones
        foreach ($registeredPhones as $phone) {
            $axlPhone = $axlPhones->findByAttributes(['device' => $phone->name]);
            $phone->fill([
                'css' => $axlPhone->css,
                'devicePool' => $axlPhone->dpool,
                'prefix' => $axlPhone->prefix,
                'phoneDN' => $axlPhone->dnorpattern,
                'alertingName' => $axlPhone->fio,
                'partition' => $axlPhone->pt,
                'type' => $axlPhone->type,
            ]);
        }

        // Опросить кажждый телефон по его IP через WEB Interface - DeviceInformationX
        foreach ($registeredPhones as $phone) {
            $phoneData = simplexml_load_file('http://' . $phone->ipAddress . '/DeviceInformationX');
            if (false !== $phoneData) {
                $phone->fill([
                    'macAddress' => $phoneData->MACAddress->__toString(),
                    'serialNumber' => $phoneData->serialNumber->__toString(),
                    'modelNumber' => $phoneData->modelNumber->__toString(),
                    'versionID' => $phoneData->versionID->__toString(),
                    'appLoadID' => $phoneData->appLoadID->__toString(),
                ]);
            } else {
                // Todo записать в лог о том, что у телефона нет WEB доступа
            }
        }

        return $registeredPhones;
    }


    /**
     * Получить данные по телефонам из cucm используя AXL
     *
     * @return Collection
     */
    protected function getPhonesFromAxl()
    {
        $axl = (new AxlClient($this->managementIp))->connection;

        // Получить данные по телефонам из cucm
        $phones = $axl->ExecuteSQLQuery(['sql' => '
                    SELECT d."name" AS Device,
                          d."description",
                          css."name" AS css,
                          css2."name" AS name_off_clause,
                          dp."name" AS dPool,
                          n2."dnorpattern" AS prefix,
                          n."dnorpattern",
                          n."alertingname" AS FIO,
                          partition."name" AS pt,
                          tm."name" AS type
                    FROM device AS d
                    INNER JOIN callingsearchspace AS css ON css."pkid" = d."fkcallingsearchspace"
                    INNER JOIN devicenumplanmap AS dmap ON dmap."fkdevice" = d."pkid" AND d."tkclass" = 1
                    INNER JOIN typemodel AS tm ON d."tkmodel" = tm."enum"
                    INNER JOIN numplan AS n ON dmap."fknumplan" = n."pkid"
                    INNER JOIN routepartition AS partition ON partition."pkid" = n."fkroutepartition"
                    INNER JOIN DevicePool AS dp ON dp."pkid" = d.fkDevicePool
                    INNER JOIN callingsearchspace AS css2 ON css2."clause" LIKE "%" || partition."name" || "%"
                    INNER JOIN numplan AS n2 ON n2."fkcallingsearchspace_translation" = css2."pkid"
                          WHERE n2."tkpatternusage" = 3 AND
                                n2."dnorpattern" LIKE "5%"
                '])->return->row;

        $axlPhones = new Collection();
        foreach ($phones as $phone) {
            $axlPhones->add($phone);
        }

        return $axlPhones;
    }


    /**
     * Получить данные по зарегистрированным телефонам из cucm используя RisPort
     *
     * @return Collection
     */
    protected function getPhonesFromRis(Collection $phones, Collection $nodes)
    {
        $ris = (new RisPortClient($this->managementIp))->connection;

        // ЕСЛИ кол-во опрашиваемых телефонов БОЛЬШЕ, чем кол-во (MAXRETURNEDDEVICES) отдаваемых callmanager за один запрос,
        // ТО опрашивать callmanager будем по телефонам из коллекции $phones в несколько запросов
        // 1 запрос - кол-во телефонов = MAXRETURNEDDEVICES
        // TODO - доделать по ограничению кол-ва запросов в минуту (не более 15 запросов в минуту)
        $registeredPhones = new Collection();
        if (self::MAXRETURNEDDEVICES < $phones->count()) {
            $n = 0;
            foreach ($phones as $phone) {
                $items[] = ['Item' => $phone->device];
                $n++;

                if (self::MAXRETURNEDDEVICES == $n) {
                    $risPhones = $ris->SelectCmDevice('',[
                        'MaxReturnedDevices' => self::MAXRETURNEDDEVICES,
                        'Class' => self::PHONE,
                        'Model' => self::ALLMODELS,
                        'Status' => self::PHONESTATUS,
                        'SelectBy' => 'Name',
                        'SelectItems' => $items,
                    ]);
                    foreach (($risPhones['SelectCmDeviceResult'])->CmNodes as $cmNode) {
                        if ('ok' == strtolower($cmNode->ReturnCode)) {
                            $node = $nodes->findByAttributes(['cmNodeIpAddress' => $cmNode->Name]);

                            foreach ($cmNode->CmDevices as $cmDevice) {
                                $registeredPhones->add(
                                    (new Phone())->fill([
                                        'cmName' => $node->cmNodeName,
                                        'cmIpAddress' => $node->cmNodeIpAddress,
                                        'name' => $cmDevice->Name,
                                        'ipAddress' => $cmDevice->IpAddress,
                                        'description' => $cmDevice->Description,
                                        'status' => $cmDevice->Status,
                                    ])
                                );
                            }
                        }
                    }
                    $n = 0;
                    $items = [];
                }
            }
        } else {
            // ЕСЛИ кол-во опрашиваемых телефонов МЕНЬШЕ, чем кол-во (MAXRETURNEDDEVICES) отдаваемых callmanager за один запрос,
            // ТО делаем выборку всех зарегистрированных на callmanager телефонов одним запросом
            $risPhones = $ris->SelectCmDevice('',[
                'Class' => self::PHONE,
                'Model' => self::ALLMODELS,
                'Status' => self::PHONESTATUS,
                'SelectBy' => 'Name',
                'SelectItems' => [['Item' => '*']],
            ]);
            foreach (($risPhones['SelectCmDeviceResult'])->CmNodes as $cmNode) {
                if ('ok' == strtolower($cmNode->ReturnCode)) {
                    $node = $nodes->findByAttributes(['cmNodeIpAddress' => $cmNode->Name]);

                    foreach ($cmNode->CmDevices as $cmDevice) {
                        $registeredPhones->add(
                            (new Phone())->fill([
                                'cmName' => $node->cmNodeName,
                                'cmIpAddress' => $node->cmNodeIpAddress,
                                'name' => $cmDevice->Name,
                                'ipAddress' => $cmDevice->IpAddress,
                                'description' => $cmDevice->Description,
                                'status' => $cmDevice->Status,
                            ])
                        );
                    }
                }
            }
        }

        return $registeredPhones;
    }


    /**
     * @return Collection
     */
    protected function listAllProcessNodes()
    {
        $axl = (new AxlClient($this->managementIp))->connection;

        // Get all CmNodes from the publisher
        $listCmNodes = new Collection();
        $cmServers = ($axl->ListAllProcessNodes())->return->processNode;
        foreach ($cmServers as $server) {
            $listCmNodes->add((new Std())
                ->fill([
                    'cmNodeIpAddress' => $server->name,
                    'cmNodeName' => $server->description,
                ])
            );
        }

        return $listCmNodes;
    }


    protected function getAppliance()
    {
        return $this->appliance;
    }

    protected function getManagementIp()
    {
        return $this->managementIp;
    }


    /**
     * @return Collection
     */
    public static function findAllPublishers()
    {
        $publishers = new Collection();
        $appliances = Appliance::findAllByType(self::PUBLISHER);
        foreach ($appliances as $appliance) {
            $publisher = (new Cucm())->fill([
                'appliance' => $appliance,
                'managementIp' => $appliance->getManagementIp(),
            ]);
            $publishers->add($publisher);
        }
        return $publishers;
    }


    /**
     * @return Collection
     */
    public static function findAllSubscribers()
    {
        $subscribers = new Collection();
        $appliances = Appliance::findAllByType(self::SUBSCRIBER);
        foreach ($appliances as $appliance) {
            $subscriber = (new Cucm())->fill([
                'appliance' => $appliance,
                'managementIp' => $appliance->getManagementIp(),
            ]);
            $subscribers->add($subscriber);
        }
        return $subscribers;
    }
}
