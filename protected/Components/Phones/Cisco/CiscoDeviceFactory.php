<?php
namespace App\Components\Phones\Cisco;

class CiscoDeviceFactory
{
    public static function model(string $model, string $ip = null)
    {
        $ciscoPhone = false;
        switch (mb_strtolower(mb_ereg_replace(' +', '', $model))) {
            case 'cisco6921':
            case 'cisco7911':
            case 'cisco7942':
            case 'cisco7945':
            case 'cisco7960':
            case 'cisco7962':
            case 'cisco7965':
            case 'cisco7975':
            case 'cisco8831':
            case 'cisco8865':
            case 'ciscoipcommunicator':
                $ciscoPhone = new CiscoPhone($ip);
                break;
            case 'ciscovgcphone':
            case 'cisco30vip':
            case 'analogphone':
            case 'ciscounifiedclientservicesframework':
            case 'ciscoata187':
            case 'cisco7936':
                $ciscoPhone = new CiscoNoWebPhone($ip);
                break;
            case 'cisco7937':
                $ciscoPhone = new CiscoPhone7937($ip);
                break;
            case 'ciscoata186':
                $ciscoPhone = new CiscoATA186($ip);
                break;
            case 'cisco8945':
                $ciscoPhone = new CiscoPhone8945($ip);
                break;
            case 'cisco7912':
                $ciscoPhone = new CiscoPhone7912($ip);
                break;
            case 'cisco7905':
                $ciscoPhone = new CiscoPhone7905($ip);
                break;
            case 'cisco7940':
                $ciscoPhone = new CiscoPhone7940($ip);
                break;
            default:
        }
        return $ciscoPhone;
    }
}
