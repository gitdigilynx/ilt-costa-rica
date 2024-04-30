<?php

namespace App\Wicrew\CoreBundle\Service\Exporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * XlsxExporter
 */
class XlsxExporter extends ExporterAbstract {

    /**
     * {@inheritdoc}
     */
    public function excecute(): array {
        $data = $this->getData();
        $destination = $this->getDestination();

        $status = 'failed';
        $message = '';

        $spreadsheet = new Spreadsheet();
        //        $sheet = $spreadsheet->getActiveSheet();
        foreach ($this->getData() as $sheetIdx => $sheetData) {
            if ($sheetIdx == 0) {
                $sheet = $spreadsheet->getSheet($spreadsheet->getFirstSheetIndex());
            } else {
                $sheet = $spreadsheet->createSheet($sheetIdx);
            }
            $sheet->setTitle('Sheet' . ($sheetIdx + 1));
            foreach ($sheetData as $rowIdx => $rowData) {
                foreach ($rowData as $colIdx => $value) {
                    $sheet->setCellValueByColumnAndRow(($colIdx + 1), ($rowIdx + 1), $value);
                }
            }
        }

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
