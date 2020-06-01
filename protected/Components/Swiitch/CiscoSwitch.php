<?php
namespace App\Components\Swiitch;

use App\Components\Ssh\SshClient;
use App\Models\Appliance;
use App\Models\ApplianceType;
use function T4\app;

class CiscoSwitch
{
    private $appliance;
    private $sshClient;

    public function __construct(Appliance $appliance)
    {
        $this->appliance = $appliance;
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
                $CDP_PHONE_PORT_PATTERN = 'Port.*';
                $fields = mb_split(
                    ' ',
                    mb_ereg_replace(' +', ' ', $phoneNeighbor)
                );
                $phone = [];
                $phone['sep'] = trim($fields[0]);
                $phone['sw_port'] = trim($fields[1]) . ' ' . trim($fields[2]);
                $phone['ph_port'] =
                    mb_eregi($CDP_PHONE_PORT_PATTERN, $phoneNeighbor, $regs)
                        ? (mb_eregi("\d+", $regs[0], $ph_port) ? $ph_port[0] : '')
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
     * @return string
     * @throws \Exception
     */
    public function hostname(): string
    {
        return $this->appliance()->hostname();
    }

    /**
     * @return Appliance
     * @throws \Exception
     */
    public function appliance(): Appliance
    {
        if ($this->appliance->type->type != ApplianceType::SWITCH) {
            throw new \Exception('Appliance (id=' . $this->appliance->getPk() . ') is not a switch');
        }
        return $this->appliance;
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
