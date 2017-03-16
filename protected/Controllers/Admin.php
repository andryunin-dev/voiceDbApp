<?php

namespace App\Controllers;

use App\Components\Parser;
use App\Components\Publisher;
use App\Models\Address;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\City;
use App\Models\Module;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Region;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Mvc\Controller;

class Admin extends Controller
{
    public function actionDefault()
    {

    }
    public function actionRegions()
    {
        $this->data->regions = Region::findAll(['order' => 'title']);
    }
    public function actionAddRegion($region = null)
    {
        try {
            Region::getDbConnection()->beginTransaction();
            if (!empty($region)) {
                if (!empty(trim($region['many']))) {
                    $pattern = '~[\n\r]~';
                    $regsInString = preg_replace($pattern, ',', trim($region['many']));
                    $regInArray = explode(',', $regsInString);

                    try {
                        foreach ($regInArray as $region) {
                            (new Region())
                                ->fill(['title' => trim($region)])
                                ->save();
                        }
                        $this->data->result = 'Регионы добавлены';
                    } catch (MultiException $e) {
                        $e->prepend(new Exception('Ошибка пакетного ввода'));
                        throw $e;
                    }
                } elseif (!empty($region['one'])) {
                    $res = (new Region())
                        ->fill(['title' => $region['one']])
                        ->save();
                    $this->data->result = 'Регион добавлен';
                } else {
                    $this->data->result = 'Нет данных';
                }
            }
            Region::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    public function actionEditRegion($region)
    {
        try {
            Region::getDbConnection()->beginTransaction();

            if (false === $item = Region::findByPK($region['id'])) {
                throw new Exception('Неверные данные');
            }
            $item->fill([
                'title' => $region['title']
            ]);
            $item->save();
            $this->data->result = 'Регион изменен';

            Region::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    public function actionDelRegion($id)
    {
        try {
            Region::getDbConnection()->beginTransaction();

            //проверка правильности id
            if (false === $item = Region::findByPK($id)) {
                throw new Exception('Неверные данные');
            }
            //Проверка наличия городов в этом регионе
            if ($item->cities->count() > 0) {
                throw new Exception('Сначала удалите все города из этого региона');
            }
            $item->delete();
            $this->data->result = 'Регион "' . $item->title .  '" удален';

            Region::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    public function actionCities()
    {
        $asc = function (City $city_1, City $city_2) {
            return strnatcmp($city_1->region->title, $city_2->region->title);
        };

        $this->data->cities = City::findAll()->uasort($asc);
    }

    public function actionAddCity($city)
    {
        try {
            City::getDbConnection()->beginTransaction();
            if (!is_numeric($city['regId'])) {
                throw new Exception('Регион не выбран');
            }
            if (false === $region = Region::findByPK($city['regId'])) {
                throw new Exception('Регион не найден');
            }
            (new City())
                ->fill([
                'title' => $city['title'],
                'region' => $region
                ])
                ->save();

            City::getDbConnection()->commitTransaction();

        } catch (MultiException $e) {
            City::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            City::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    public function actionEditCity($city)
    {
        try {
            City::getDbConnection()->beginTransaction();

            if (false === $editedCity = City::findByPK($city['id'])) {
                throw new Exception('Неверные данные');
            }
            $editedCity->title = $city['title'];
            $editedCity->region = Region::findByPK($city['regId']);
            $editedCity->save();

            City::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            City::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            City::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    public function actionDelCity($id = null)
    {
        try {
            City::getDbConnection()->beginTransaction();

            //проверка правильности id
            if (false === $item = City::findByPK($id)) {
                throw new Exception('Неверные данные');
            }
            //Проверка наличия адресов в этом регионе
            if ($item->addresses->count() > 0) {
                throw new Exception('Сначала удалите все адреса и офисы из этого города');
            }
            $item->delete();
            $this->data->result = 'Город  "' . $item->title .  '" удален';

            City::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            City::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            City::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    /**
     * action вывода всех имеющихся статусов
     */
    public function actionOfficeStatuses()
    {
        $this->data->statuses = OfficeStatus::findAll(['order' => 'title']);
    }
    /**
     * action добавления нового статуса.
     *
     * @param array $status новые статусы ['many'] с ',' в качестве разделителя
     * или один новый статус ['one']
     */
    public function actionAddStatus($status = null)
    {
        try {
            OfficeStatus::getDbConnection()->beginTransaction();

            (new OfficeStatus())
                ->fill($status)
                ->save();

            OfficeStatus::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            OfficeStatus::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            OfficeStatus::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    public function actionEditStatus($status)
    {
        try {
            OfficeStatus::getDbConnection()->beginTransaction();
            if (false === $editedStatus = OfficeStatus::findByPK($status['id'])) {
                throw new Exception('Неверные данные');
            }
            $editedStatus
                ->fill([
                    'title' => $status['title']
                ])
                ->save();

            OfficeStatus::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            OfficeStatus::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            OfficeStatus::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }

    public function actionDelStatus($id = null)
    {
        try {
            OfficeStatus::getDbConnection()->beginTransaction();
            if (false === $status = OfficeStatus::findByPK($id)) {
                throw new Exception('Неверные данные');
            }
            if ($status->offices->count() > 0 ) {
                throw new Exception('Удаление невозможно. Данный статус используется');
            }
            $status->delete();
            $this->data->result = 'Статус "' . $status->title . '" удален.';

            OfficeStatus::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            OfficeStatus::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            OfficeStatus::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }

    }

    public function actionOffices()
    {
        $asc = function (Office $office_1, Office $office_2) {
            return strnatcmp($office_1->address->city->region->title, $office_2->address->city->region->title);
        };

        $this->data->offices = Office::findAll()->uasort($asc);
    }

    /**
     * @param Std $data
     * В случае $data->many формат записи для офиса : регион; город; адрес; офис; статус
     * @throws Exception
     */
    public function actionAddOffice($data)
    {
        //если поле пакетного ввода не пустое, то берем данные оттуда
        if (!empty(trim($data->many))) {
            try {
                Office::getDbConnection()->beginTransaction();

                $officeCollection = Parser::lotusTerritory(trim($data->many));
                if (false === $officeCollection) {
                    throw new Exception('Ошибка данных пакетного ввода');
                }

                foreach ($officeCollection as $item) {
                        //Region
                        $region = Region::findByTitle($item->region);
                        if (false === $region && isset($data->addNewRegion)) {
                            $region = (new Region())
                                ->fill([
                                    'title' => $item->region
                                ])
                                ->save();
                        }

                        //City
                        $city = City::findByTitle($item->city);
                        if (false === $city && isset($data->addNewCity)) {
                            $city = (new City())
                                ->fill([
                                    'title' => $item->city,
                                    'region' => $region
                                ])
                                ->save();
                        }

                        //Address
                        $address = (new Address())
                            ->fill([
                                'address' => $item->address,
                                'city' => $city
                            ])
                            ->save();

                        //статус (берем из формы)
                        $status = OfficeStatus::findByPK($data->statusId);


                        //собираем офис
                        $res = (new Office())
                            ->fill([
                                'title' => $item->office,
                                'lotusId' => $item->lotusId,
                                'address' => $address,
                                'status' => $status
                            ])
                            ->save();
                     Office::getDbConnection()->commitTransaction();
                }
            } catch (MultiException $e) {
                Office::getDbConnection()->rollbackTransaction();
                $e->prepend(new Exception('Ошибка пакетного ввода'));
                $this->data->errors = $e;
            } catch (Exception $e) {
                Office::getDbConnection()->rollbackTransaction();
                $errors = (new MultiException())
                    ->add(
                        new Exception('Ошибка пакетного ввода')
                    );
                $this->data->errors = $errors->add($e);
            }
            return;
        }

        //если поле пакетного ввода пустое, то берем поля формы
        try {
            Office::getDbConnection()->beginTransaction();
            if (!is_numeric($data->regionId)) {
                throw new Exception('Регион не выбран');
            }
            if (!is_numeric($data->cityId)) {
                throw new Exception('Город не выбран');
            }
            if (!is_numeric($data->statusId)) {
                throw new Exception('Статус не выбран');
            }

            //создаем объект адреса
            $address = (new Address())
                ->fill([
                    'address' => $data->address,
                    'city' => City::findByPK($data->cityId)
                ])
                ->save();

            (new Office())
                ->fill([
                    'title' => $data->title,
                    'lotusId' => $data->lotusId,
                    'address' => $address,
                    'status' => OfficeStatus::findByPK($data->statusId)
                ])
                ->save();

            //Office::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Office::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            Office::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }





//        if (!empty(trim($data->many))) {
//            $officeCollection = Parser::lotusTerritory(trim($data->many));
//            if (false !== $officeCollection) {
//                foreach ($officeCollection as $item) {
//                    Office::getDbConnection()->beginTransaction();
//                    //Region
//                    $region = Region::findByTitle($item->region);
//                    if (false === $region) {
//                        if (isset($data->addNewRegion)) {
//                            $region = (new Region())
//                                ->fill(['title' => $item->region])
//                                ->save();
//                        } else {
//                            $region = false;
//                        }
//                    }
//
//                    //City
//                    $city = City::findByTitle($item->city);
//                    //если город не найден и разрешено создание нового региона
//                    if (false === $city) {
//                        if (isset($data->addNewCity)) {
//                            $city = (new City())
//                                ->fill([
//                                    'title' => $item->city,
//                                    'region' => $region
//                                ])
//                                ->save();
//                        } else {
//                            $city = false;
//                        }
//                    }
//
//                    //Address
//                    $address = (new Address())
//                        ->fill([
//                            'address' => $item->address,
//                            'city' => $city
//                        ])
//                        ->save();
//
//                    //Office status
//                    //если парсинг откинул поле статуса или оно пустое - берем из формы
//                    if (empty($item->status)) {
//                        $status = OfficeStatus::findByPK($data->statusId);
//                        if (false ===$status) {
//                            continue; //Ошибка. невозможно установить статус, переход к след записи
//                        }
//                    } else {
//                        $status = OfficeStatus::findByTitle($item->status);
//                        if (false === $status) {
//                            if (isset($data->addNewStatus)) {
//                                $status = (new OfficeStatus())
//                                    ->fill(['title' => $item->status])
//                                    ->save();
//                            } else {
//                                continue; //Ошибка. невозможно установить статус, переход к след записи
//                            }
//                        }
//                    }
//
//                    //Office
//                    //проверка существования оффиса по lotusId (перенес в валидатор)
//
//                    $office = (new Office())
//                        ->fill([
//                            'title' => $item->office,
//                            'lotusId' => $item->lotusId,
//                            'status' => $status,
//                            'address' => $address
//                        ])
//                        ->save();
//                    if (false === $office) {
//                        Office::getDbConnection()->rollbackTransaction();
//                    } else {
//                        Office::getDbConnection()->commitTransaction();
//                    }
//                }
//            } else {
//                //ошибка импорта данных, надо бы выкинуть сообщение об ошибке
//            }
//        } else {
//            Office::getDbConnection()->beginTransaction();
//
//            $city = City::findByPK($data->cityId);
//            $status = OfficeStatus::findByPK($data->statusId);
//            $address = (new Address())
//                ->fill([
//                    'address' => trim($data->address),
//                    'city' => $city
//                ])
//                ->save();
//            $office = (new Office())
//                ->fill([
//                    'title' =>$data->title,
//                    'lotusId' => $data->lotusId,
//                    'status' => $status,
//                    'address' => $address
//                ]);
//            $office->save();
//
//            if (false === $office) {
//                Office::getDbConnection()->rollbackTransaction();
//            } else {
//                Office::getDbConnection()->commitTransaction();
//            }
//        }
//        header('Location: /admin/offices');
    }

    public function actionEditOffice($data)
    {
        /**
         * @var Office $office
         */
        $office = Office::findByLotusId($data['lotusId']);
        $oldAddress = $office->address;
        if (false === $office) {
            header('Location: /admin/offices');
            return;
        }
        Office::getDbConnection()->beginTransaction();
        $city = City::findByPK($data->cityId);
        $status = OfficeStatus::findByPK($data->statusId);
        $newAddress = (new Address())
            ->fill([
                'address' => trim($data->address),
                'city' => $city
            ])
            ->save();
        $office = $office
            ->fill([
                'title' =>$data->title,
                'status' => $status,
                'address' => $newAddress
            ])
            ->save();

        if (false === $office) {
            Office::getDbConnection()->rollbackTransaction();
        } else {
            $oldAddress->delete();
            Office::getDbConnection()->commitTransaction();
        }
        header('Location: /admin/offices');
    }

    public function actionDelOffice($id = null)
    {
        $office = Office::findByPK($id);
        $office->delete();
        $office->address->delete();

        header('Location: /admin/offices');
    }

    public function actionDevparts()
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
        $this->data->platforms = Platform::findAll(['order' => 'title']);
        $this->data->software = Software::findAll(['order' => 'title']);
        $this->data->modules = Module::findAll(['order' => 'title']);
        $this->data->applianceTypes = ApplianceType::findAll(['order' => 'type']);

        $this->data->settings->activeTab = 'platforms';
    }

    public function actionAddApplianceType($applianceType)
    {
        (new ApplianceType())
            ->fill($applianceType)
            ->save();
        header('Location: /admin/devparts');
    }

    public function actionEditApplianceType($applianceType)
    {
        ApplianceType::findByPK($applianceType['id'])
            ->fill([
                'type' => $applianceType['type']
            ])
            ->save();
        header('Location: /admin/devparts');
    }

    public function actionDelApplianceType($id)
    {
        if (false !== $applianceType = ApplianceType::findByPK($id)) {
            $applianceType->delete();
        }
        header('Location: /admin/devparts');
    }

    public function actionAddPlatform($platform)
    {
        (new Platform())
            ->fill([
                'title' => $platform['title'],
                'vendor' => Vendor::findByPK($platform['vendorId'])
            ])
            ->save();
        header('Location: /admin/devparts');
    }

    public function actionEditPlatform($platform)
    {
        Platform::getDbConnection()->beginTransaction();
        $updatedPlatform = (Platform::findByPK($platform['id']))
            ->fill([
                'title' => $platform['title'],
                'vendor' => Vendor::findByPK($platform['vendorId'])
            ])
            ->save();
        if (false === $updatedPlatform) {
            Platform::getDbConnection()->rollbackTransaction();
        } else {
            Platform::getDbConnection()->commitTransaction();
        }
        header('Location: /admin/devparts');
    }

    public function actionDelPlatform($id)
    {
        if (false !== $platform = Platform::findByPK($id)) {
            $platform->delete();
        }
        header('Location: /admin/devparts');
    }

    public function actionAddModule($module)
    {
        //var_dump($module);
        (new Module())
            ->fill([
                'title' => $module['title'],
                'vendor' => Vendor::findByPK($module['vendorId'])
            ])
            ->save();
        header('Location: /admin/devparts');
    }

    public function actionEditModule($module)
    {
        Module::getDbConnection()->beginTransaction();
        $updatedModule = (Module::findByPK($module['id']))
            ->fill([
                'title' => $module['title'],
                'vendor' => Vendor::findByPK($module['vendorId'])
            ])
            ->save();
        if (false === $updatedModule) {
            Module::getDbConnection()->rollbackTransaction();
        } else {
            Module::getDbConnection()->commitTransaction();
        }
        header('Location: /admin/devparts');

    }

    public function actionDelModule($id)
    {
        if (false !== $module = Module::findByPK($id)) {
            $module->delete();
        }
        header('Location: /admin/devparts');
    }

    public function actionAddSoftware($software)
    {
        (new Software())
            ->fill([
                'title' => $software['title'],
                'vendor' => Vendor::findByPK($software['vendorId'])
            ])
            ->save();
        header('Location: /admin/devparts');
    }

    public function actionEditSoftware($software)
    {
        Software::getDbConnection()->beginTransaction();
        $updatedSoftware = (Software::findByPK($software['id']))
            ->fill([
                'title' => $software['title'],
                'vendor' => Vendor::findByPK($software['vendorId'])
            ])
            ->save();
        if (false === $updatedSoftware) {
            Software::getDbConnection()->rollbackTransaction();
        } else {
            Software::getDbConnection()->commitTransaction();
        }
        header('Location: /admin/devparts');
    }

    public function actionDelSoftware($id)
    {
        if (false !== $software = Software::findByPK($id)) {
            $software->delete();
        }
        header('Location: /admin/devparts');
    }

    public function actionAddVendor($vendor)
    {
        (new Vendor())
            ->fill($vendor)
            ->save();
        header('Location: /admin/devparts');
    }

    public function actionEditVendor($vendor)
    {
        Vendor::findByPK($vendor['id'])
            ->fill([
                'title' => $vendor['title']
            ])
            ->save();
        header('Location: /admin/devparts');
    }

    public function actionDelVendor($id)
    {
        if (false !== $vendor = Vendor::findByPK($id)) {
            $vendor->delete();
        }
        header('Location: /admin/devparts');
    }

    public function actionDevices()
    {

    }

    public function actionAddAppliance($data)
    {
        try {
            Appliance::getDbConnection()->beginTransaction();

            $office = Office::findByPK($data->officeId);
            $vendor = Vendor::findByPK($data->vendorId);
            $applianceType = ApplianceType::findByPK($data->applianceTypeId);

            $platformItem = (new PlatformItem())
                ->fill([
                    'platform' => Platform::findByPK($data->platformId),
                    'serialNumber' => $data->platformSn
                ])
                ->save();

            $softwareItem = (new SoftwareItem())
                ->fill([
                    'software' => Software::findByPK($data->softwareId),
                    'version' => $data->softwareVersion
                ])
                ->save();

            $appliance = (new Appliance())
                ->fill([
                    'location' => $office,
                    'vendor' => $vendor,
                    'platform' => $platformItem,
                    'software' => $softwareItem,
                    'type' => $applianceType
                ])
                ->save();
        } catch (MultiException $e) {

        }

        if (false === $appliance) {
            Appliance::getDbConnection()->rollbackTransaction();
        } else {
            Appliance::getDbConnection()->commitTransaction();
        }
        header('Location: /admin/devices');
    }

}