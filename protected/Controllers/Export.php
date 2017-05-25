<?php

namespace App\Controllers;

use App\Models\Appliance;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use T4\Mvc\Controller;

class Export extends Controller
{
    public function actionExcellll()
    {
        $spreadsheet = new Spreadsheet();

        // 4.6.21.	Setting the default style of a workbook
//        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

         /// 4.6.28.	Setting a column’s width
        $spreadsheet->getActiveSheet()->getColumnDimension('A:L')->setAutoSize(true);
        // Default
//        $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);

        // 4.6.31.	Setting a row’s height
//        $spreadsheet->getActiveSheet()->getRowDimension('10')->setRowHeight(100);
//        $spreadsheet->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);

        // 4.6.34.	Merge/unmerge cells
//        $spreadsheet->getActiveSheet()->mergeCells('A18:E22');
//        $spreadsheet->getActiveSheet()->unmergeCells('A18:E22');

        // 4.6.38.	Add rich text to a cell
//        $objRichText = new PHPExcel_RichText();
//        $objRichText->createText('This invoice is ');
//
//        $objPayable = $objRichText->createTextRun('payable within thirty days after the end of the month');
//        $objPayable->getFont()->setBold(true);
//        $objPayable->getFont()->setItalic(true);
//        $objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_DARKGREEN ) );
//
//        $objRichText->createText(', unless specified otherwise on the invoice.');
//
//        $spreadsheet->getActiveSheet()->getCell('A18')->setValue($objRichText);

        // 4.6.18.	Formatting cells
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
//
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
//        $spreadsheet->getActiveSheet()->getStyle('A1:D4')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
//
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
//
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
//        $spreadsheet->getActiveSheet()->getStyle('B2')->getFill()->getStartColor()->setARGB('FFFF0000');
//        // cell range as a parameter
//        $spreadsheet->getActiveSheet()->getStyle('B3:B7')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');



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

    }

    /**
     *
     */
    public function actionExcel()
    {
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // HEADER
        $spreadsheet->getActiveSheet()->getStyle('A1:M1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:M1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M'];
        foreach ($columns as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }

        // Add some data
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', '№п/п')
            ->setCellValue('B1', 'Регион')
            ->setCellValue('C1', 'Офис')
            ->setCellValue('D1', 'Hostname')
            ->setCellValue('E1', 'Type')
            ->setCellValue('F1', 'Device')
            ->setCellValue('G1', 'Device ser.')
            ->setCellValue('H1', 'Software')
            ->setCellValue('I1', 'Software ver.')
            ->setCellValue('J1', 'Module')
            ->setCellValue('K1', 'Module ser.')
            ->setCellValue('L1', 'Comment')
            ->setCellValue('M1', 'In use');

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
