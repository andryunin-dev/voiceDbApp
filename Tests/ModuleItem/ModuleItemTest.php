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

    public function providerValidModuleItemData()
    {
        return [
            ['sn1', 'inv1', ['name' => 'value']],
            ['sn2', '', ['name' => 'value']],
            ['sn3', 'inv1', ''],
            ['sn4', '', ''],
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
     * @dataProvider providerValidModuleItemData
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
}