<?php

namespace App\ConsolidationTablesModels;

use T4\Orm\Model;

/**
 * Class ConsolidationTable_1
 * Source for Excel consolidation table of Yushin
 * @package App\ConsolidationTablesModels
 *
 */
class ConsolidationTable_1 extends Model
{
    protected static $schema = [
        'table' => 'view.consolidation_excel_table_src',
        'columns' => []
    ];
    
    protected function beforeSave()
    {
        return false;
    }
}