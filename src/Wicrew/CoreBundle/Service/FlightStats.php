<?php

namespace App\Wicrew\CoreBundle\Service;

/**
 * FlightStats
 */
class FlightStats extends Client {

    /**
     * Container interface
     *
     * @var Utils
     */
    protected $utils;

    /**
     * Application ID
     *
     * @var string
     */
    protected $applicationId;

    /**
     * Application key
     *
     * @var string
     */
    protected $applicationKey;

    /**
     * Constructor
     *
     * @param Utils $container
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);

        $params = $this->getUtils()->getContainer()->getParameter('api.flight_stats');
        if (is_array($params) && $params) {
            if (isset($params['application_id']) && $params['application_id']) {
                $this->setApplicationId($params['application_id']);
            }

            if (isset($params['application_key']) && $params['application_key']) {
                $this->setApplicationKey($params['application_key']);
            }
        }
    }

    /**
     * Get Utils
     *
     * @return Utils
     */
    public function getUtils() {
        return $this->utils;
    }

    /**
     * Set Utils
     *
     * @param Utils $utils
     *
     * @return FlightStats
     */
    public function setUtils(Utils $utils): FlightStats {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get application ID
     *
     * @return string
     */
    public function getApplicationId() {
        return $this->applicationId;
    }

    /**
     * Set application ID
     *
     * @param string $applicationId
     *
     * @return FlightStats
     */
    public function setApplicationId($applicationId): FlightStats {
        $this->applicationId = $applicationId;
        return $this;
    }

    /**
     * Get application key
     *
     * @return string
     */
    public function getApplicationKey() {
        return $this->applicationKey;
    }

    /**
     * Get application key
     *
     * @param string $applicationKey
     *
     * @return FlightStats
     */
    public function setApplicationKey($applicationKey): FlightStats {
        $this->applicationKey = $applicationKey;
        return $this;
    }

    /**
     * @todo Get information
     */
    public function getInfo() {
    }

}
