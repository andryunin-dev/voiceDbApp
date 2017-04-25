<?php

trait EnvironmentTrait
{
    public function createRegion($title = 'test')
    {
        if (false === $region = \App\Models\Region::findByTitle($title)) {
            $region = (new \App\Models\Region())
                ->fill(['title' => $title])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Region::class, $region);
        return $region;
    }

    public function createCity($title = 'test')
    {
        if (false === $city = \App\Models\City::findByTitle($title)) {
            $city = (new \App\Models\City())
                ->fill([
                    'title' => $title,
                    'region' => $this->createRegion()
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\City::class, $city);
        return $city;
    }

    public function createOfficeStatus($title = 'test')
    {
        if (false === $status = \App\Models\OfficeStatus::findByTitle($title)) {
            $status = (new \App\Models\OfficeStatus())
                ->fill([
                    'title' => $title
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\OfficeStatus::class, $status);
        return $status;
    }

    public function createLocation($lotusId = 1, $title = 'test')
    {
        $address = (new \App\Models\Address())
            ->fill([
                'address' => 'test',
                'city' => $this->createCity()
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Address::class, $address);

        if (false !== $office = \App\Models\Office::findByColumn('lotusId', $lotusId)) {
            $this->assertInstanceOf(\App\Models\Office::class, $office);
            return $office;
        }
       if (false !== $office = \App\Models\Office::findByTitle($title)) {
           $this->assertInstanceOf(\App\Models\Office::class, $office);
           return $office;
        }
        $office = (new \App\Models\Office())
            ->fill([
                'title' => $title,
                'status' => $this->createOfficeStatus(),
                'lotusId' => $lotusId,
                'address' => $address
            ]);
            $office->save();
        $this->assertInstanceOf(\App\Models\Office::class, $office);
        return $office;
    }

    public function createOffice()
    {
        return $this->createLocation();
    }

    public function createVlan($id = 1, $name = 'test')
    {
        $vlan = (new \App\Models\Vlan())
            ->fill([
                'id' => $id,
                'name' => $name,
                'comment' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Vlan::class, $vlan);
        return $vlan;
    }

    public function createVrf($name = 'test', $rd = '10:100')
    {
        $vrf = (new \App\Models\Vrf())
            ->fill([
                'name' => $name,
                'rd' => $rd,
                'comment' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Vrf::class, $vrf);
        return $vrf;
    }

    public function createVendor($title = 'test')
    {
        if (false === $vendor = \App\Models\Vendor::findByTitle($title)) {
            $vendor = (new \App\Models\Vendor())
                ->fill([
                    'title' => $title
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Vendor::class, $vendor);
        return $vendor;
    }

    public function createPlatform($title = 'test', $vendor = null)
    {
        $vendor = $vendor ?? $this->createVendor();
        if (false === $platform = \App\Models\Platform::findByTitle($title)) {
            $platform = (new \App\Models\Platform())
                ->fill([
                    'title' => $title,
                    'vendor' => $vendor
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Platform::class, $platform);
        return $platform;
    }

    public function createPlatformItem(
        $version = 'testVersion',
        $invNumber = 'testInventoryNumber',
        $serialNumber = 'testSerialNumber',
        $details = ['name' => 'value'],
        $platform = null
    ) {
        $platform = $platform ?? $this->createPlatform();
        $platformItem = (new \App\Models\PlatformItem())
            ->fill([
                'version' => $version,
                'inventoryNumber' => $invNumber,
                'serialNumber' => $serialNumber,
                'details' => $details,
                'platform' => $platform
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\PlatformItem::class, $platformItem);
        return $platformItem;
    }

    public function createSoftware($title = 'test', $vendor = null)
    {
        $vendor = $vendor ?? $this->createVendor();
        if (false === $software = \App\Models\Software::findByTitle($title)) {
            $software = (new \App\Models\Software())
                ->fill([
                    'title' => $title,
                    'vendor' => $vendor
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Software::class, $software);
        return $software;
    }

    public function createSoftwareItem(
        $version = 'testVersion',
        $details = ['name' => 'value'],
        $software = null
    ) {
        $software = $software ?? $this->createSoftware();
        $softwareItem = (new \App\Models\SoftwareItem())
            ->fill([
                'version' => $version,
                'details' => $details,
                'software' => $software
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $softwareItem);
        return $softwareItem;
    }

    public function createApplianceType($type = 'test')
    {
        if (false === $applianceType = \App\Models\ApplianceType::findByType($type)) {
            $applianceType = (new \App\Models\ApplianceType())
                ->fill([
                    'type' => $type
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\ApplianceType::class, $applianceType);
        return $applianceType;
    }

    public function createCluster($title = 'test')
    {
        if (false === $cluster = \App\Models\Cluster::findByTitle($title)) {
            $cluster = (new \App\Models\Cluster())
                ->fill([
                    'title' => $title,
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Cluster::class, $cluster);

        return $cluster;
    }

    public function createAppliance(
        $applianceType = null,
        $cluster = null,
        $vendor = null,
        $platformItem = null,
        $softwareItem = null,
        $office = null
    ) {
        $applianceType = $applianceType ?? $this->createApplianceType();
        $cluster = $cluster ?? $this->createCluster();
        $vendor = $vendor ?? $this->createVendor();
        $platformItem = $platformItem ?? $this->createPlatformItem();
        $softwareItem = $softwareItem ?? $this->createSoftwareItem();
        $office = $office ?? $this->createOffice();
        $appliance = (new \App\Models\Appliance())
            ->fill([
                'type' => $applianceType,
                'cluster' => $cluster,
                'vendor' => $vendor,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'location' => $office
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Appliance::class, $appliance);
        return $appliance;
    }

    public function createModule($title = 'test', $vendor = null)
    {
        $vendor = $vendor ?? $this->createVendor();
        $module = (new \App\Models\Module())
            ->fill([
                'title' => $title,
                'vendor' => $vendor
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Module::class, $module);
        return $module;
    }

    public function createModuleItem(
        $invNumber = 'testInventoryNumber',
        $serialNumber = 'testSerialNumber',
        $details = ['name' => 'value'],
        $module = null,
        $appliance = null,
        $location = null
    ) {
        $module = $module ?? $this->createModule();
        $appliance = $appliance ?? $this->createAppliance();
        $location = $location ?? $this->createLocation();
        $moduleItem = (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => $serialNumber,
                'inventoryNumber' => $invNumber,
                'details' => $details,
                'module' => $module,
                'appliance' => $appliance,
                'location' => $location
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\ModuleItem::class, $moduleItem);
        return $moduleItem;
    }
}