<?php

namespace App\Wicrew\CoreBundle\Service\Reminder;

use App\Wicrew\CoreBundle\Service\Utils;

/**
 * ReminderAbstract
 */
abstract class ReminderAbstract {
    /**
     * Utils
     *
     * @var Utils
     */
    private $utils;

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
     * @return Lead
     */
    public function setUtils(Utils $utils): ReminderAbstract {
        $this->utils = $utils;
        return $this;
    }

}
