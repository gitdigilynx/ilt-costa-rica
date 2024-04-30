<?php


namespace App\Wicrew\SaleBundle\Service\Summary;

use App\Wicrew\CoreBundle\Service\Money;

/**
 * Refers to an individual element (row) in the price breakdown of an OrderItem.
 *
 * @package App\Wicrew\SaleBundle\Service\Summary
 */
class SummaryElement {
    /* @var Money $rackPrice */
    private $rackPrice;

    public function getRackPrice(): Money {
        return $this->rackPrice;
    }

    /* @var Money $netPrice */
    private $netPrice;

    public function getNetPrice(): Money {
        return $this->netPrice;
    }

    /* @var string $rowTitle The title shown on the left side of the row when displaying this element. */
    private $rowTitle;

    public function getRowTitle(): string {
        return $this->rowTitle;
    }

    /* @var string|null $displayText The price shown on the right side of the row when displaying this element. */
    private $displayPrice;

    public function getDisplayText(): string {
        if ($this->displayPrice !== null) {
            return $this->displayPrice;
        }
        return $this->rackPrice;
    }

    /**
     * @param Money $rackPrice
     * @param Money $netPrice
     * @param string $rowTitle
     * @param string|null $displayPrice
     */
    public function __construct(Money $rackPrice, Money $netPrice, string $rowTitle, ?string $displayPrice = null) {
        $this->rackPrice = $rackPrice;
        $this->netPrice = $netPrice;
        $this->rowTitle = $rowTitle;
        $this->displayPrice = $displayPrice;
    }
}