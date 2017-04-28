<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';
require_once __DIR__ . '/../EnvironmentTrait.php';


class ModuleItemTest extends \PHPUnit\Framework\TestCase
{
    use DbTrait;
    use EnvironmentTrait;

    public function testCreateLocation()
    {
        return $this->createLocation();
    }

    public function testCreateModule()
    {
        return $this->createModule();
    }

    /**
     * @return $this|bool
     * @param \App\Models\Module $module
     *
     * @depends testCreateModule
     */
    public function testCreateAppliance($module)
    {
        return $this->createAppliance();
    }

    public function providerValidModuleItem()
    {
        return [
            ['sn1', 'sn2', 'inv1', ['name' => 'value']],
            ['sn2', 'sn3', '', ['name' => 'value']],
            ['sn3', 'sn4','inv1', ''],
            ['sn4', 'sn1', '', ''],
        ];
    }


    /**
     * serial, location, appliance, module
     *
     * @return array
     */
    public function providerInvalidModuleItem()
    {
        return [
            ['', true, true, true],
            ['sn1', false, true, true],
            ['sn1', 'location', true, true],
            ['sn1', null, true, true],
        ];
    }

    /**
     * @depends testCreateLocation
     * @depends testCreateModule
     */
    public function testCreateModuleItemWithoutAppliance(
        $location,
        $module
    ) {
        $moduleItem = (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => 'sn123',
                'module' => $module,
                'appliance' => null,
                'location' => $location
            ])
            ->save();

        $this->assertInstanceOf(\App\Models\ModuleItem::class, $moduleItem);
        $this->assertInstanceOf(\App\Models\Module::class, $moduleItem->module);
        $this->assertNull($moduleItem->appliance);
        $this->assertInstanceOf(\App\Models\Office::class, $moduleItem->location);
        $moduleItem->delete();
    }


    /**
     * @param $serialNumber_1
     * @param $serialNumber_2
     * @param $inventoryNumber
     * @param $details
     * @param $location
     * @param $appliance
     * @param $module
     *
     * @dataProvider providerValidModuleItem
     * @depends testCreateLocation
     * @depends testCreateAppliance
     * @depends testCreateModule
     */
    public function testValidModuleItem(
        $serialNumber_1,
        $serialNumber_2,
        $inventoryNumber,
        $details,
        $location,
        $appliance,
        $module
    ) {
        $moduleItem = (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => $serialNumber_1,
                'inventoryNumber' => $inventoryNumber,
                'details' =>$details,
                'module' => $module,
                'appliance' => $appliance,
                'location' => $location
            ])
            ->save();

        $this->assertInstanceOf(\App\Models\ModuleItem::class, $moduleItem);
        $this->assertInstanceOf(\App\Models\Module::class, $moduleItem->module);
        $this->assertInstanceOf(\App\Models\Appliance::class, $moduleItem->appliance);
        $this->assertInstanceOf(\App\Models\Office::class, $moduleItem->location);
    }

    /**
     * @param $serialNumber_1
     * @param $serialNumber_2
     * @param $inventoryNumber
     * @param $details
     * @param $location
     * @param $appliance
     * @param $module
     *
     * @dataProvider providerValidModuleItem
     * @depends testValidModuleItem
     * @depends testCreateLocation
     * @depends testCreateModule
     */
    public function testDoubleModuleItemError_1(
        $serialNumber_1,
        $serialNumber_2,
        $inventoryNumber,
        $details,
        $location,
        $appliance,
        $module
    ) {
        $this->expectException(\T4\Core\Exception::class);

        //expect Exception when create new module item
        (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => $serialNumber_1,
                'inventoryNumber' => $inventoryNumber,
                'details' =>$details,
                'module' => $module,
                'appliance' => $appliance,
                'location' => $location
            ])
            ->save();
    }

    /**
     * @param $serialNumber_1
     * @param $serialNumber_2
     *
     * @dataProvider providerValidModuleItem
     * @depends testValidModuleItem
     */
    public function testDoubleModuleItemError_2(
        $serialNumber_1,
        $serialNumber_2
    ) {
        $this->expectException(\T4\Core\Exception::class);

        //expect Exception when update existed module item
        /**
         * @var \App\Models\ModuleItem $fromDb
         */
        $fromDb = \App\Models\ModuleItem::findBySerialNumber($serialNumber_1);
        $fromDb->serialNumber = $serialNumber_2;
        $fromDb->save();
    }

    /**
     * @param $serialNumber
     * @param $location
     * @param $appliance
     * @param $module
     *
     * @dataProvider providerInvalidModuleItem
     * @depends testCreateLocation
     * @depends testCreateAppliance
     * @depends testCreateModule
     */
    public function testInvalidModuleItem(
        $serialNumber,
        $location,
        $appliance,
        $module,
        $locationItem,
        $applianceItem,
        $moduleItem
    )
    {
        $this->expectException(\T4\Core\Exception::class);

        $location = (true === $location) ? $locationItem : $location;
        $appliance = (true === $appliance) ? $applianceItem : $appliance;
        $module = (true === $module) ? $moduleItem : $module;

        (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => $serialNumber,
                'module' => $module,
                'appliance' => $appliance,
                'location' => $location
            ])
            ->save();
    }

    /**
     * test unlink Appliance
     *
     * @param $serialNumber_1
     *
     * @dataProvider providerValidModuleItem
     * @depends testValidModuleItem
     */
    public function testUnlinkAppliance(
        $serialNumber_1
    ) {
        /**
         * @var \App\Models\ModuleItem $fromDb
         */
        $fromDb = \App\Models\ModuleItem::findBySerialNumber($serialNumber_1);
        $appliance = $fromDb->appliance;
        $this->assertInstanceOf(\App\Models\Appliance::class, $appliance);

        $fromDb->appliance = null;
        $this->assertNull($fromDb->appliance);
        $fromDb->save();
        $this->assertNull($fromDb->appliance);
        $fromDb->refresh();
        $this->assertNull($fromDb->appliance);

        $fromDb
            ->fill([
                'appliance' => $appliance
            ]);
        $this->assertInstanceOf(\App\Models\Appliance::class, $fromDb->appliance);
        $fromDb->save();
        $this->assertInstanceOf(\App\Models\Appliance::class, $fromDb->appliance);
        $fromDb->refresh();
        $this->assertInstanceOf(\App\Models\Appliance::class, $fromDb->appliance);

        //test method unlinkAppliance
        $fromDb->unlinkAppliance();
        $this->assertNull($fromDb->appliance);
        $fromDb->save();
        $this->assertNull($fromDb->appliance);
        $fromDb->refresh();
        $this->assertNull($fromDb->appliance);
    }
}