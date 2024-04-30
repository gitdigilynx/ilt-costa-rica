<?php

namespace App\Wicrew\SystemConfigurationBundle\Service;

use App\Wicrew\CoreBundle\Service\Utils;

/**
 * SystemConfigurationService
 */
class SystemConfigurationService {

    /**
     * Utils
     *
     * @var Utils
     */
    protected $utils;

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
     * @return OrderService
     */
    public function setUtils(Utils $utils): SystemConfigurationService {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get configuration value from full path
     *
     * @param type $key
     *
     * @return string
     */
    public function getSystemConfigValue($key) {
        return $this->getUtils()->getSystemConfigValue($key);
    }

    /**
     * Get configuration values by group path
     *
     * @param string $groupPath
     * @param bool $asDimension
     *
     * @return array
     */
    public function getSystemConfigValues($groupPath, $asDimension = false) {
        return $this->getUtils()->getSystemConfigValues($groupPath, $asDimension);
    }
}
