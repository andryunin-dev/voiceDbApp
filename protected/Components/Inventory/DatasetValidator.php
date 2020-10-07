<?php
namespace App\Components\Inventory;

class DatasetValidator
{
    /**
     * Validate data structure
     *
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        if (!isset($data['dataSetType'])) {
            return false;
        }
        $validate = false;
        switch ($data['dataSetType']) {
            case 'cluster':
                $validate = $this->validateCluster($data);
                break;
            case 'appliance':
                $validate = $this->validateAppliance($data);
                break;
            case 'prefixes':
                $validate = $this->validatePrefixes($data);
                break;
            case 'error':
                $validate = $this->validateError($data);
                break;
            case 'phone':
                $validate = $this->validatePhone($data);
                break;
            default:
                break;
        }
        return $validate;
    }

    /**
     * Validate "Appliance" data structure
     * {
     *   "dataSetType",
     *   "applianceType",
     *   "platformVendor",
     *   "platformTitle",
     *   "chassis",
     *   "platformSerial",
     *   "applianceSoft",
     *   "softwareVersion",
     *   "LotusId",
     *   "hostname",
     *   "ip",
     *   "vrf_name",
     *   "applianceModules": [
     *     {
     *        "serial",
     *        "product_number",
     *        "description",
     *     }
     *   ]
     * }
     * @param array $data
     * @return boolean
     */
    private function validateAppliance(array $data): bool
    {
        if (!isset(
            $data['dataSetType'],
            $data['applianceType'],
            $data['platformVendor'],
            $data['platformTitle'],
            $data['chassis'],
            $data['platformSerial'],
            $data['applianceSoft'],
            $data['softwareVersion'],
            $data['LotusId'],
            $data['hostname'],
            $data['applianceModules']
        )) return false;
        if (!is_array($data['applianceModules'])) return false;
        foreach ($data['applianceModules'] as $module) {
            if (!is_array($module)) return false;
            if (!isset(
                $module['serial'],
                $module['product_number'],
                $module['description']
            )) return false;
        }
        if (!array_key_exists('ip', $data) ||
            !array_key_exists('vrf_name', $data)
        ) return false;
        if (!is_null($data['ip']) &&
            is_null($data['vrf_name'])
        ) return false;
        return true;
    }

    /**
     * Validate "Cluster" data structure
     * {
     *   "dataSetType",
     *   "hostname",
     *   "clusterAppliances": [
     *      {
     *        "dataSetType",
     *        "applianceType",
     *        "platformVendor",
     *        "platformTitle",
     *        "chassis",
     *        "platformSerial",
     *        "applianceSoft",
     *        "softwareVersion",
     *        "LotusId",
     *        "hostname",
     *        "ip",
     *        "vrf_name",
     *        "applianceModules": [
     *          {
     *             "serial",
     *             "product_number",
     *             "description",
     *          }
     *        ]
     *      }
     *   ]
     *
     *   "ip",  (do not used)
     *   "vrf_name",  (do not used)
     *   "platformSerial",  (do not used)
     *   "softwareVersion",  (do not used)
     *   "chassis",  (do not used)
     *   "LotusId",  (do not used)
     *   "applianceType",  (do not used)
     *   "platformTitle",  (do not used)
     *   "applianceSoft",  (do not used)
     *   "platformVendor",  (do not used)
     * }
     * @param array $data
     * @return boolean
     */
    private function validateCluster(array $data): bool
    {
        if (!isset(
            $data['dataSetType'],
            $data['hostname'],
            $data['clusterAppliances']
        )) return false;
        if (!is_array($data['clusterAppliances'])) return false;
        foreach ($data['clusterAppliances'] as $appliance) {
            if (!is_array($appliance)) return false;
            if (!$this->validateAppliance($appliance)) return false;
        }
        return true;
    }

    /**
     * Validate "Prefixes" data structure
     * {
     *   "dataSetType",
     *   "bgp_as",
     *   "bgp_networks" : [],
     *   "ip",
     *   "vrf_name",
     *   "networks": [
     *     {
     *        "interface",
     *        "ip_address",
     *        "vrf_name",
     *        "vrf_rd",
     *        "vni",
     *        "description",
     *        "mac",
     *     }
     *   ]
     * }
     * @param array $data
     * @return boolean
     */
    private function validatePrefixes(array $data): bool
    {
        if (!isset(
            $data['dataSetType'],
            $data['bgp_as'],
            $data['bgp_networks'],
            $data['ip'],
            $data['vrf_name'],
            $data['networks']
        )) return false;
        if (!is_array($data['bgp_networks'])) return false;
        if (!is_array($data['networks'])) return false;
        foreach ($data['networks'] as $network) {
            if (!is_array($network)) return false;
            if (!isset(
                $network['interface'],
                $network['ip_address'],
                $network['vrf_name'],
                $network['vrf_rd'],
                $network['vni'],
                $network['description'],
                $network['mac']
            )) return false;
        }
        return true;
    }

    /**
     * Validate "Error" data structure
     * {
     *   "dataSetType",
     *   "ip",
     *   "hostname",
     *   "message"
     * }
     * @param array $data
     * @return bool
     */
    private function validateError(array $data): bool
    {
        return isset(
            $data['dataSetType'],
            $data['ip'],
            $data['hostname'],
            $data['message']
        );
    }

    /**
     * Validate "Phone" data structure
     *
     * @param array $data
     * @return bool
     */
    private function validatePhone(array $data): bool
    {
        return isset(
            $data['dataSetType'],
            $data['name'],
            $data['model'],
            $data['status'],
            $data['class'],
            $data['prefix'],
            $data['phonedn'],
            $data['e164mask'],
            $data['css'],
            $data['devicepool'],
            $data['alertingname'],
            $data['partition'],
            $data['description'],
            $data['serialNumber'],
            $data['modelNumber'],
            $data['versionID'],
            $data['appLoadID'],
            $data['timezone'],
            $data['macAddress'],
            $data['ipAddress'],
            $data['subNetMask'],
            $data['vlanId'],
            $data['dhcpEnabled'],
            $data['dhcpServer'],
            $data['domainName'],
            $data['tftpServer1'],
            $data['tftpServer2'],
            $data['defaultRouter'],
            $data['dnsServer1'],
            $data['dnsServer2'],
            $data['callManager1'],
            $data['callManager2'],
            $data['callManager3'],
            $data['callManager4'],
            $data['userLocale'],
            $data['cdpNeighborDeviceId'],
            $data['cdpNeighborIP'],
            $data['cdpNeighborPort'],
            $data['publisherIp']
        );
    }
}
