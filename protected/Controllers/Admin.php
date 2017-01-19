<?php

namespace App\Controllers;

use App\Components\Parser;
use App\Components\Publisher;
use App\Models\Address;
use App\Models\City;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Region;
use T4\Core\Std;
use T4\Mvc\Controller;

class Admin extends Controller
{
    public function actionDefault()
    {

    }

    /**
     * action вывода всех имеющихся статусов
     */
    public function actionOfficeStatuses()
    {
        $this->data->statuses = OfficeStatus::findAll();
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
                $statsInString = preg_replace($pattern, '', $status['many']);
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

    public function actionDelStatus($id = null)
    {
        OfficeStatus::findByPK($id)->delete();
        header('Location: /admin/officeStatuses');
    }

    /**
     * action вывода всех офисов
     */
    public function actionOffices()
    {
        $regions = Region::findAll();
        $this->data->regions = $regions;
        $this->data->statuses = OfficeStatus::findAll();
    }

    /**
     * @param Std $data POST array в виде объекта Std класса
     * В случае $data->many формат записи для офиса: регион; город; адрес; офис; статус
     *
     * @var OfficeStatus $status
     */
    public function actionAddOffice($data)
    {
        if (!empty(trim($data->many))) {
            $officeCollection = Parser::lotusTerritory(trim($data->many));
            if (false !== $officeCollection) {
                foreach ($officeCollection as $item) {
                    //Region
                    $region = Region::findByTitle($item->region);
                    if (false ===$region) {
                        if (isset($data->addNewRegion)) {
                            $region = (new Region())
                                ->fill(['title' => $item->region])
                                ->save();
                        } else {
                            echo 'continue reg';die;

                            continue; //Ошибка. невозможно установить регион, переход к след записи
                        }
                    }

                    //City
                    $city = City::findByTitle($item->city);
                    //если город не найден и разрешено создание нового региона
                    if (false ===$city) {
                        if (isset($data->addNewCity)) {
                            $city = (new City())
                                ->fill(['title' => $item->city, 'region' => $region])
                                ->save();
                        } else {
                            echo 'continue city';die;
                            continue; //Ошибка. невозможно установить город, переход к след записи
                        }
                    }

                    //Address
                    $address = (new Address())
                        ->fill(['address' => $item->address, 'city' => $city])
                        ->save();

                    //Office status
                    //если парсинг откинул поле статуса или оно пустое - берем из формы
                    if (empty($item->status)) {
                        $status = OfficeStatus::findByPK($data->statId);
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
                                echo 'continue status';
                                continue; //Ошибка. невозможно установить статус, переход к след записи
                            }
                        }
                    }

                    //Office
                    //проверка существования оффиса по lotusId
                    if (false !== Office::findByLotusId($item->lotusId)) {
                        continue; //если есть с таким lotusId - к следующей записи
                    }
                    $office = (new Office())
                        ->fill([
                            'title' => $item->office,
                            'lotusId' => $item->lotusId,
                            'address' => $address,
                            'status' => $status
                        ])
                        ->save();
                }
            } else {
                //ошибка импорта данных, надо бы выкинуть сообщение об ошибке
            }
        } else {
            if (empty(trim($data->lotusId))) {
                header('Location: /admin/offices');//LotusId обязательное поле
            } elseif (false !== Office::findByLotusId(trim($data->lotusId))) {
                header('Location: /admin/offices'); //есть офис с таким Lotus ID
            } elseif (empty(trim($data->title))) {
                header('Location: /admin/offices'); //Пустое название офиса
            } elseif (false !== Office::findByTitle($data->title)) {
                header('Location: /admin/offices'); //Офис с таким названием уже есть
            } else {
                $city = City::findByPK($data->cityId);
                $status = OfficeStatus::findByPK($data->statId);
                $address = (new Address())
                    ->fill(['address' => trim($data->address), 'city' => $city])
                    ->save();
                $office = (new Office())
                    ->fill(['title' =>$data->title, 'lotusId' => $data->lotusId, 'address' => $address, 'status' => $status])
                    ->save();
                $office->save();
            }
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

    public function actionCities()
    {
        $this->data->regions = Region::findAll(['order' => 'title']);
    }

    public function actionAddCity($city)
    {
        $region = Region::findByPK($city['regId']);

        $newCity = (new City())
            ->fill(['title' => $city['title']]);
        $newCity->region = $region;
        $newCity->save();

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

    public function actionDelRegion($id)
    {
        Region::findByPK($id)->delete();
        header('Location: /admin/regions');
    }

    public function actionDevices()
    {

    }

}