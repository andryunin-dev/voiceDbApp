<?php

namespace App\Controllers;


use App\Components\Reports\ApplianceTypeReport;
use App\Components\Reports\PlatformReport;
use App\Components\Reports\SoftReport;
use App\Components\Reports\VendorReport;
use App\Components\UrlExt;
use App\Models\ApplianceType;
use App\Models\Module;
use App\Models\Software;
use App\Models\Vendor;
use T4\Http\Helpers;
use T4\Mvc\Controller;

class Report extends Controller
{
    use DebugTrait;

    public function actionDefault()
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
        $this->data->software = Software::findAll(['order' => 'title']);
        $this->data->modules = Module::findAll(['order' => 'title']);
        $this->data->applianceTypes = ApplianceType::findAll(['order' => 'type']);
        $this->data->devsUrl = new UrlExt('/admin/devices');

        $this->data->platforms = PlatformReport::findAll();
        $this->data->types = ApplianceTypeReport::findAll();
        $this->data->softs = SoftReport::findAll();
        $this->data->vendors = VendorReport::findAll();

        $this->data->settings->activeTab = (Helpers::issetCookie('netcmdb_report_tab')) ? Helpers::getCookie('netcmdb_report_tab') : 'platforms';
        $this->data->activeLink->report = true;
    }
}