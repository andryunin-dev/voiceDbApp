<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 12.09.2017
 * Time: 9:03
 */

namespace App\Components;


use T4\Core\Std;

/**
 * Class Paginator
 * @package App\Components
 *
 * @property int $page
 * @property int $pages
 * @property int $records
 * @property int $rowsOnPage
 */
class Paginator extends Std
{
    protected static $template = [
        'rowsOnPage' => -1,
        'page' => 1,
        'pages' => 1,
        'records' => 0
    ];

    public function __construct($sourceData = null)
    {
        $data = self::$template;

        if ($sourceData instanceof Std) {
            $sourceData = $sourceData->toArrayRecursive();
        } elseif (! is_array($sourceData)) {
            $sourceData = [];
        }
        $data = array_merge($data, $sourceData);
        parent::__construct($data);
    }
    public function update()
    {
        $this->pages = $this->rowsOnPage <= 0 ? 1 : ceil($this->records / $this->rowsOnPage);
        $this->page = $this->pages >= $this->page ? $this->page : 1;
    }

    protected function sanitizeRecords($value)
    {
        return intval($value);
    }
    protected function sanitizeRowsOnPage($value)
    {
        return is_numeric($value) ? intval($value) : $value;
    }
    protected function sanitizePage($value)
    {
        return intval($value);
    }
    protected function sanitizePages($value)
    {
        return intval($value);
    }
}