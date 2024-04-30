<?php

namespace App\Wicrew\CoreBundle\Service;

use App\Wicrew\CoreBundle\Service\Exporter\CsvExporter;
use App\Wicrew\CoreBundle\Service\Exporter\ExporterAbstract;
use App\Wicrew\CoreBundle\Service\Exporter\XlsExporter;
use App\Wicrew\CoreBundle\Service\Exporter\XlsxCustomExporter;
use App\Wicrew\CoreBundle\Service\Exporter\XlsxExporter;
use App\Wicrew\CoreBundle\Service\Exporter\XlsxPassengersExporter;

/**
 * Writer
 */
class Writer {

    /**
     * Types
     */
    const TYPE_CSV = 'csv';
    const TYPE_XLS = 'xls';
    const TYPE_XLSX = 'xlsx';
    const TYPE_XLSX_CUSTOM = 'xlsx_custom';
    const TYPE_XLSX_PASSENGERS = 'xlsx_passengers';
    const TYPE_ODS = 'ods';

    /**
     * Utils
     *
     * @var Utils
     */
    private $utils;

    /**
     * Type
     *
     * @var string
     */
    private $type = self::TYPE_CSV;

    /**
     * Data
     *
     * @var array
     */
    private $data = [];

    /**
     * Destination
     *
     * @var string
     */
    private $destination = '';

    /**
     * Options
     *
     * @var array
     */
    private $options = [];

    /**
     * ExporterAbstract
     *
     * @var ExporterInterface
     */
    private $customExporter;

    /**
     * Constructor
     *
     * @param Utils $utils
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);
    }

    /**
     * Get utils
     *
     * @return Utils
     */
    public function getUtils(): Utils {
        return $this->utils;
    }

    /**
     * Set utils
     *
     * @param Utils $utils
     *
     * @return Writer
     */
    public function setUtils(Utils $utils): Writer {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Writer
     */
    public function setType($type): Writer {
        $this->type = $type;
        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return Writer
     */
    public function setData(array $data): Writer {
        $this->data = $data;
        return $this;
    }

    /**
     * Get destination
     *
     * @return string
     */
    public function getDestination() {
        return $this->destination;
    }

    /**
     * Set destination
     *
     * @param string $destination
     *
     * @return Writer
     */
    public function setDestination($destination): Writer {
        $this->destination = $destination;
        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Set options
     *
     * @param array $options
     *
     * @return Writer
     */
    public function setOptions($options): Writer {
        $this->options = $options;
        return $this;
    }

    /**
     * Get custom exporter
     *
     * @return null|ExporterAbstract
     */
    public function getCustomExporter(): ?ExporterAbstract {
        return $this->customExporter;
    }

    /**
     * Set custom exporter
     *
     * @param null|ExporterAbstract $customExporter
     *
     * @return Export
     */
    public function setCustomExporter(?ExporterAbstract $customExporter): Export {
        $this->customExporter = $customExporter;
        return $this;
    }

    /**
     * Export
     *
     * @param array $data
     * @param string $destination
     * @param string $type
     *
     * @return bool
     */
    public function export(array $data, $destination = '', $type = '') {
        if ($data) {
            $this->setData($data);
        }

        if ($destination) {
            $this->setDestination($destination);
        }

        if ($type) {
            $this->setType($type);
        }

        $result = [
            'status' => 'failed',
            'message' => '',
        ];

        if ($this->getCustomExporter()) {
            $result = $this->getCustomExporter()->export($data, $destination);
        } else {
            $exporter = null;
            switch ($this->getType()) {
                case self::TYPE_CSV:
                    $exporter = new CsvExporter($this->getData(), $this->getDestination());
                    break;
                case self::TYPE_XLS:
                    $exporter = new XlsExporter($this->getData(), $this->getDestination());
                    break;
                case self::TYPE_XLSX:
                    $exporter = new XlsxExporter($this->getData(), $this->getDestination());
                    break;
                case self::TYPE_XLSX_CUSTOM:
                    $exporter = new XlsxCustomExporter($this->getData(), $this->getDestination());
                    break;
                case self::TYPE_XLSX_PASSENGERS:
                    $exporter = new XlsxPassengersExporter($this->getData(), $this->getDestination());
                    break;
            }

            if ($exporter) {
                $result = $exporter->export();
            }
        }

        return $result;
    }

}