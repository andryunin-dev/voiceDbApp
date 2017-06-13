<?php

namespace App\Components;

/**
 * Class IpTool
 * @package App\Components
 *
 * only for ipV4
 * @property string $address        ip address (i.e. 192.168.1.1) without mask
 * @property string $cidrAddress    ip address in CIDR notation (i.e. 192.168.1.1/24)
 * @property string $network        network address for this IP
 * @property string $broadcast      broadcast address for this network
 * @property integer $networkSize    number of IP addresses in current network (include broadcast and network address)
 * @property string $cidrNetwork    network address for this IP with mask in CIDR notation (i.e 192.168.1.0/24)
 * @property string $mask           mask (i.e. 255.255.255.0, 255.240.0.0 etc.)
 * @property int $masklen           length of mask (1-32)
 * @property bool $is_valid         valid or not this object
 * @property bool $is_ipValid         valid or not ip address
 * @property bool $is_maskValid         valid or not ip address
 *
 * if masklen == 32 then $is_hostIp == true AND $is_networkIp == true
 * @property bool $is_hostIp        current address is host IP
 * @property bool $is_networkIp     current address is network IP
 * @property array $errors          errors
 */
class IpTools
{
    const MAX_LEN_MASK_IPV4 = 32;

    private $__data = [];

    /**
     * Ip constructor.
     * mask can pass explicitly in ip addresss with CIDR notation or in mask argument.
     * if mask pass in ip address argument value of mask argument ignore.
     * null mask - is valid value for IP
     * is_valid == true if ip address valid && 0 < masklen <= MAX_LEN_MASK_IPV4
     * @param string $ip        IP address with or without mask in CIDR notation (10.10.0.0/24, 10.11.12.13 etc.)
     * @param int|string $mask  mask length (integer or numeric string) or mask like 255.255.0.0 etc.
     */
    public function __construct(string $ip, $mask = null)
    {
        $this->innerSet('is_valid', true);

        $ip = str_replace('\\', '/', trim($ip)); // санитация
        if (empty($ip)) {
            $this->innerErrorAdd('IP адрес не задан');
            $this->innerSet('is_ipValid', false);
            $this->innerSet('is_maskValid', false);
            return;
        }
        $ip2array = explode('/', $ip);
        if (count($ip2array) > 2) {
            $this->innerErrorAdd('Данные введены неверно');
            $this->innerSet('is_ipValid', false);
            $this->innerSet('is_maskValid', false);
            return;
        }
        if (1 == count($ip2array)) {
            //analize IP address
            $this->innerSet('address', filter_var(array_pop($ip2array), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
            if (false === $this->innerGet('address')) {
                $this->innerErrorAdd('Неверный формат IP адреса');
                $this->innerSet('is_ipValid', false);
                $this->__unset('address');
            } else {
                $this->innerSet('is_ipValid', true);
            }
            //analize mask argument
            if (is_numeric($mask) && $mask > 0 && $mask <= self::MAX_LEN_MASK_IPV4 ) {
                $this->innerSet('masklen', (int)$mask);
                $this->innerSet('is_maskValid', true);
            } elseif (is_string($mask)) {
                $this->innerSet('mask', filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
                if (false !== $this->innerGet('mask')) {
                    $this->innerSet('is_maskValid', true);
                } else {
                    $this->innerErrorAdd('Неверно задана маска подсети');
                    $this->innerSet('is_maskValid', false);
                    $this->__unset('mask');
                }
            } else {
                $this->innerErrorAdd('Неверно задана маска подсети');
                $this->innerSet('is_maskValid', false);
            }
        } else {
            //ip address have two parts(address and masklen)
            //analize mask len
            if (is_numeric($masklen = array_pop($ip2array)) && (int)$masklen > 0 && (int)$masklen <= self::MAX_LEN_MASK_IPV4) {
                $this->innerSet('is_maskValid', true);
                $this->innerSet('masklen', (int)$masklen);
            } else {
                $this->innerErrorAdd('Неверно задана маска подсети');
                $this->innerSet('is_maskValid', false);
            }
            //analize ip address
            $this->innerSet('address', filter_var(array_pop($ip2array), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
            if (false === $this->innerGet('address')) {
                $this->innerErrorAdd('Невалидный IP адрес');
                $this->innerSet('is_ipValid', false);
                $this->__unset('address');
            } else {
                $this->innerSet('is_ipValid', true);
            }
        }
        if (!$this->innerGet('is_ipValid') || !$this->innerGet('is_maskValid')) {
            $this->innerSet('is_valid', false);
        }
    }

    public static function cidr2mask($cidr)
    {
        if (is_numeric($cidr)) {
            return ($cidr > 0 && $cidr <= self::MAX_LEN_MASK_IPV4) ? long2ip(~((1 << (32 - $cidr)) - 1)) : false;
        } else {
            return false;
        }
    }

    public static function mask2cidr(string $mask)
    {
        if (empty($mask) || false === filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $cidr = 1;
        while ($cidr < self::MAX_LEN_MASK_IPV4) {
            if ($mask == long2ip(~((1 << (32 - $cidr)) - 1))) {
                return $cidr;
            }
            $cidr++;
        }
        return false;
    }

    public static function ip2subnet(string $ip)
    {
        return (new Ip($ip))->network;
    }

    /**
     * @param Ip $child testing object of Ip class
     * @return bool true if network of current object is parental for $child
     *
     */
    public function is_parent(Ip $child)
    {
        if ($this->network == $child->network) {
            return $this->masklen < $child->masklen;
        } else {
            $mixed = (new Ip(($child->network . '/' . $this->masklen)));
            return ($mixed->is_hostIp && ($mixed->network == $this->network));
        }
    }

    public function __set($name, $value)
    {
    }

    public function __get($name)
    {
        switch ($name) {
            case 'is_ipValid':
                return (isset($this->__data['is_ipValid'])) ? $this->innerGet('is_ipValid') : false;
                break;
            case 'address':
                if (true === $this->innerGet('is_ipValid')) {
                    return $this->innerGet('address');
                } else {
                    return false;
                }
                break;
            case 'is_maskValid':
                return (isset($this->__data['is_maskValid'])) ? $this->innerGet('is_maskValid') : false;
                break;
            case 'mask':
                if (true === $this->innerGet('is_maskValid')) {
                    return ($this->__isset('mask')) ?
                        $this->innerGet('mask') :
                        $this->innerSet('mask', self::cidr2mask($this->innerGet('masklen')));
                } else {
                    return false;
                }
                break;
            case 'masklen':
                if (true === $this->innerGet('is_maskValid')) {
                    return ($this->__isset('masklen')) ?
                        $this->innerGet('masklen') :
                        $this->innerSet('masklen', self::mask2cidr($this->innerGet('mask')));
                } else {
                    return false;
                }
                break;
            case 'cidrAddress':
                if ($this->innerGet('is_valid')) {
                    return ($this->__isset('cidrAddress')) ?
                        $this->innerGet('cidrAddress') :
                        $this->innerSet('cidrAddress', $this->innerGet('address') . '/' . $this->masklen);
                } else {
                    return false;
                }
                break;
            case 'network':
                if ($this->innerGet('is_valid')) {
                    return ($this->__isset('network')) ?
                        $this->innerGet('network') :
                        $this->innerSet('network', long2ip(ip2long($this->innerGet('address')) & ip2long($this->mask)));
                } else {
                    return false;
                }
                break;
            case 'networkSize':
                if ($this->innerGet('is_valid')) {
                    return ($this->__isset('networkSize')) ?
                        $this->innerGet('networkSize') :
                        $this->innerSet('networkSize', 1 << (self::MAX_LEN_MASK_IPV4 - $this->masklen));
                } else {
                    return false;
                }
                break;
            case 'cidrNetwork':
                if ($this->innerGet('is_valid')) {
                    return ($this->__isset('cidrNetwork')) ?
                        $this->innerGet('cidrNetwork') :
                        $this->innerSet('cidrNetwork', $this->network . '/' . $this->masklen);
                } else {
                    return false;
                }
                break;
            case 'broadcast':
                if ($this->innerGet('is_valid')) {
                    return ($this->__isset('broadcast')) ?
                        $this->innerGet('broadcast') :
                        $this->innerSet('broadcast', long2ip(ip2long($this->network) + $this->networkSize - 1));
                } else {
                    return false;
                }
                break;
             case 'is_hostIp':
                if ($this->innerGet('is_valid')) {
                    return ($this->__isset('is_hostIp')) ?
                        $this->innerGet('is_hostIp') :
                        $this->innerSet('is_hostIp', (32 == $this->masklen) || ($this->address != $this->network));
                } else {
                    return false;
                }
                break;
             case 'is_networkIp':
                if ($this->innerGet('is_valid')) {
                    return ($this->__isset('is_networkIp')) ?
                        $this->innerGet('is_networkIp') :
                        $this->innerSet('is_networkIp', (32 == $this->masklen) || ($this->address == $this->network));
                } else {
                    return false;
                }
                break;

            case 'errors':
                return (isset($this->__data['errors'])) ? $this->innerGet('errors') : false;
                break;
            default:
                return false;
        }
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->__data);
    }

    public function __unset($key)
    {
        unset($this->__data[$key]);
    }

    protected function innerSet($key, $val)
    {
        return $this->__data[$key] = $val;
    }

    protected function innerErrorAdd($value)
    {
        if (!isset($this->__data['errors'])) {
            $this->__data['errors'] = [];
        }
        $this->__data['errors'][] = $value;
    }

    protected function innerGet($key)
    {
        return isset($this->__data[$key]) ? $this->__data[$key] : null;
    }
}