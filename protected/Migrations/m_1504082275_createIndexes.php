<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1504082275_createIndexes
    extends Migration
{

    public function up()
    {
        $sql['idx_reg_title'] = 'CREATE INDEX idx_reg_title ON geolocation.regions(title)';
        $sql['idx_city_title'] = 'CREATE INDEX idx_city_title ON geolocation.cities(title)';
        $sql['idx_office_title'] = 'CREATE INDEX idx_office_title ON company.offices(title)';
        $sql['idx_phone_name'] = 'CREATE INDEX idx_phone_name ON equipment."phoneInfo"(name)';

        $sql['idx_region_id'] = 'CREATE INDEX idx_region_id ON geolocation.cities(__region_id)';
        $sql['idx_city_id'] = 'CREATE INDEX idx_city_id ON geolocation.addresses(__city_id)';
        $sql['idx_address_id'] = 'CREATE INDEX idx_address_id ON company.offices(__address_id)';
        $sql['idx_office_status_id'] = 'CREATE INDEX idx_office_status_id ON company.offices(__office_status_id)';

        $sql['idx_appl_cluster_id'] = 'CREATE INDEX idx_appl_cluster_id ON equipment.appliances(__cluster_id)';
        $sql['idx_appl_vendor_id'] = 'CREATE INDEX idx_appl_vendor_id ON equipment.appliances(__vendor_id)';
        $sql['idx_platform_item_id'] = 'CREATE INDEX idx_platform_item_id ON equipment.appliances(__platform_item_id)';
        $sql['idx_software_item_id'] = 'CREATE INDEX idx_software_item_id ON equipment.appliances(__software_item_id)';
        $sql['idx_appl_location_id'] = 'CREATE INDEX idx_appl_location_id ON equipment.appliances(__location_id)';
        $sql['idx_appl_type_id'] = 'CREATE INDEX idx_appl_type_id ON equipment.appliances(__type_id)';
        $sql['idx_dport_appliance_id'] = 'CREATE INDEX idx_dport_appliance_id ON equipment."dataPorts"(__appliance_id)';
        $sql['idx_type_port_id'] = 'CREATE INDEX idx_type_port_id ON equipment."dataPorts"(__type_port_id)';
        $sql['idx_network_id'] = 'CREATE INDEX idx_network_id ON equipment."dataPorts"(__network_id)';
        $sql['idx_mod_item_appliance_id'] = 'CREATE INDEX idx_mod_item_appliance_id ON equipment."moduleItems"(__appliance_id)';
        $sql['idx_module_id'] = 'CREATE INDEX idx_module_id ON equipment."moduleItems"(__module_id)';
        $sql['idx_mod_item_loc_id'] = 'CREATE INDEX idx_mod_item_loc_id ON equipment."moduleItems"(__location_id)';
        $sql['idx_mod_vendor_id'] = 'CREATE INDEX idx_mod_vendor_id ON equipment.modules(__vendor_id)';
        $sql['idx_software_id'] = 'CREATE INDEX idx_software_id ON equipment."softwareItems"(__software_id)';
        $sql['idx_soft_vendor_id'] = 'CREATE INDEX idx_soft_vendor_id ON equipment.software(__vendor_id)';
        $sql['idx_platform_id'] = 'CREATE INDEX idx_platform_id ON equipment."platformItems"(__platform_id)';
        $sql['idx_plat_vendor_id'] = 'CREATE INDEX idx_plat_vendor_id ON equipment.platforms(__vendor_id)';
        $sql['idx_phone_info_appliance_id'] = 'CREATE INDEX idx_phone_info_appliance_id ON equipment."phoneInfo"(__appliance_id)';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['idx_reg_title'] = 'DROP INDEX geolocation.idx_reg_title';
        $sql['idx_city_title'] = 'DROP INDEX geolocation.idx_city_title';
        $sql['idx_office_title'] = 'DROP INDEX company.idx_office_title';
        $sql['idx_phone_name'] = 'DROP INDEX equipment.idx_phone_name';

        $sql['idx_region_id'] = 'DROP INDEX geolocation.idx_region_id';
        $sql['idx_city_id'] = 'DROP INDEX geolocation.idx_city_id';
        $sql['idx_address_id'] = 'DROP INDEX company.idx_address_id';
        $sql['idx_office_status_id'] = 'DROP INDEX company.idx_office_status_id';

        $sql['idx_appl_cluster_id'] = 'DROP INDEX equipment.idx_appl_cluster_id';
        $sql['idx_appl_vendor_id'] = 'DROP INDEX equipment.idx_appl_vendor_id';
        $sql['idx_platform_item_id'] = 'DROP INDEX equipment.idx_platform_item_id';
        $sql['idx_software_item_id'] = 'DROP INDEX equipment.idx_software_item_id';
        $sql['idx_appl_location_id'] = 'DROP INDEX equipment.idx_appl_location_id';
        $sql['idx_appl_type_id'] = 'DROP INDEX equipment.idx_appl_type_id';
        $sql['idx_dport_appliance_id'] = 'DROP INDEX equipment.idx_dport_appliance_id';
        $sql['idx_type_port_id'] = 'DROP INDEX equipment.idx_type_port_id';
        $sql['idx_network_id'] = 'DROP INDEX equipment.idx_network_id';
        $sql['idx_mod_item_appliance_id'] = 'DROP INDEX equipment.idx_mod_item_appliance_id';
        $sql['idx_module_id'] = 'DROP INDEX equipment.idx_module_id';
        $sql['idx_mod_item_loc_id'] = 'DROP INDEX equipment.idx_mod_item_loc_id';
        $sql['idx_mod_vendor_id'] = 'DROP INDEX equipment.idx_mod_vendor_id';
        $sql['idx_software_id'] = 'DROP INDEX equipment.idx_software_id';
        $sql['idx_soft_vendor_id'] = 'DROP INDEX equipment.idx_soft_vendor_id';
        $sql['idx_platform_id'] = 'DROP INDEX equipment.idx_platform_id';
        $sql['idx_plat_vendor_id'] = 'DROP INDEX equipment.idx_plat_vendor_id';
        $sql['idx_phone_info_appliance_id'] = 'DROP INDEX equipment.idx_phone_info_appliance_id';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}