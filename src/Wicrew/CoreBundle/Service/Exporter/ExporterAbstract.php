<?php

namespace App\Wicrew\CoreBundle\Service\Exporter;

/**
 * ExporterAbstract
 */
abstract class ExporterAbstract implements ExporterInterface {

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
    private $destination = 'php://output';

    /**
     * Options
     *
     * @var array
     */
    private $options = [];

    /**
     * Constructor
     *
     * @param array $data
     * @param string $destination
     * @param array $options
     */
    public function __construct(array $data, $destination = '', $options = []) {
        $this->setData($data);
        $this->setDestination($destination);
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
     * @return ExporterAbstract
     */
    public function setData(array $data): ExporterAbstract {
        $this->data = $data;
        return $this;
    }

    /**
     * Get destination
     *
     * @return string
     */
    public function getDestination() {
        if ($this->destination) {
            return $this->destination;
        } else {
            return 'php://output';
        }
    }

    /**
     * Set destination
     *
     * @param string $destination
     *
     * @return ExporterAbstract
     */
    public function setDestination($destination): ExporterAbstract {
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
     * @return ExporterAbstract
     */
    public function setOptions($options): ExporterAbstract {
        $this->options = $options;
        return $this;
    }

    /**
     * Export
     *
     * @param array $data
     * @param string $destination
     * @param array $options
     *
     * @return array
     */
    public function export(array $data = [], $destination = '', $options = []): array {
        if ($data) {
            $this->setData($data);
        }

        if ($destination) {
            $this->setDestination($destination);
        }

        if ($options) {
            $this->setOptions($options);
        }

        return $this->excecute();
    }

}
