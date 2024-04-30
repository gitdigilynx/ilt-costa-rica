<?php

namespace App\Wicrew\CoreBundle\Service\Exporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

/**
 * XlsExporter
 */
class XlsExporter extends ExporterAbstract {

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

        // Create your Office 2003 Excel (XLS Format)
        $writer = new Xls($spreadsheet);
        $writer->save($this->getDestination());

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

}
