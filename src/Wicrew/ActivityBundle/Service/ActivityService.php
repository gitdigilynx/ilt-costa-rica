<?php

namespace App\Wicrew\ActivityBundle\Service;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\CoreBundle\Entity\IBasePriceEntity;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\SaleBundle\Service\Summary\ActivitySummary;
use DateTime;

/**
 * Activity
 */
class ActivityService {

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
     * @return ActivityService
     */
    public function setUtils(Utils $utils): ActivityService {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Calculate product price
     *
     * @param Activity $activity
     * @param int $adultCount
     * @param int $childCount
     * @param array $areaFromInputInfo
     * @param array $areaToInputInfo
     * @param DateTime|null $pickupDate
     * @param DateTime|null $tourTime
     * @param int $activityType
     * @param array|null $custom_services
     *
     * @return ActivitySummary
     */
    public function getPriceSummary(Activity $activity, int $adultCount, int $childCount, array $areaFromInputInfo, array $areaToInputInfo, ?DateTime $pickupDate = null, ?DateTime $tourTime = null, int $activityType, ?array $custom_services = null, ?string $childrenAges = null): ActivitySummary {
        $em = $this->getUtils()->getEntityManager();
        $translator = $this->getUtils()->getTranslator();
        $summary = new ActivitySummary($activity, $em, $translator, $activityType, $adultCount, $childCount, $areaFromInputInfo, $areaToInputInfo, $pickupDate, $tourTime, $custom_services, $childrenAges);
        if ($activity->getSlides()->count() > 0) {
            $uploadHelper = $this->getUtils()->getContainer()->get('vich_uploader.templating.helper.uploader_helper');
            $summary->setImage($uploadHelper->asset($activity->getSlides()->first(), 'imageFile'));
        }

        return $summary;
    }

    /**
     * Get activity price detail for 1 adult and 1 child.
     *
     * @param Activity $activity
     *
     * @return Money[]
     */
    public function getActivityFeeForAdultAndChild(Activity $activity): array {
        $adultRack = new Money();
        $childRack = new Money();

        if ($activity->isCombo()) {

            $adultRack = $activity->getAdultRackPrice();
            $childRack = $activity->getChildRackPrice();

        } else if ($activity->isTransportationRequired()) {

            $adultRack = new Money($activity->getTransportAdultRackPrice());
            $childRack = new Money($activity->getTransportKidRackPrice());

        } else {

            if ($activity->getPriceType() === IBasePriceEntity::PRICE_TYPE_PER_PERSON) {
                $adultRack = new Money($activity->getAdultRackPrice());
                $childRack = new Money($activity->getChildRackPrice());
            } else if ($activity->getPriceType() === IBasePriceEntity::PRICE_TYPE_FOR_THE_TRIP) {
                $adultRack = $adultRack->addStr($activity->getFixedRackPrice());
            }
        }


        if ( in_array("1", $activity->getTypes()) ) {
            
            $groupAdultRack = new Money($activity->getGroupAdultRackPrice());
            $groupChildRack = new Money($activity->getGroupKidRackPrice());
            return ['adultRack' => $adultRack, 'childRack' => $childRack, 'priceType' => $activity->getTypes(), 'groupAdultRack' => $groupAdultRack, 'groupChildRack' => $groupChildRack];
            
        }else{

            return [
                'adultRack' => $adultRack, 
                'childRack' => $childRack,
                'priceType' => $activity->getTypes()
            ];
        }
    }

    /**
     * Get Activity by id
     *
     * @param int $id
     *
     * @return Activity
     */
    public function loadActivity($id) {
        $em = $this->getUtils()->getEntityManager();
        $activvityQB = $em->getRepository('\App\Wicrew\ActivityBundle\Entity\Activity')->createQueryBuilder('act')->select('act');
        $activvityQB->where('act.id = (:id) AND act.status = (:sta)');
        $activvityQB->setParameters(['id' => $id, 'sta' => 1]);
        $activityFound = $activvityQB->getQuery()->getSingleResult();

        return $activityFound;
    }
}
