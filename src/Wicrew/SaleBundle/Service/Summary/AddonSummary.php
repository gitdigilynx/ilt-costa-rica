<?php


namespace App\Wicrew\SaleBundle\Service\Summary;


use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\AddonBundle\Entity\Addon;

class AddonSummary extends SummaryElement {
    /**
     * @var int|null
     */
    private $adultQuantity;

    /**
     * @var int|null
     */
    private $childQuantity;

    /**
     * @return int|null
     */
    public function getAdultQuantity(): ?int {
        return $this->adultQuantity;
    }
 
    private $addonTitle;
 
    public function getAddonTitle() {
        return $this->addonTitle;
    }

    private $index;
 
    public function getIndex() {
        return $this->index;
    }

    /**
     * @return int|null
     */
    public function getChildQuantity(): ?int {
        return $this->childQuantity;
    }

    /**
     * @return int|null
     */
    public function getExtraTransportationQuantity(): ?int {
        return $this->extraTransportationQuantity;
    }

    /**
     * @return int|null
     */
    public function getAdultRackPrice(): ?Money {
        return $this->adultRackPrice;
    }

    /**
     * @return int|null
     */
    public function getAdultNetPrice(): ?Money {
        return $this->adultNetPrice;
    }

    /**
     * @return int|null
     */
    public function getChildRackPrice(): ?Money {
        return $this->childRackPrice;
    }

    /**
     * @return int|null
     */
    public function getChildNetPrice(): ?Money {
        return $this->childNetPrice;
    }

    /**
     * @return int|null
     */
    public function getExtraTransportation(): ?Money {
        return $this->extraTransportation;
    }

    /**
     * @param array $rackPrices
     * @param array $netPrices
     * @param string $rowTitle
     * @param string|null $displayPrice
     * @param array|null $quantity
     */
    public function __construct(array $rackPrices, array $netPrices, string $rowTitle, string $displayPrice, ?array $quantities, $addonTitle = '', $index = '') {
        parent::__construct($rackPrices[Addon::ADDON_PRICE_DISPLAY_TOTAL], $netPrices[Addon::ADDON_PRICE_DISPLAY_TOTAL], $rowTitle, $displayPrice);
        $this->adultQuantity = $quantities[Addon::ADDON_LABEL_ADULT] ?? 0;
        $this->childQuantity = $quantities[Addon::ADDON_LABEL_CHILD] ?? 0;
        $this->extraTransportationQuantity = $quantities[Addon::ADDON_LABEL_EXTRA_TRANSPORTATION] ?? 0;

        $this->adultRackPrice = $rackPrices[Addon::ADDON_PRICE_DISPLAY_ADULT] ?? new Money('0.00');
        $this->adultNetPrice = $netPrices[Addon::ADDON_PRICE_DISPLAY_ADULT] ?? new Money('0.00');
        $this->childRackPrice = $rackPrices[Addon::ADDON_PRICE_DISPLAY_CHILD] ?? new Money('0.00');
        $this->childNetPrice = $netPrices[Addon::ADDON_PRICE_DISPLAY_CHILD] ?? new Money('0.00');
        $this->extraTransportation = $rackPrices[Addon::ADDON_PRICE_DISPLAY_EXTRA_TRANSPORTATION] ?? new Money('0.00');
        $this->addonTitle = $addonTitle;
        $this->index = $index;
    }
}