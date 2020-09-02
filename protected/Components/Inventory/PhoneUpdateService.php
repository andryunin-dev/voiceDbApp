<?php
namespace App\Components\Inventory;

use App\Models\Appliance;
use App\Models\PhoneInfo;
use \Exception;

class PhoneUpdateService
{
    private $phoneInfo;

    /**
     * @param PhoneInfo $phoneInfo
     * @param array $data
     * @throws Exception
     */
    public function update(PhoneInfo $phoneInfo, array $data): void
    {
        $this->phoneInfo = $phoneInfo;
    }

    /**
     * @return Appliance
     */
    private function appliance(): Appliance
    {
        return $this->phoneInfo->phone;
    }
}
