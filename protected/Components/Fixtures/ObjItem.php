<?php

namespace App\Components\Fixtures;

use T4\Core\Collection;
use T4\Core\Std;

/**
 * Class ObjItem
 * @package App\Components\Fixtures
 * @property string $id
 * @property string $ip
 * @property bool $isExpanded
 * @property string $title
 * @property string $text
 * @property Std $children
 */
class ObjItem extends Std
{
    public function __construct($data = null)
    {
        parent::__construct($data);
        $this->id = '';
        $this->ip = '';
        $this->isExpanded = false;
        $this->title = 'Hello my new world';
        $this->text = 'Culpa dolor deserunt veniam irure amet officia';
        $this->children = new Collection();
    }
}