<?php

namespace App\Migrations;

use App\Models\Vrf;
use T4\Core\Exception;
use T4\Dbal\Query;
use T4\Orm\Migration;

class m_1493212187_insertGlobalVrf
    extends Migration
{

    public function up()
    {
        $query = (new Query())
            ->select()
            ->from(Vrf::getTableName())
            ->where('name = :name AND rd = :rd')
            ->params([':name' => Vrf::GLOBAL_VRF_NAME, ':rd' => Vrf::GLOBAL_VRF_RD]);
        if (false !== Vrf::findByQuery($query)) {
            throw new Exception('Global Vrf already exists!');
        }
        if (false !== Vrf::instanceGlobalVrf()) {
            echo 'main DB: inserted Global VRF (name =  ' . Vrf::GLOBAL_VRF_NAME . ', rd = ' . Vrf::GLOBAL_VRF_RD . ' )' . "\n";
        }
    }

    public function down()
    {
        $gvrf = (new Query())
            ->select()
            ->from(Vrf::getTableName())
            ->where('name = :name AND rd = :rd')
            ->params([':name' => Vrf::GLOBAL_VRF_NAME, ':rd' => Vrf::GLOBAL_VRF_RD]);
        $gvrf = Vrf::findByQuery($gvrf);
        if (false !== $gvrf && $gvrf->networks->count() > 0) {
            throw new Exception('Please delete all networks with Global VRF before rollback this migration');
        }

        $query = (new Query())
            ->delete(Vrf::getTableName())
            ->where('name = :name AND rd = :rd')
            ->params([':name' => Vrf::GLOBAL_VRF_NAME, ':rd' => Vrf::GLOBAL_VRF_RD]);
        if (true === $this->db->execute($query)) {
            echo 'main DB: Global VRF deleted' . "\n";
        }
    }
    
}