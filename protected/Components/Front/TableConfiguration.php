<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 26.10.2017
 * Time: 15:31
 */

namespace App\Components\Front;


use T4\Core\Config;
use T4\Core\TStdGetSet;

class TableConfiguration
{
    use TStdGetSet;

    //путь к файлу конфигурации таблиц
    const TABLE_CONFIG_PATH = ROOT_PATH_PROTECTED . DS . 'tableConfig.php';
    /**
     * @var Config $configuration
     * конфигурация текущей таблицы
     * Если ее нет - создается новая
     */
    protected $configuration;
    /**
     * @var bool $isNew
     * true only if configuration was just created and not was saved yet.
     */
    protected $isNew;

    public function __construct(string $tableName)
    {

    }
}