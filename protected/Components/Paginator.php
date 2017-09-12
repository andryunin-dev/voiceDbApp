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
        $data['page'] = key_exists('page', $sourceData) && is_numeric($sourceData['page']) ? intval($sourceData['page']) : 1;
        $data['rowsOnPage'] = key_exists('rowsOnPage', $sourceData) && is_numeric($sourceData['rowsOnPage']) ? intval($sourceData['rowsOnPage']) : -1;
        parent::__construct($data);
    }

    protected function setRecords($value)
    {
        if (is_numeric($value)) {
            $this->records = intval($value);
            $this->pages = (int)$this->rowsOnPage <= 0 ? 1 : ceil($this->records / $this->rowsOnPage);
        }
    }
    protected function setRowsOnPage($value)
    {
        if (is_numeric($value)) {
            $this->rowsOnPage = intval($value);
            $this->pages = (int)$this->rowsOnPage <= 0 ? 1 : ceil($this->records / $this->rowsOnPage);
        }
    }
}