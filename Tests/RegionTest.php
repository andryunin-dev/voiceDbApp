<?php

require __DIR__ . '/../protected/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../protected/boot.php';

class RegionTest extends \PHPUnit\Framework\TestCase
{
    public function testInit()
    {
        $config = new \T4\Core\Config(__DIR__ . '/../protected/config.php');
        $app = \T4\Console\Application::instance();
        $app->setConfig($config);
        $conn = $app->db->phpUnitTest;

        \T4\Orm\Model::setConnection($conn);
        $this->assertInstanceOf('\T4\Dbal\Connection', \T4\Orm\Model::getDbConnection());
    }

    /**
     * @depends testInit
     */
    public function testGeolocation()
    {
        $region = (new \App\Models\Region())
            ->fill([
                'title' => 'title region'
            ])
            ->save();
        $fromDb = \App\Models\Region::findByPK($region->getPk());
        $this->assertEquals($region->title, $fromDb->title);
    }
}