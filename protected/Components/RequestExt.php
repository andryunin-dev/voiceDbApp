<?php

namespace App\Components;

use T4\Core\QueryString;
use T4\Core\Std;
use T4\Core\Url;
use T4\Http\Request;

class RequestExt extends Request
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Clone current Request Object, add get parameter and return this clone
     * @param array $newGetParams
     * @return RequestExt $this
     */
    public function addGetParam($newGetParams)
    {
        $res = new RequestExt();
        $res->url->query = $res->url->query ?? new QueryString();
        $res->url->query = new QueryString(array_merge($res->url->query->toArrayRecursive(), $newGetParams)) ;
        $res->get = new Std(array_merge($res->get->toArrayRecursive(), $newGetParams));
        return $res;
    }

    protected function getUrlRel()
    {
        $url = new Url($this->url->to);
        $url->protocol = '';
        $url->host = '';
        $url->port = '';
        return $url;
    }
}