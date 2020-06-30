<?php
namespace App\Components\Cucm;

use App\Models\Appliance;
use App\Models\ApplianceType;

class CucmsService
{
    /**
     * @return array of Cucm
     */
    public function cucms(): array
    {
        $cucms = [];
        $appliances = Appliance::findAllByType(ApplianceType::CUCM_PUBLISHER)->toArray();
        array_walk(
            $appliances,
            function ($appliance) use (&$cucms) {
                if (false !== $appliance->managementIp) {
                    $cucms[] = new Cucm($appliance);
                }
            }
        );
        return $cucms;
    }

    /**
     * Cucm with Ip
     * @param string $ip
     * @return Cucm|false
     */
    public function cucmWithIp(string $ip)
    {
        $filterResult = array_filter(
            $this->cucms(),
            function ($cucm) use ($ip) {
                return $ip == $cucm->ip();
            }
        );
        return reset($filterResult);
    }
}
