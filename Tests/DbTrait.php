<?php

trait DbTrait
{
    protected static $app;

    protected static function setDefaultDb($dbName)
    {
        try {
            self::$app = \T4\Console\Application
                ::instance()
                ->setConfig(new \T4\Core\Config(ROOT_PATH_PROTECTED . '/config.php'));
            if (!isset(self::$app->db->$dbName)) {
                throw new \T4\Core\Exception('Ошибка инициализации БД');
            }
            self::$app->db->default = self::$app->db->$dbName;
        } catch (\Exception $e) {
            throw new \PHPUnit\Framework\Exception($e->getMessage());
        }
    }

    protected static function truncateTables($schema)
    {
        /**
         * @var \T4\Dbal\Connection $connection
         */
        $connection = self::$app->db->default;
        $tables = $connection->query('SELECT table_name FROM information_schema.tables WHERE table_schema = :schemaName', ['schemaName' => $schema])->fetchAll(PDO::FETCH_ASSOC);

        $tables = array_map(function ($item) {return array_pop($item);}, $tables);
        foreach ($tables as $table) {
            if (0 == $connection->query('SELECT COUNT(1) FROM ' . $schema . '."' . $table . '"')->fetchScalar()) {
//                echo $schema . '.' . $table . ' empty' . "\n";
                continue;
            }

            echo 'truncate ' . $table . ' table... ';
            if (true !== ($connection->execute('TRUNCATE ' . $schema . '."' . $table . '" CASCADE'))) {
                throw new \T4\Core\Exception('Ошибка при очистке таблицы ' . $table . '.' . $schema);
            }
            echo 'Done!' . "\n";
        }
    }
}