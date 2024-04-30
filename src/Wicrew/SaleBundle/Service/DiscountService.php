<?php

namespace App\Wicrew\SaleBundle\Service;

use App\Wicrew\CoreBundle\Controller\Controller;
use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\SaleBundle\Entity\Discount;

/**
 * DiscountService
 */
class DiscountService {

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
    public function __construct(Utils $utils = null) {
        if ($utils) {
            $this->setUtils($utils);
        }
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
     * @return DiscountService
     */
    public function setUtils(Utils $utils): DiscountService {
        $this->utils = $utils;
        return $this;
    }

    public function getDiscountValuesFromSummaries(array $summaries, array $discounts) {
        $discountValues = [];
        $totalRack = new Money();
        $totalNet = new Money();

        foreach ($summaries as $key => $summary) {
            $grandtotal = $summary->getGrandTotal();
            $totalRack = $totalRack->add($grandtotal->getRackPrice());
            $totalNet = $totalNet->add($grandtotal->getNetPrice());
        }

        foreach ($discounts as $key => $discount) {
            $discountRack = new Money($this->getDiscountValueFromAmount($totalRack, $discount));
            $discountNet = new Money($this->getDiscountValueFromAmount($totalNet, $discount));

            $discountValues[] = [
                'discount' => $discount,
                'discountRack' => $discountRack,
                'discountNet' => $discountNet,
            ];
        }

        return $discountValues;
    }

    public function getDiscountValuesFromOrderItems($orderItems, $discountItems) {
        $discountValues = [];
        $totalRack = new Money();
        $totalNet = new Money();

        foreach ($orderItems as $key => $orderItem) {
            $totalRack = $totalRack->addStr($orderItem->getGrandTotal());
            $totalNet = $totalNet->addStr($orderItem->getGrandTotalNet());
        }

        foreach ($discountItems as $key => $discount) {
            $discountRack = new Money($this->getDiscountValueFromAmount($totalRack, $discount));
            $discountNet = new Money($this->getDiscountValueFromAmount($totalNet, $discount));

            $discountValues[] = [
                'discount' => $discount,
                'discountRack' => $discountRack,
                'discountNet' => $discountNet,
            ];
        }

        return $discountValues;
    }

    public function getDiscountValueFromAmount(string $amount, $discount) {
        $discountValue = 0;
        $typeDiscount = $discount->getTypeDiscount();

        if ($typeDiscount == Discount::TYPE_DISCOUNT_AMOUNT) {
            $discountValue = $this->getAmountDiscountValue($amount, $discount->getReductionAmount());
        } else if ($typeDiscount == Discount::TYPE_DISCOUNT_PERCENTAGE) {
            $discountValue = $this->getPercentageDiscountValue($amount, $discount->getReductionPercentage());
        }

        return $discountValue;
    }

    public function getPriceWithPercentageDiscount(string $price, string $discount): string {
        $decimalPrecision = 4;
        $discountScale = bcmul($discount, '0.01', $decimalPrecision);
        $discountScale = bcsub('1', $discountScale, $decimalPrecision); // Invert because this is a discount.

        $decimalPrecision = 2;
        return bcmul($price, $discountScale, $decimalPrecision);
    }

    public function getPriceWithAmountDiscount(string $price, string $discount): string {
        $decimalPrecision = 2;
        return bcsub($price, $discount, $decimalPrecision);
    }

    public function getPercentageDiscountValue(string $price, string $discount): string {
        $decimalPrecision = 4;
        $discountScale = bcmul($discount, '0.01', $decimalPrecision);

        $decimalPrecision = 2;
        return bcmul($price, $discountScale, $decimalPrecision);
    }

    public function getAmountDiscountValue(string $price, string $discount): string {
        return $discount;
    }

}
