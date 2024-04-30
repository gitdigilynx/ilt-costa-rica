<?php

namespace App\Wicrew\CoreBundle\Service\Exporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * XlsxCustomExporter
 */
class XlsxCustomExporter extends ExporterAbstract {

    /**
     * {@inheritdoc}
     */
    public function excecute(): array {
        $data = $this->getData();
        $destination = $this->getDestination();

        $status = 'failed';
        $message = '';

        $spreadsheet = new Spreadsheet();

        foreach ($this->getData() as $sheetIdx => $sheetData) {
            if ($sheetIdx == 0) {
                $sheet = $spreadsheet->getSheet($spreadsheet->getFirstSheetIndex());
            } else {
                $sheet = $spreadsheet->createSheet($sheetIdx);
            }
            $sheet->setTitle('Sheet' . ($sheetIdx + 1));
            foreach ($sheetData as $rowIdx => $rowData) {
                foreach ($rowData as $colIdx => $value) {
                    if ($value == 'logo_tag') {
                        $objDrawing = new Drawing();    //create object for Worksheet drawing
                        $objDrawing->setName('Logo');        //set name to image
                        $objDrawing->setDescription('Logo'); //set description to image
                        $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
                        $signature = $rootDir . '/export/passenger/logo.jpg';    //Path to signature .jpg file
                        $objDrawing->setPath($signature);
                        $objDrawing->setOffsetX(25);                       //setOffsetX works properly
                        $objDrawing->setOffsetY(10);                       //setOffsetY works properly
                        $objDrawing->setCoordinates('A1');        //set image to cell
                        $objDrawing->setWidth(100);                 //set width, height
                        $objDrawing->setHeight(100);

                        $objDrawing->setWorksheet($sheet);  //save

                        $sheet->getColumnDimensionByColumn($colIdx + 1)->setWidth(30);
                        $sheet->calculateColumnWidths();
                        $sheet->getRowDimension(1)->setRowHeight(100);


                        $sheet->getStyle('B1')->getAlignment()->applyFromArray(
                            [
                                'horizontal' => 'center',
                                'vertical' => 'center',
                                'wrapText' => true
                            ]
                        );

                        $sheet->getStyle('B1')->getFont()->setBold(true);
                    } else {
                        //                        $sheet->getStyleByColumnAndRow(($colIdx + 1), ($rowIdx + 1))->getFont()->setName('Verdana');
                        $sheet->getColumnDimensionByColumn($colIdx + 1)->setWidth(20);
                        $sheet->setCellValueByColumnAndRow(($colIdx + 1), ($rowIdx + 1), $value);

                        if (strpos($value, 'Note #') !== false) {
                            $sheet->getStyleByColumnAndRow(($colIdx + 1), ($rowIdx + 1))->getAlignment()->applyFromArray(
                                [
                                    'wrapText' => true
                                ]
                            );
                        }

                        if (strpos($value, 'Order ID#') !== false) {
                            $sheet->getStyleByColumnAndRow(($colIdx + 1), ($rowIdx + 1))->getFont()->setBold(true);
                        }
                    }
                }
            }
        }
        $sheet->getColumnDimensionByColumn(1)->setWidth(30);
        $sheet->getColumnDimensionByColumn(2)->setWidth(40);
        $sheet->getStyleByColumnAndRow(1, 1, 50, 1)->getFont()->setBold(true);
        $sheet->getStyleByColumnAndRow(1, 2, 50, 2)->getFont()->setBold(true);
        $sheet->getStyleByColumnAndRow(1, 1);
        $sheet->calculateColumnWidths();


        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setOffice2003Compatibility(true);
        $writer->save($this->getDestination());

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

}
