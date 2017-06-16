<?php
namespace App\Components;

use T4\Core\Exception;
use T4\Core\Std;
use App\Models\Office;
use App\Models\Vendor;

class DSPappliance extends Std
{
    protected $dataSet;


    /**
     * DSPappliance constructor.
     * @param null $dataSet
     */
    public function __construct($dataSet)
    {
        $this->dataSet = $dataSet;
    }

//-------------------------------------------------------------------------------------------
    protected function run()
    {
        $office = $this->identifyOffice();
        $appliance = $this->identifyAppliance();
    }
//-----------------------------------------------------------------------------------------

    /**
     * @return Office
     * @throws Exception
     */
    protected function identifyOffice()
    {
        $office = Office::findByLotusId($this->dataSet->LotusId);

        if (!($office instanceof Office)) {
            throw new Exception('Location not found, LotusId = ' . $this->dataSet->LotusId);
        }

        return $office;
    }

//-----------------------------------------------------------------------------------------
    protected function identifyAppliance()
    {
        if (!empty($this->dataSet->platformSerial)){
            $vendor = $this->processVendorDataSet();
        }
    }
//--------------------------------------------------------------------------------------------

    /**
     * @return Vendor|bool
     */
    protected function processVendorDataSet()
    {
        $vendor = Vendor::findByTitle($this->dataSet->platformVendor);

        if (!($vendor instanceof Vendor)) {
            $vendor = (new Vendor())
                ->fill([
                    'title' => $this->dataSet->platformVendor
                ])
                ->save();
        }

        return $vendor;
    }

}
