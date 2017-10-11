<?php
namespace App\Components;

use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Vrf;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class DSPprefixes extends Std
{
    protected $dataSet;


    /**
     * DSPprefixes constructor.
     * @param Std|null $dataSet
     */
    public function __construct(Std $dataSet = null)
    {
        $this->dataSet = $dataSet;
        parent::__construct($dataSet);
    }


    /**
     * @return bool
     * @throws MultiException
     */
    public function run()
    {
        $this->verifyDataSet();

        $errors = new MultiException();
        try {

            //// GOAL 1 - FIND APPLIANCE (искать по management Ip)
            // Проверяем на валидность managementIp
            $managementIp = (new IpTools($this->dataSet->ip))->address;
            if (false === $managementIp) {
                throw new Exception('[dataSetType]=prefixes; [ip]=' . $this->dataSet->ip . '; [error-message]=No valid managementIp ' . $this->dataSet->ip);
            }

            // Ищем managementIp's VRF
            if (!empty($this->dataSet->vrf_rd)) {
                $managementIpVrf = Vrf::findByColumn('rd', $this->dataSet->vrf_rd);
            } else {
                $managementIpVrf = Vrf::findByColumn('name', $this->dataSet->vrf_name);
            }
            if (false === $managementIpVrf) {
                throw new Exception('[dataSetType]=prefixes; [ip]=' . $this->dataSet->ip . '; [error-message]=Unknown VRF - ' . $this->dataSet->vrf_name);
            }

            // Ищем managementIp's dataport
            $managementIpDataPort = DataPort::findByIpVrf($managementIp, $managementIpVrf);
            if (false === $managementIpDataPort) {
                throw new Exception('[dataSetType]=prefixes; [ip]=' . $this->dataSet->ip . '; [error-message]=Data port ' . $this->dataSet->ip . ' does not found');
            }

            $appliance = $managementIpDataPort->appliance;
            //// GOAL 1 IS ACHIEVED - APPLIANCE FOUND


            //// GOAL 2 - FOR EACH OF RESPONDING DATA PORT DO ...
            foreach ($this->dataSet->networks as $dataSetNetwork) {

                try {

                    //// GOAL 3 - FIND RESPONDING DATA PORT
                    $dataSetPortName = (empty($dataSetNetwork->interface)) ? DataPort::DEFAULT_PORTNAME : $dataSetNetwork->interface;
                    $dataSetMacAddress = (empty($dataSetNetwork->mac)) ? DataPort::DEFAULT_MACADDRESS : $dataSetNetwork->mac;

                    $result = DataPort::findByAppliancePortnameMacaddress($appliance,$dataSetPortName,$dataSetMacAddress);
                    $respondingDataPort = (false === $result) ? new DataPort() : $result;
                    //// GOAL 3 IS ACHIEVED - RESPONDING DATA PORT FOUND



                    //// GOAL 4 - CREATE OR UPDATE RESPONDING DATA PORT
                    // Проверяем на валидность dataSetNetworkIp
                    $ipTools = new IpTools($dataSetNetwork->ip_address);
                    if (false === $ipTools->is_valid) {
                        throw new Exception('[dataSetType]=prefixes; [ip]=' . $this->dataSet->ip . '; [error-message]=No valid dataSetNetworkIp ' . $dataSetNetwork->ip_address);
                    }
                    $dataSetNetworkIp = $ipTools->address;
                    $dataSetNetworkMasklen = $ipTools->masklen;

                    // Определяем dataSetNetworkVrf
                    if (!empty($dataSetNetwork->vrf_rd)) {
                        $dataSetNetworkVrf = Vrf::findByColumn('rd', $dataSetNetwork->vrf_rd);
                    } else {
                        $dataSetNetworkVrf = Vrf::findByColumn('name', $dataSetNetwork->vrf_name);
                    }
                    if (false === $dataSetNetworkVrf) {
                        throw new Exception('[dataSetType]=prefixes; [ip]=' . $this->dataSet->ip . '; [error-message]=Unknown VRF - ' . $dataSetNetwork->vrf_name);
                    }

                    if ($respondingDataPort->isNew()) {

                        /// CREATE RESPONDING DATA PORT

                        // Определяем dataSetNetworkType
                        $dataSetNetworkType = DPortType::findByColumn('type', DPortType::TYPE_ETHERNET);
                        if (false === $dataSetNetworkType) {
                            $dataSetNetworkType = (new DPortType())->fill([
                                'type' => DPortType::TYPE_ETHERNET,
                            ]);
                            $dataSetNetworkType->save();
                        }

                        // Если существует dataport с dataSetNetworkIp, то удаляем его
                        $existDataPort = DataPort::findByIpVrf($dataSetNetworkIp, $dataSetNetworkVrf);
                        if (false !== $existDataPort) {
                            $existDataPort->delete();
                        }

                        $respondingDataPort->fill([
                            'appliance' => $appliance,
                            'portType' => $dataSetNetworkType,
                            'macAddress' => $dataSetNetwork->mac,
                            'ipAddress' => $dataSetNetworkIp,
                            'vrf' => $dataSetNetworkVrf,
                            'masklen' => $dataSetNetworkMasklen,
                            'details' => [
                                'portName' => trim($dataSetNetwork->interface),
                                'description' => $dataSetNetwork->description,
                            ],
                            'isManagement' => ($dataSetNetworkIp == $managementIp) ? true : false,
                            'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                        ]);

                    } else {

                        /// UPDATE RESPONDING DATA PORT

                        // Если у respondingDataPort изменился ipAddress, то проверить - существует ли dataport с dataSetNetworkIp. Если ДА - то удаляем его.
                        // Update ipAddress
                        if ($dataSetNetworkIp != $respondingDataPort->ipAddress) {
                            $existDataPort = DataPort::findByIpVrf($dataSetNetworkIp, $dataSetNetworkVrf);
                            if (false !== $existDataPort) {
                                $existDataPort->delete();
                            }

                            $respondingDataPort->fill([
                                'ipAddress' => $dataSetNetworkIp,
                            ]);
                        }

                        // Update data of details
                        $respondingDataPortDetails = $respondingDataPort->details;
                        if (!is_null($respondingDataPortDetails) && (is_array($respondingDataPortDetails = $respondingDataPortDetails->getData()))) {

                            $detailsNew = [
                                'description' => $dataSetNetwork->description,
                            ];

                            $respondingDataPort->fill([
                                'details' => array_merge($respondingDataPortDetails, $detailsNew),
                            ]);

                        } else {
                            $respondingDataPort->fill([
                                'details' => [
                                    'description' => $dataSetNetwork->description,
                                ],
                            ]);
                        }


                        $respondingDataPort->fill([
                            'vrf' => $dataSetNetworkVrf,
                            'masklen' => $dataSetNetworkMasklen,
                            'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                        ]);
                    }

                    $respondingDataPort->save();
                    //// GOAL 4 IS ACHIEVED - CREATE OR UPDATE RESPONDING DATA PORT

                } catch (\Throwable $e) {
                    $errors->addException($e->getMessage());
                }
            }

            if (!$errors->isEmpty()) {
                throw $errors;
            }
            //// GOAL 2 IS ACHIEVED

        } catch (MultiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $errors->addException($e->getMessage());
        }

        if (!$errors->isEmpty()) {
            throw $errors;
        }

        return true;
    }


    /**
     * @throws Exception
     * @throws MultiException
     */
    protected function verifyDataSet()
    {
        $errors = new MultiException();
        try {

            if (is_null($this->dataSet) || empty($this->dataSet->getData())) {
                throw new Exception('[dataSetType]=prefixes; [error-message]=Empty an input DATASET');
            }

            if (!isset($this->dataSet->ip)) {
                throw new Exception('[dataSetType]=prefixes; [error-message]=DATASET: Missing field ip');
            }
            if (!isset($this->dataSet->vrf_name)) {
                $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: Missing field vrf_name');
            }
            if (!isset($this->dataSet->lotus_id)) {
                $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: Missing field lotus_id');
            }
            if (empty($this->dataSet->networks)) {
                $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: Missing field networks');
            } else {
                foreach ($this->dataSet->networks as $dataSetNetwork) {
                    if (!isset($dataSetNetwork->ip_address)) {
                        $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: dataPort - Missing field ip_address');
                    }
                    if (!isset($dataSetNetwork->interface)) {
                        $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: dataPort(' . $dataSetNetwork->ip_address . ') - Missing field interface');
                    }
                    if (!isset($dataSetNetwork->mac)) {
                        $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: dataPort(' . $dataSetNetwork->ip_address . ') - Missing field mac');
                    }
                    if (!isset($dataSetNetwork->vrf_name)) {
                        $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: dataPort(' . $dataSetNetwork->ip_address . ') - Missing field vrf_name');
                    }
                    if (!isset($dataSetNetwork->vrf_rd)) {
                        $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: dataPort(' . $dataSetNetwork->ip_address . ') - Missing field vrf_rd');
                    }
                    if (!isset($dataSetNetwork->description)) {
                        $errors->addException('[dataSetType]=prefixes; [managmentIp]=' . $this->dataSet->ip . '; [error-message]=DATASET: dataPort(' . $dataSetNetwork->ip_address . ') - Missing field description');
                    }
                }
            }

            if (!$errors->isEmpty()) {
                throw $errors;
            }

        } catch (Exception $e) {
            $errors->add($e);
        } catch (MultiException $e) {
            throw $e;
        }

        // Если DataSet не валидный, то заканчиваем работу
        if (!$errors->isEmpty()) {
            throw $errors;
        }
    }
}
