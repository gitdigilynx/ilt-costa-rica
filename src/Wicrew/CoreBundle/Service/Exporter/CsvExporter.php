<?php

namespace App\Wicrew\CoreBundle\Service\Exporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

/**
 * CsvExporter
 */
class CsvExporter extends ExporterAbstract {

    /**
     * Delimiter
     *
     * @var string
     */
    private $delimiter = ',';

    /**
     * Enclosure
     *
     * @var string
     */
    private $enclosure = '"';

    /**
     * Line ending
     *
     * @var string
     */
    private $lineEnding = "\r\n";

    /**
     * Get delimiter
     *
     * @return string
     */
    public function getDelimiter() {
        return $this->delimiter;
    }

    /**
     * Set delimiter
     *
     * @param string $delimiter
     *
     * @return CsvExporter
     */
    public function setDelimiter($delimiter): CsvExporter {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Get enclosure
     *
     * @return string
     */
    public function getEnclosure() {
        return $this->enclosure;
    }

    /**
     * Set enclosure
     *
     * @param string $enclosure
     *
     * @return CsvExporter
     */
    public function setEnclosure($enclosure): CsvExporter {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * Get line ending
     *
     * @return string
     */
    public function getLineEnding() {
        return $this->lineEnding;
    }

    /**
     * Set line ending
     *
     * @param string $lineEnding
     *
     * @return CsvExporter
     */
    public function setLineEnding($lineEnding): CsvExporter {
        $this->lineEnding = $lineEnding;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function excecute(): array {
        $options = $this->getOptions();
        if (isset($options['delimiter'])) {
            $this->setDelimiter($options['delimiter']);
        }
        if (isset($options['enclosure'])) {
            $this->setEnclosure($options['enclosure']);
        }
        if (isset($options['lineEnding'])) {
            $this->setLineEnding($options['lineEnding']);
        }

        $status = 'failed';
        $message = '';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($this->getData() as $rowIdx => $row) {
            foreach ($row as $colIdx => $value) {
                $sheet->setCellValueByColumnAndRow(($colIdx + 1), ($rowIdx + 1), $value);
            }
        }

        $writer = new Csv($spreadsheet);
        $writer->setUseBOM(false);
        $writer->setDelimiter($this->getDelimiter());
        $writer->setEnclosure($this->getEnclosure());
        $writer->setLineEnding($this->getLineEnding());
        $writer->setSheetIndex(0);
        $writer->save($this->getDestination());

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

}
