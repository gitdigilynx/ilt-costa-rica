<?php

namespace App\Wicrew\SystemConfigurationBundle\Twig;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\SystemConfigurationBundle\Service\SystemConfigurationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * SystemConfigurationExtension
 */
class SystemConfigurationExtension extends AbstractExtension {

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
     * @return SystemConfigurationExtension
     */
    public function setUtils(Utils $utils): SystemConfigurationExtension {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('getSystemConfigValue', [$this, 'getSystemConfigValue']),
            new TwigFunction('getSystemConfigValues', [$this, 'getSystemConfigValues'])
        ];
    }

    /**
     * Get config value
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getSystemConfigValue($key) {
        $configUtil = new SystemConfigurationService($this->getUtils());
        $value = $configUtil->getSystemConfigValue($key);

        return $value;
    }

    /**
     * Get config group value
     *
     * @param string $groupPath
     * @param bool $asDimension
     *
     * @return array
     */
    public function getSystemConfigValues($groupPath, $asDimension = false) {
        $configUtil = new SystemConfigurationService($this->getUtils());
        $values = $configUtil->getSystemConfigValues($groupPath, $asDimension);

        return $values;
    }

}
