-- DROP indexes
DROP INDEX IF EXISTS geolocation.idx_reg_title;
DROP INDEX IF EXISTS geolocation.idx_city_title;
DROP INDEX IF EXISTS company.idx_office_title;
DROP INDEX IF EXISTS equipment.idx_phone_name;

DROP INDEX IF EXISTS geolocation.idx_region_id;
DROP INDEX IF EXISTS geolocation.idx_city_id;
DROP INDEX IF EXISTS company.idx_address_id;
DROP INDEX IF EXISTS company.idx_office_status_id;

DROP INDEX IF EXISTS equipment.idx_appl_cluster_id;
DROP INDEX IF EXISTS equipment.idx_appl_vendor_id;
DROP INDEX IF EXISTS equipment.idx_platform_item_id;
DROP INDEX IF EXISTS equipment.idx_software_item_id;
DROP INDEX IF EXISTS equipment.idx_appl_location_id;
DROP INDEX IF EXISTS equipment.idx_appl_type_id;
DROP INDEX IF EXISTS equipment.idx_dport_appliance_id;
DROP INDEX IF EXISTS equipment.idx_type_port_id;
DROP INDEX IF EXISTS equipment.idx_network_id;
DROP INDEX IF EXISTS equipment.idx_mod_item_appliance_id;
DROP INDEX IF EXISTS equipment.idx_module_id;
DROP INDEX IF EXISTS equipment.idx_mod_item_loc_id;
DROP INDEX IF EXISTS equipment.idx_mod_vendor_id;
DROP INDEX IF EXISTS equipment.idx_software_id;
DROP INDEX IF EXISTS equipment.idx_soft_vendor_id;
DROP INDEX IF EXISTS equipment.idx_platform_id;
DROP INDEX IF EXISTS equipment.idx_plat_vendor_id;
DROP INDEX IF EXISTS equipment.idx_phone_info_appliance_id;

-- CREATE INDEXES

CREATE INDEX IF NOT EXISTS idx_reg_title ON geolocation.regions(title);
CREATE INDEX IF NOT EXISTS idx_city_title ON geolocation.cities(title);
CREATE INDEX IF NOT EXISTS idx_office_title ON company.offices(title);
CREATE INDEX IF NOT EXISTS idx_phone_name ON equipment."phoneInfo"(name);

-- indexes for foreign keys
-- geo indexes
CREATE INDEX IF NOT EXISTS idx_region_id ON geolocation.cities(__region_id);
CREATE INDEX IF NOT EXISTS idx_city_id ON geolocation.addresses(__city_id);
CREATE INDEX IF NOT EXISTS idx_address_id ON company.offices(__address_id);
CREATE INDEX IF NOT EXISTS idx_office_status_id ON company.offices(__office_status_id);
-- equipment.appliances indexes
CREATE INDEX IF NOT EXISTS idx_appl_cluster_id ON equipment.appliances(__cluster_id);
CREATE INDEX IF NOT EXISTS idx_appl_vendor_id ON equipment.appliances(__vendor_id);
CREATE INDEX IF NOT EXISTS idx_platform_item_id ON equipment.appliances(__platform_item_id);
CREATE INDEX IF NOT EXISTS idx_software_item_id ON equipment.appliances(__software_item_id);
CREATE INDEX IF NOT EXISTS idx_appl_location_id ON equipment.appliances(__location_id);
CREATE INDEX IF NOT EXISTS idx_appl_type_id ON equipment.appliances(__type_id);
-- equipment.dataPorts indexes
CREATE INDEX IF NOT EXISTS idx_dport_appliance_id ON equipment."dataPorts"(__appliance_id);
CREATE INDEX IF NOT EXISTS idx_type_port_id ON equipment."dataPorts"(__type_port_id);
CREATE INDEX IF NOT EXISTS idx_network_id ON equipment."dataPorts"(__network_id);
-- equipment.moduleItems indexes
CREATE INDEX IF NOT EXISTS idx_mod_item_appliance_id ON equipment."moduleItems"(__appliance_id);
CREATE INDEX IF NOT EXISTS idx_module_id ON equipment."moduleItems"(__module_id);
CREATE INDEX IF NOT EXISTS idx_mod_item_loc_id ON equipment."moduleItems"(__location_id);
-- equipment.modules indexes
CREATE INDEX IF NOT EXISTS idx_mod_vendor_id ON equipment.modules(__vendor_id);
-- equipment.softwareItems indexes
CREATE INDEX IF NOT EXISTS idx_software_id ON equipment."softwareItems"(__software_id);
-- equipment.software indexes
CREATE INDEX IF NOT EXISTS idx_soft_vendor_id ON equipment.software(__vendor_id);
-- equipment.platformItems indexes
CREATE INDEX IF NOT EXISTS idx_platform_id ON equipment."platformItems"(__platform_id);
-- equipment.platforms indexes
CREATE INDEX IF NOT EXISTS idx_plat_vendor_id ON equipment.platforms(__vendor_id);
-- equipment.phone_info indexes
CREATE INDEX IF NOT EXISTS idx_phone_info_appliance_id ON equipment."phoneInfo"(__appliance_id);
