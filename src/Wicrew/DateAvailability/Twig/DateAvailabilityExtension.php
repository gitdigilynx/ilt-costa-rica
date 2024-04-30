<?php

namespace App\Wicrew\DateAvailability\Twig;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderHistory;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use App\Wicrew\SaleBundle\Service\OrderService;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use App\Wicrew\DateAvailability\Entity\DateAvailability;
use Twig\Extension\AbstractExtension;

class DateAvailabilityExtension extends AbstractExtension
{

    /**
     * Core utility class
     *
     * @var Utils
     */
    private $utils;

    public function __construct(Utils $utils)
    {
        $this->setUtils($utils);
    }

    /**
     * Get utils
     *
     * @return Utils
     */
    public function getUtils(): Utils
    {
        return $this->utils;
    }

    /**
     * Set utils
     *
     * @param Utils $utils
     *
     * @return DateAvailabilityExtension
     */
    public function setUtils(Utils $utils): DateAvailabilityExtension
    {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [];
    }
}
