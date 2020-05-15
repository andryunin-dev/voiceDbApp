<?php
namespace App\Components\Swiitch;

use App\Components\Ssh\SshClient;
use App\Models\Appliance;
use function T4\app;

class CiscoSwitch
{
    private $id;
    private $sshClient;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->sshClient = new SshClient(
            $this->managementIp(),
            $this->login(),
            $this->passphrase()
        );
    }

    /**
     * @return string Output the command "show cdp neighbors"
     * @throws \Exception
     */
    public function showCdpNeighbors(): string
    {
        return $this->sshClient->exec('show cdp neighbors');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function cdpPhoneNeighborsData(): array
    {
        $HEADER_OFFSET = 5;
        return array_map(
            function ($phoneNeighbor) {
                $CDP_PORT_PC_PATTERN = 'Port.*';
                $fields = mb_split(
                    ' ',
                    mb_ereg_replace(' +', ' ', $phoneNeighbor)
                );
                $phone = [];
                $phone['phone_name'] = trim($fields[0]);
                $phone['sw_port'] = trim($fields[1]) . ' ' . trim($fields[2]);
                $phone['phone_port'] =
                    mb_eregi($CDP_PORT_PC_PATTERN, $phoneNeighbor, $portPc)
                        ? trim($portPc[0])
                        : ''
                ;
                return $phone;
            },
            array_filter(
                array_slice(
                    explode(
                        "\n",
                        $this->showCdpNeighbors()
                    ), $HEADER_OFFSET
                ),
                function ($cdpNeighbor) {
                    $PHONE = 'sep';
                    return mb_ereg_match($PHONE, mb_strtolower($cdpNeighbor));
                }
            )
        );
    }

    /**
     * @return string|false management ip address
     * @throws \Exception
     */
    public function managementIp()
    {
        return $this->appliance()->getManagementIp();
    }

    /**
     * @return Appliance
     * @throws \Exception
     */
    private function appliance(): Appliance
    {
        $appliance = (new SwitchService())->switchWithPk($this->id);
        if (false == $appliance) {
            throw new \Exception("Switch (id=" . $this->id . ") has not exists");
        }
        return $appliance;
    }

    /**
     * @return string
     */
    private function login(): string
    {
        return app()->config->ssh->login;
    }

    /**
     * @return string
     */
    private function passphrase(): string
    {
        return app()->config->ssh->password;
    }
}
