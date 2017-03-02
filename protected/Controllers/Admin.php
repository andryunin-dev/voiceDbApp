<?php

namespace App\Controllers;

use App\Components\Parser;
use App\Components\Publisher;
use App\Models\Address;
use App\Models\City;
use App\Models\Module;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Platform;
use App\Models\Region;
use App\Models\Software;
use App\Models\Vendor;
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
        if (!empty($region)) {
            if (!empty(trim($region['many']))) {
                $pattern = '~[\n\r]~';
                $regsInString = preg_replace($pattern, '', trim($region['many']));
                $regInArray = explode(',', $regsInString);

                foreach ($regInArray as $region) {
                    (new Region())
                        ->fill(['title' => trim($region)])
                        ->save();
                }

            } elseif (!empty(trim($region['one']))) {
                (new Region())
                    ->fill(['title' => $region['one']])
                    ->save();
            }
        }
        header('Location: /admin/Regions');
    }

    public function actionEditRegion($region)
    {
        if (!empty(trim($region['title'])) && (false !== $item = Region::findByPK($region['id']))) {
            $item->fill([
                'title' => $region['title']
            ]);
            $item->save();
        }
        header('Location: /admin/regions');
    }

    public function actionDelRegion($id)
    {
        if (false !== $region = Region::findByPK($id)) {
            $region->delete();
        }
        header('Location: /admin/regions');
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
        $region = Region::findByPK($city['regId']);

        $newCity = (new City())
            ->fill([
                'title' => $city['title'],
                'region' => $region
            ]);
        $newCity->save();

        header('Location: /admin/cities');
    }

    public function actionEditCity($city)
    {
        $currentCity = City::findByPK($city['id']);
        $currentCity->title = $city['title'];
        $currentCity->region = Region::findByPK($city['regId']);
        $currentCity->save();

        header('Location: /admin/cities');
    }

    public function actionDelCity($id = null)
    {
        if (!empty($id)) {
            City::findByPK($id)
                ->delete();
        }

        header('Location: /admin/cities');
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
        if (!empty($status)) {
            if (!empty(trim($status['many']))) {
                $pattern = '~[\n\r]~';
                $statsInString = preg_replace($pattern, ',', $status['many']);
                $statsInArray = explode(',', $statsInString);

                foreach ($statsInArray as $status) {
                    (new OfficeStatus())
                        ->fill(['title' => trim($status)])
                        ->save();
                }
            } elseif (!empty(trim($status['one']))) {
                (new OfficeStatus())
                    ->fill(['title' => trim($status['one'])])
                    ->save();
            }
        }
        header('Location: /admin/OfficeStatuses');
    }

    public function actionEditStatus($status)
    {
        if (true == $currentStatus = OfficeStatus::findByPK($status['id'])) {
            $currentStatus->fill([
                'title' => $status['title']
            ]);
            $currentStatus->save();
        }
        header('Location: /admin/OfficeStatuses');
    }

    public function actionDelStatus($id = null)
    {
        OfficeStatus::findByPK($id)->delete();
        header('Location: /admin/officeStatuses');
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
     * В случае $data->many формат записи для офиса: регион; город; адрес; офис; статус
     */
    public function actionAddOffice($data)
    {
        if (!empty(trim($data->many))) {
            $officeCollection = Parser::lotusTerritory(trim($data->many));
            if (false !== $officeCollection) {
                foreach ($officeCollection as $item) {
                    Office::getDbConnection()->beginTransaction();
                    //Region
                    $region = Region::findByTitle($item->region);
                    if (false === $region) {
                        if (isset($data->addNewRegion)) {
                            $region = (new Region())
                                ->fill(['title' => $item->region])
                                ->save();
                        } else {
                            $region = false;
                        }
                    }

                    //City
                    $city = City::findByTitle($item->city);
                    //если город не найден и разрешено создание нового региона
                    if (false === $city) {
                        if (isset($data->addNewCity)) {
                            $city = (new City())
                                ->fill([
                                    'title' => $item->city,
                                    'region' => $region
                                ])
                                ->save();
                        } else {
                            $city = false;
                        }
                    }

                    //Address
                    $address = (new Address())
                        ->fill([
                            'address' => $item->address,
                            'city' => $city
                        ])
                        ->save();

                    //Office status
                    //если парсинг откинул поле статуса или оно пустое - берем из формы
                    if (empty($item->status)) {
                        $status = OfficeStatus::findByPK($data->statusId);
                        if (false ===$status) {
                            continue; //Ошибка. невозможно установить статус, переход к след записи
                        }
                    } else {
                        $status = OfficeStatus::findByTitle($item->status);
                        if (false === $status) {
                            if (isset($data->addNewStatus)) {
                                $status = (new OfficeStatus())
                                    ->fill(['title' => $item->status])
                                    ->save();
                            } else {
                                continue; //Ошибка. невозможно установить статус, переход к след записи
                            }
                        }
                    }

                    //Office
                    //проверка существования оффиса по lotusId (перенес в валидатор)

                    $office = (new Office())
                        ->fill([
                            'title' => $item->office,
                            'lotusId' => $item->lotusId,
                            'status' => $status,
                            'address' => $address
                        ])
                        ->save();
                    if (false === $office) {
                        Office::getDbConnection()->rollbackTransaction();
                    } else {
                        Office::getDbConnection()->commitTransaction();
                    }
                }
            } else {
                //ошибка импорта данных, надо бы выкинуть сообщение об ошибке
            }
        } else {
            Office::getDbConnection()->beginTransaction();

            $city = City::findByPK($data->cityId);
            $status = OfficeStatus::findByPK($data->statusId);
            $address = (new Address())
                ->fill([
                    'address' => trim($data->address),
                    'city' => $city
                ])
                ->save();
            $office = (new Office())
                ->fill([
                    'title' =>$data->title,
                    'lotusId' => $data->lotusId,
                    'status' => $status,
                    'address' => $address
                ]);
            $office->save();

            if (false === $office) {
                Office::getDbConnection()->rollbackTransaction();
            } else {
                Office::getDbConnection()->commitTransaction();
            }
        }
        header('Location: /admin/offices');
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
        $this->data->settings->activeTab = 'platforms';
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

    }

    public function actionDelModule($module)
    {

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



























    public function actionAddVendorold($vendor)
    {
        if (!empty($vendor)) {
            if (!empty(trim($vendor['many']))) {
                $pattern = '~[\n\r]~';
                $vendorListStr = preg_replace($pattern, '', trim($vendor['many']));
                $vendorListArray = explode(',', $vendorListStr);

                foreach ($vendorListArray as $vendor) {
                    (new Vendor())
                        ->fill(['title' => trim($vendor)])
                        ->save();
                }

            } elseif (!empty(trim($vendor['one']))) {
                (new Vendor())
                    ->fill(['title' => $vendor['one']])
                    ->save();
            }
        }
        header('Location: /admin/devparts');
    }

    public function actionDelVendorold($id)
    {
        if (false !== $vendor = Vendor::findByPK($id)) {
            $vendor->delete();
        }
        header('Location: /admin/devparts');
    }



    public function actionAddSoftwareold($software)
    {
        if (!empty(trim($software['title']))) {
            (new Software())
                ->fill([
                    'title' => $software['title'],
                    'vendor' => Vendor::findByPK($software['vendorId'])
                ])
                ->save();
        }
        header('Location: /admin/devparts');
    }

    public function actionDelSoftwareold($id)
    {
        if (false !== $software = Software::findByPK($id)) {
            $software->delete();
        }
        header('Location: /admin/devparts');
    }

}