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
            ['sn1', 'inv1', ['name' => 'value']],
            ['sn2', '', ['name' => 'value']],
            ['sn3', 'inv1', ''],
            ['sn4', '', ''],
        ];
    }


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
     * @param $serialNumber
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
        $serialNumber,
        $inventoryNumber,
        $details,
        $location,
        $appliance,
        $module
    ) {
        $moduleItem = (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => $serialNumber,
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
     * @param $serialNumber
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
     * @depends testValidModuleItem
     */
    public function testDoubleModuleItemError(
        $serialNumber,
        $inventoryNumber,
        $details,
        $location,
        $appliance,
        $module
    ) {
        $this->expectException(\T4\Core\Exception::class);

        (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => $serialNumber,
                'inventoryNumber' => $inventoryNumber,
                'details' =>$details,
                'module' => $module,
                'appliance' => $appliance,
                'location' => $location
            ])
            ->save();
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
}