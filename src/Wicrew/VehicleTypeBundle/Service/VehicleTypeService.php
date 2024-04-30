<?php

namespace App\Wicrew\VehicleTypeBundle\Service;

use App\Wicrew\CoreBundle\Service\Utils;

/**
 * Product
 */
class VehicleTypeService {

    /**
     * utils
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
     * @return Summary
     */
    public function setUtils(Utils $utils): CustomerService {
        $this->utils = $utils;
        return $this;
    }
}
