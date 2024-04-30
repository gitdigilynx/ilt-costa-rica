<?php

namespace App\Wicrew\ActivityBundle\Twig;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ActivityBundle\Service\ActivityService;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\CoreBundle\Service\Utils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * ActivityExtension
 */
class ActivityExtension extends AbstractExtension {
    /**
     * Core utility class
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
     * @return ActivityExtension
     */
    public function setUtils(Utils $utils): ActivityExtension {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('getActivityLocations', [$this, 'getActivityLocations']),
            new TwigFunction('getActivityFeeForAdultAndChild', [$this, 'getActivityFeeForAdultAndChild']),
            new TwigFunction('loadActivity', [$this, 'loadActivity']),
        ];
    }

    /**
     * Get activty locations
     *
     * @return array
     */
    public function getActivityLocations() {
        return [];
    }

    /**
     * Get activty price detail
     *
     * @param Activity $activity
     *
     * @return Money[]
     */
    public function getActivityFeeForAdultAndChild(Activity $activity): array {
        $activityUtil = new ActivityService($this->getUtils());
        return $activityUtil->getActivityFeeForAdultAndChild($activity);
    }

    /**
     * Get Activity by id
     *
     * @param int $id
     *
     * @return Activity
     */
    public function loadActivity($id) {
        $activityUtil = new ActivityService($this->getUtils());
        $activityFound = $activityUtil->loadActivity($id);

        return $activityFound;
    }
}
