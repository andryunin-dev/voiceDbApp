<?php

require __DIR__ . '/../protected/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../protected/boot.php';

class ModelsTest extends \PHPUnit\Framework\TestCase
{
    public function compareModels($model1, $model2)
    {
        foreach ($model1 as $key => $value) {
            if (!isset($model2->$key)) {
                return false;
            }
            if ($model1->$key !== $model2->$key) {
                if ($key != get_class($model1)::PK) {
                    return false;
                }
                if ($key == get_class($model1)::PK && !empty($model1->$key) && !empty($model1->$key)) {
                    return false;
                }
            }
            //echo get_class($model1) . '->' . $key . ' == ' . get_class($model2) . '->' . $key . "\n";
        }
        return true;
    }

    public function testInit()
    {
        $config = new \T4\Core\Config(__DIR__ . '/../protected/config.php');
        $app = \T4\Console\Application::instance();
        $app->setConfig($config);
        $conn = $app->db->phpUnitTest;

        \T4\Orm\Model::setConnection($conn);
        $this->assertInstanceOf('\T4\Dbal\Connection', \T4\Orm\Model::getDbConnection());
    }

    /**
     * GEOLOCATION TESTS
     *
     * @depends testInit
     */
    public function testRegionModel()
    {
        $region = (new \App\Models\Region())
            ->fill([
                'title' => 'title region'
            ])
            ->save();
        $fromDb = \App\Models\Region::findByPK($region->getPk());
        $this->assertEquals(true, $this->compareModels($region, $fromDb));
        return $region;
    }

    /**
     * @depends testRegionModel
     */
    public function testCityModel(\App\Models\Region $region)
    {
        $city = (new \App\Models\City())
            ->fill([
                'title' => 'title city',
                'diallingCode' => '1234',
                'region' => $region
            ])
            ->save();
        $fromDb = \App\Models\City::findByPK($city->getPk());
        $this->assertEquals(true, $this->compareModels($city, $fromDb));
        return $city;
    }

    /**
     * @depends testCityModel
     */
    public function testAddressModel($city)
    {
        $address = (new \App\Models\Address())
            ->fill([
                'address' => 'addresses text',
                'city' => $city
            ])
            ->save();
        $fromDb = \App\Models\Address::findByPK($address->getPk());
        $this->assertEquals(true, $this->compareModels($address, $fromDb));
        return $address;
    }

    /**
     * COMPANY TESTS
     * @depends testInit
     *
     */
    public function testOfficeStatusModel()
    {
        $officeStatus = (new \App\Models\OfficeStatus())
            ->fill([
                'title' => 'title office status'
            ])
            ->save();
        $fromDb = \App\Models\OfficeStatus::findByPK($officeStatus->getPk());
        $this->assertEquals(true, $this->compareModels($officeStatus, $fromDb));
        return $officeStatus;
    }

    /**
     * @depends testOfficeStatusModel
     * @depends testAddressModel
     *
     */
    public function testOfficeModel($officeStatus, $address)
    {
        $office = (new \App\Models\Office())
            ->fill([
                'title' => 'title office',
                'lotusId' => rand(0, 2147483647),
                'details' => ['propName' => 'propValue'],
                'comment' => 'comment',
                'status' => $officeStatus,
                'address' => $address
            ])
            ->save();
        $fromDb = \App\Models\Office::findByPK($office->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($office, $fromDb));
        return $office;
    }

    /**
     * EQUIPMENT TESTS
     * @depends testInit
     *
     */
    public function testVendor()
    {
        $vendor = (new \App\Models\Vendor())
            ->fill([
                'title' => 'vendor name'
            ])
            ->save();
        $fromDb = \App\Models\Vendor::findByPK($vendor->getPk());
        $this->assertEquals(true, $this->compareModels($vendor, $fromDb));
        return $vendor;
    }

    /**
     * @depends testVendor
     */
    public function testSoftware($vendor)
    {
        $objectName = 'software';
        $className = '\App\Models\Software';
        $$objectName = (new $className())
            ->fill([
                'title' => 'soft title',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testSoftware
     */
    public function testSoftwareItem($software)
    {
        $objectName = 'softwareItem';
        $className = '\App\Models\SoftwareItem';
        $$objectName = (new $className())
            ->fill([
                'version' => 'soft ver',
                'details' => ['propName' => 'propValue'],
                'comment' => 'soft comment',
                'software' => $software
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testVendor
     */
    public function testModule($vendor)
    {
        $module = (new \App\Models\Module())
            ->fill([
                'partNumber' => 'part Number',
                'comment' => 'comment',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = \App\Models\Module::findByPK($module->getPk());
        $this->assertEquals(true, $this->compareModels($module, $fromDb));
        return $module;
    }

    /**
     * @depends testVendor
     */
    public function testPlatform($vendor)
    {
        $objectName = 'platform';
        $className = '\App\Models\Platform';
        $$objectName = (new $className())
            ->fill([
                'title' => 'platform title',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testPlatform
     */
    public function testPlatformItem($platform)
    {
        $objectName = 'platformItem';
        $className = '\App\Models\PlatformItem';
        $$objectName = (new $className())
            ->fill([
                'version' => 'platform ver',
                'inventoryNumber' => 'inventory Number',
                'serialNumber' => 'serial Number',
                'details' => ['propName' => 'propValue'],
                'comment' => 'platform comment',
                'platform' => $platform
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }



//   /**
//     * @depends testVendor
//     */
//    public function testModuleItem($vendor)
//    {
//        $module = (new \App\Models\ModuleItem())
//            ->fill([
//                'partNumber' => 'part Number',
//                'comment' => 'comment',
//                'vendor' => $vendor
//            ])
//            ->save();
//        $fromDb = \App\Models\Module::findByPK($module->getPk());
//        $this->assertEquals(true, $this->compareModels($module, $fromDb));
//        return $module;
//    }
}