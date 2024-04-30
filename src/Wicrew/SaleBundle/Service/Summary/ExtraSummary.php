<?php


namespace App\Wicrew\SaleBundle\Service\Summary;


use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\AddonBundle\Entity\Addon;

class ExtraSummary extends SummaryElement {
    /**
     * @var int|null
     */
    private $quantity;

    /**
     * @return int|null
     */
    public function getQuantity(): ?int {
        return $this->quantity;
    }

    private $index;
 
    public function getIndex() {
        return $this->index;
    }

    private $addonTitle;
 
    public function getAddonTitle() {
        return $this->addonTitle;
    }

    /**
     * @param array $rackPrices
     * @param array $netPrices
     * @param string $rowTitle
     * @param string|null $displayPrice
     * @param array|null $quantities
     */
    public function __construct(array $rackPrices, array $netPrices, string $rowTitle, string $displayPrice, ?array $quantities, $addonTitle = '', $index = '') {
        parent::__construct($rackPrices[Addon::ADDON_PRICE_DISPLAY_TOTAL], $netPrices[Addon::ADDON_PRICE_DISPLAY_TOTAL], $rowTitle, $displayPrice);
        $this->quantity = $quantities[Extra::EXTRA_LABEL_PRICE] ?? 0;
        $this->addonTitle = $addonTitle;
        $this->index = $index;
    }
}