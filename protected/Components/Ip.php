<?php

namespace App\Components;

/**
 * Class Ip
 * @package App\Components
 *
 * only for ipV4
 * @property string $address        ip address (i.e. 192.168.1.1) without mask
 * @property string $network        network address for this IP
 * @property string $networkSize    number of IP addresses in current network (include broadcast and network address)
 * @property string $mask           mask (i.e. 255.255.255.0, 255.240.0.0 etc.)
 * @property int $masklen           length of mask (1-32)
 * @property bool $is_valid         valid or not this object
 * @property bool $is_hostIp        current address is host IP (not network address)
 */
class Ip
{
    const MAX_LEN_MASK_IPV4 = 32;

    private $__data = [];

    /**
     * Ip constructor.
     * @param string $ip        IP address with or without mask in CIDR notation (10.10.0.0/24, 10.11.12.13 etc.)
     * @param int|string $mask  mask length (integer or numeric string) or mask like 255.255.0.0 etc. Ignore if $ip argument have a mask
     */
    public function __construct(string $ip = null, $mask = null)
    {
        if (! isset($ip)) {
            $this->innerSet('is_valid', false);
        }
        $ip2array = explode('/', $ip);
        if (1 == count($ip2array)) {
            $this->innerSet('address', filter_var(array_pop($ip2array), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
            if (empty($mask)) {
                $this->innerSet('masklen', self::MAX_LEN_MASK_IPV4);
                $this->innerSet('mask', self::cidr2mask($this->innerGet('masklen')));
            } elseif (is_numeric($mask) && $mask > 0 && $mask <= self::MAX_LEN_MASK_IPV4 ) {
                $this->innerSet('masklen', (int)$mask);
                $this->innerSet('mask', self::cidr2mask($this->innerGet('masklen')));
            } elseif (is_string($mask)) {
                $this->innerSet('mask', filter_var(array_pop($ip2array), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
                $this->innerSet('masklen', self::mask2cidr($this->innerGet('mask')));
            } else {
                $this->innerSet('mask', false);
                $this->innerSet('masklen', false);
            }
        } elseif (2 == count($ip2array)) {
            $this->innerSet('masklen', (is_numeric($mask = array_pop($ip2array))) ? (int)$mask : false);
            $this->innerSet('mask', self::cidr2mask($this->innerGet('masklen')));
            $this->innerSet('address', filter_var(array_pop($ip2array), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        } else {
            $this->innerSet('is_valid', false);
        }
        if (
            false === $this->innerGet('address') ||
            false === $this->innerGet('mask') ||
            false === $this->innerGet('masklen')
        ) {
            $this->innerSet('is_valid', false);
        } else {
            $this->innerSet('is_valid', true);

            $this->innerSet('network', long2ip(ip2long($this->innerGet('address')) & ip2long($this->innerGet('mask'))));
            $this->innerSet('networkSize', 1 << self::MAX_LEN_MASK_IPV4 - $this->innerGet('masklen'));
            $this->innerSet('broadcast', long2ip(ip2long($this->innerGet('network')) + $this->innerGet('networkSize') - 1));
            $this->innerSet('is_hostIp', $this->innerGet('address') != $this->innerGet('network'));
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
        if (empty($mask) && false === filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
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

    public function __set($name, $value)
    {
    }

    public function __get($name)
    {
        return ($this->innerGet('is_valid')) ? $this->innerGet($name) : false;
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
        $this->__data[$key] = $val;
    }

    protected function innerGet($key)
    {
        return $this->__data[$key];
    }
}