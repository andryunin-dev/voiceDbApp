<?php

namespace App\Components;

use T4\Core\QueryString;
use T4\Core\Url;

/**
 * Class UrlExt
 * @package App\Components
 *
 */
class UrlExt extends Url
{
    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    public function cloneToShortUrl()
    {
        $clone = clone $this;
        $clone->protocol = '';
        $clone->host = '';
        $clone->port = '';
        return $clone;
    }
    public function clone()
    {
        return clone $this;
    }

    public function addQuery($data)
    {
        if (!is_array($data)) {
            return $this;
        }
        if (null === $this->query) {
            $query = $data;
        } else {
            $query = $this->query->toArrayRecursive();
        }
        $query = array_merge($query, $data);
        $this->query = new QueryString($query);
        return $this;
    }
    public function replaceQuery($data)
    {
        if (!is_array($data)) {
            return $this;
        }
        $this->query = new QueryString($data);
        return $this;
    }
}