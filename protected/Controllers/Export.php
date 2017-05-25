<?php

namespace App\Controllers;

use App\Models\Appliance;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use T4\Mvc\Controller;

class Export extends Controller
{
    public function actionExcel()
    {
        $spreadsheet = new Spreadsheet();

        // Set default font
        $spreadsheet->getDefaultStyle()
            ->getFont()
            ->setName('Arial')
            ->setSize(10);

        // Add header
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Регион')
            ->setCellValue('B1', 'Офис')
            ->setCellValue('C1', 'Hostname')
            ->setCellValue('D1', 'Type')
            ->setCellValue('E1', 'Device')
            ->setCellValue('F1', 'Device ser.')
            ->setCellValue('G1', 'Software')
            ->setCellValue('H1', 'Software ver.')
            ->setCellValue('I1', 'Module')
            ->setCellValue('J1', 'Module ser.')
            ->setCellValue('K1', 'Comment')
            ->setCellValue('L1', 'In use');

//        $appliances = Appliance::findAll();
        $appliance = Appliance::findByPK('18096');
//        var_dump($appliance->modules->first());die;

//        appliance.modules.first.module.title
//        appliance.software.version

        $n = 2;
        foreach ($appliance->modules as $module) {
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $n, $appliance->location->address->city->region->title)
                ->setCellValue('B' . $n, $appliance->location->title)
                ->setCellValue('C' . $n, $appliance->details->hostname)
                ->setCellValue('D' . $n, $appliance->type)
                ->setCellValue('E' . $n, $appliance->platform->platform->vendor->title . ' ' .$appliance->platform->platform->title)
                ->setCellValue('F' . $n, $appliance->platform->serialNumber)

                ->setCellValue('G' . $n, $module->module->title)
                ->setCellValue('H' . $n, $module->serialNumber)

                ->setCellValue('I' . $n, $appliance->software->software->title)
                ->setCellValue('J' . $n, $appliance->software->version)

                ->setCellValue('K' . $n, $module->comment)
                ->setCellValue('L' . $n, $module->inUse);

            $n++;
        }


        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Appliances');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Appliances '. gmdate('d M Y') . '.xlsx"');
        header('Cache-Control: max-age=0');

        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Excel2007');
        $writer->save('php://output');
        exit;
    }
}
