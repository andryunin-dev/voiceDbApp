<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 31.07.2017
 * Time: 17:59
 */

namespace App\Components;


class Cookies
{
    protected static function getUniversalDomainName($domain)
    {
        if (false !== strpos($domain, '.')) {
            return preg_replace('~^(www\.|)(.*)$~', '.$2', $domain);
        } else {
            // @fix Chrome security policy bug
            return '';
        }
    }

    public static function setCookie($name, $value, $expire = 0, $allSubDomains = true)
    {
        $domain = \T4\Mvc\Application::instance()->request->host;
        if ($allSubDomains)
            $domain = self::getUniversalDomainName($domain);
        setcookie($name, $value, $expire, '/', $domain, false, false);
        if ($expire > time()) {
            $_COOKIE[$name] = $value;
        }
    }

    public static function issetCookie($name)
    {
        return isset($_COOKIE[$name]);
    }

    public static function unsetCookie($name, $allSubDomains = true)
    {
        $domain = \T4\Mvc\Application::instance()->request->host;;
        if ($allSubDomains)
            $domain = self::getUniversalDomainName($domain);
        setcookie($name, '', time() - 60 * 60 * 24 * 30, '/', $domain, false, true);
        unset($_COOKIE[$name]);
    }

    public static function getCookie($name)
    {
        return $_COOKIE[$name];
    }
}