<?php

trait EnvironmentTrait
{
    public $region;
    public $city;
    public $officeStatus;
    public $office;
    public $vendor;
    public $cluster;
    public $appliance;
    public $applianceType;
    public $platform;
    public $platformItem;
    public $software;
    public $softwareItem;
    public $module;
    public $moduleItem;
    public $vlan;
    public $vrf;
    public $network;
    public $dPortType;
    public $dataPort;


    public function createRegion($title = 'test')
    {
        if (false === $region = \App\Models\Region::findByTitle($title)) {
            $region = (new \App\Models\Region())
                ->fill(['title' => $title])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Region::class, $region);
        return $this->region = $region;
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
        return $this->city = $city;
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
        return $this->officeStatus = $status;
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
        return $this->office = $office;
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
        return $this->vlan = $vlan;
    }

    public function createVrf($name = 'test', $rd = '10:100')
    {
        if (false === $vrf = \App\Models\Vrf::findByRd($rd)) {
            $vrf = (new \App\Models\Vrf())
                ->fill([
                    'name' => $name,
                    'rd' => $rd,
                    'comment' => 'test'
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Vrf::class, $vrf);
        return $this->vrf = $vrf;
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
        return $this->vendor = $vendor;
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
        return $this->platform = $platform;
    }

    public function createPlatformItem(
        $version = 'testVersion',
        $invNumber = 'testInventoryNumber',
        $serialNumber = 'testSerialNumber',
        $details = ['name' => 'value'],
        $platform = null
    ) {
        $platform = $platform ?? $this->createPlatform();
        if (false === $platformItem = \App\Models\PlatformItem::findByColumn('serialNumber', $serialNumber)) {
            $platformItem = (new \App\Models\PlatformItem())
                ->fill([
                    'version' => $version,
                    'inventoryNumber' => $invNumber,
                    'serialNumber' => $serialNumber,
                    'details' => $details,
                    'platform' => $platform
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\PlatformItem::class, $platformItem);
        return $this->platformItem = $platformItem;
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
        return $this->software = $software;
    }

    public function createSoftwareItem(
        $version = 'testVersion',
        $details = ['name' => 'value'],
        $software = null
    ) {
        $software = $software ?? $this->createSoftware();
        if (false === $softwareItem = \App\Models\SoftwareItem::findByVersion($version)) {
            $softwareItem = (new \App\Models\SoftwareItem())
                ->fill([
                    'version' => $version,
                    'details' => $details,
                    'software' => $software
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $softwareItem);
        return $this->softwareItem = $softwareItem;
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
        return $this->applianceType = $applianceType;
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

        return $this->cluster = $cluster;
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
        return $this->appliance = $appliance;
    }

    public function createModule($title = 'test', $vendor = null)
    {
        $vendor = $vendor ?? $this->createVendor();
        if (false === $module = \App\Models\Module::findByTitle($title)) {
            $module = (new \App\Models\Module())
                ->fill([
                    'title' => $title,
                    'vendor' => $vendor
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\Module::class, $module);
        return $this->module = $module;
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
        return $this->moduleItem = $moduleItem;
    }

    public function createDataPortType($type = 'test')
    {
        if (false === $dPortType = \App\Models\DPortType::findByType($type)) {
            $dPortType = (new \App\Models\DPortType())
                ->fill([
                    'type' => 'test'
                ])
                ->save();
        }
        $this->assertInstanceOf(\App\Models\DPortType::class, $dPortType);
        return $this->dPortType = $dPortType;
    }

    public function createDataPort(
        $ipAddress = '1.1.1.1/24',
        $vrf = null,
        $macAddress = '00-11-22-33-44-55',
        $appliance = null,
        $portType = null
    ) {
        $vrf = $vrf ?? $this->createVrf();
        $appliance = $appliance ?? $this->createAppliance();
        $portType = $portType ?? $this->createDataPortType();

        if (false === $dataPort = \App\Models\DataPort::findByIpVrf($ipAddress, $vrf)) {
            $dataPort = (new \App\Models\DataPort())
                ->fill([
                    'ipAddress' => $ipAddress,
                    'macAddress' => $macAddress,
                    'appliance' => $appliance,
                    'portType' => $portType,
                    'vrf' => $vrf
                ])
                ->save();

            $this->assertInstanceOf(\App\Models\DataPort::class, $dataPort);
            return $this->dataPort = $dataPort;
        }
    }
}