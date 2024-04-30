<?php


namespace App\Wicrew\SaleBundle\Service\Summary;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\AdditionalFeeBundle\Entity\AdditionalFee;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\AddonBundle\Entity\AddonOption;
use App\Wicrew\AddonBundle\Entity\ExtraOption;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\CoreBundle\Entity\IBasePriceEntity;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasAddon;
use App\Wicrew\SaleBundle\Entity\OrderItemHasExtra;
use App\Wicrew\SaleBundle\Entity\Tax;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

/**
 * Computes and displays pricing information for a given booking item.
 *
 * @package App\Wicrew\SaleBundle\Service\Summary
 */
abstract class PriceSummary {
    private $identifier;

    public function getIdentifier(): int {
        return $this->identifier;
    }

    private $adultCount;

    public function getAdultCount(): int {
        return $this->adultCount;
    }

    private $childCount;

    public function getChildCount(): int {
        return $this->childCount;
    }

    private $adultRackPrice;

    public function getAdultRackPrice(): string {
        return $this->adultRackPrice;
    }

    public function setAdultRackPrice($adultRackPrice): string {
        return $this->adultRackPrice = $adultRackPrice;
    }

    private $childRackPrice;

    public function getChildRackPrice(): string {
        return $this->childRackPrice;
    }

    public function setChildRackPrice($childRackPrice): string {
        return $this->childRackPrice = $childRackPrice;
    }

    private $adultNetPrice;

    public function getAdultNetPrice(): string {
        return $this->adultNetPrice;
    }

    public function setAdultNetPrice($adultNetPrice): string {
        return $this->adultNetPrice = $adultNetPrice;
    }

    private $childNetPrice;

    public function getChildNetPrice(): string {
        return $this->childNetPrice;
    }

    public function setChildNetPrice($childNetPrice): string {
        return $this->childNetPrice = $childNetPrice;
    }

    private $customerNotes;

    public function getCustomerNotes(){
        return $this->customerNotes;
    }

    public function setCustomerNotes($customerNotes){
        $this->customerNotes = $customerNotes;
    }


    private $activityType;

    public function getActivityType(): string {
        return $this->activityType;
    }

    public function setActivityType($activityType): string {
        return $this->activityType = $activityType;
    }

    private $tax;

    public function getTax(): Tax {
        return $this->tax;
    }

    /**
     * @var AreaSummary
     */
    private $areaFrom;

    public function getAreaFrom(): AreaSummary {
        return $this->areaFrom;
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $address
     * @param string $googlePlaceID
     */
    public function setAreaFrom_Area(int $id, string $name, string $address, string $googlePlaceID): void {
        $this->areaFrom = AreaSummary::constructArea($id, $name, $address, $googlePlaceID);
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $flightNumber
     * @param string $airlineCompany
     */
    public function setAreaFrom_Airport(int $id, string $name, string $flightNumber, string $airlineCompany): void {
        $this->areaFrom = AreaSummary::constructAirport($id, $name, $flightNumber, $airlineCompany);
    }

    /**
     * @var AreaSummary
     */
    private $areaTo;

    public function getAreaTo(): AreaSummary {
        return $this->areaTo;
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $address
     * @param string $googlePlaceID
     */
    public function setAreaTo_Area(int $id, string $name, string $address, string $googlePlaceID): void {
        $this->areaTo = AreaSummary::constructArea($id, $name, $address, $googlePlaceID);
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $flightNumber
     * @param string $airlineCompany
     */
    public function setAreaTo_Airport(int $id, string $name, string $flightNumber, string $airlineCompany): void {
        $this->areaTo = AreaSummary::constructAirport($id, $name, $flightNumber, $airlineCompany);
    }

    /* @var DateTime|null $pickUpDate */
    public $pickUpDate = null;

    public function getPickUpDate(): ?DateTime {
        return $this->pickUpDate;
    }

    /* @var string $imageURL */
    private $imageURL;

    public function setImage(string $image): void {
        $this->imageURL = $image;
    }

    public function getImage(): string {
        return $this->imageURL;
    }

    /**
     * @param int $identifier
     * @param int $adultCount
     * @param int $childCount
     * @param DateTime|null $pickUpDate
     * @param string $adultRackPrice
     * @param string $childRackPrice
     * @param string $adultNetPrice
     * @param string $childNetPrice
     */
    protected function __construct(int $identifier, int $adultCount, int $childCount, ?DateTime $pickUpDate = null, string $adultRackPrice = '0', string $childRackPrice  = '0', string $adultNetPrice = '0', string $childNetPrice  = '0', Tax $tax  = null) {
        $this->identifier = $identifier;
        $this->adultCount = $adultCount;
        $this->childCount = $childCount;
        $this->pickUpDate = $pickUpDate;
        $this->adultRackPrice = $adultRackPrice;
        $this->childRackPrice = $childRackPrice;
        $this->adultNetPrice = $adultNetPrice;
        $this->childNetPrice = $childNetPrice;
        $this->tax = $tax;
    }

    public function isProduct(): bool {
        return false;
    }

    public function isActivity(): bool {
        return false;
    }

    public abstract function getResolvedObject(EntityManager $em): BaseEntity;

    /* @var SummaryElement $titlePrice The initial price of the item. */
    private $titlePrice = null;

    public function getTitlePrice(): SummaryElement {
        return $this->titlePrice;
    }

    /**
     * @param Money $rackPrice
     * @param Money $netPrice
     * @param string $rowTitle
     * @param string|null $displayPrice
     */
    protected function setTitlePrice(Money $rackPrice, Money $netPrice, string $rowTitle, ?string $displayPrice = null): void {
        $this->titlePrice = new SummaryElement($rackPrice, $netPrice, $rowTitle, $displayPrice);
    }

    /* @var string[] $bulletList Bullet points of misc. info. */
    private $bulletList = [];

    public function getBulletList(): array {
        return $this->bulletList;
    }

    protected function addBulletPoint(string $bullet): void {
        $this->bulletList[] = $bullet;
    }

    /**
     * @var AddonSummary[]|null
     */
    private $addons = null;

    public function getAddons(): ?array {
        return $this->addons;
    }

    public function setAddons($addons) {
        $this->addons = $addons;
    }

    public function anyAddons(): bool {  
        return $this->addons !== null && ($this->addons != []);  
    }

    /**
     * @param string $id
     * @param array $rackPrice
     * @param array $netPrice
     * @param string $rowTitle
     * @param string $displayPrice
     * @param array|null $quantity
     */
    public function addAddon(string $id, array $rackPrices, array $netPrices, string $rowTitle, string $displayPrice, ?array $quantities, $addonTitle = '', $index = ''): void {
        if ($this->addons === null) {
            $this->addons = array();
        }

        $this->addons[$id] = new AddonSummary($rackPrices, $netPrices, $rowTitle, $displayPrice, $quantities, $addonTitle, $index);
    }

    /**
     * @var ExtraSummary[]|null
     */
    private $extras = null;

    public function getExtras(): ?array {
        return $this->extras;
    }

    public function setExtras($extras){
        $this->extras = $extras;
    }

    public function anyExtras(): bool {
        return $this->extras !== null && ($this->extras != []); 
    }

    /**
     * @param string $id
     * @param array $rackPrice
     * @param array $netPrice
     * @param string $rowTitle
     * @param string $displayPrice
     * @param array|null $quantity
     */
    public function addExtra(string $id, array $rackPrices, array $netPrices, string $rowTitle, string $displayPrice, ?array $quantities, $extraTitle = '', $index = ''): void {
        if ($this->extras === null) {
            $this->extras = array();
        }

        $this->extras[$id] = new ExtraSummary($rackPrices, $netPrices, $rowTitle, $displayPrice, $quantities, $extraTitle, $index);
    }

    /**
     * Other details to write in the summary.
     *
     * @var SummaryElement[]
     */
    private $otherRowBlocks = [];

    public function getOtherRowBlocks(): array {
        return $this->otherRowBlocks;
    }

    /**
     * @param Money $rackPrice
     * @param Money $netPrice
     * @param string $rowTitle
     * @param string|null $displayPrice
     */
    protected function addRowBlock(Money $rackPrice, Money $netPrice, string $rowTitle, ?string $displayPrice = null): void {
        $this->otherRowBlocks[] = new SummaryElement($rackPrice, $netPrice, $rowTitle, $displayPrice);
    }

    /**
     * @param IBasePriceEntity $entity
     * @param int $adultCount
     * @param int $childCount
     * @param int $activityType
     *
     * @return Money[]
     */
    protected function getRackNetTaxTotal(IBasePriceEntity $entity, int $adultCount, int $childCount, int $activityType): array {
        $totalRack = new Money();
        $totalNet = new Money();
        $totalTax = new Money();

        if ($entity->getPriceType() === IBasePriceEntity::PRICE_TYPE_PER_PERSON) {
            $totalAdultRack = (new Money($entity->getAdultRackPrice()))->multiply($adultCount);
            $totalAdultNet = (new Money($entity->getAdultNetPrice()))->multiply($adultCount);
            $totalChildRack = (new Money($entity->getChildRackPrice()))->multiply($childCount);
            $totalChildNet = (new Money($entity->getChildNetPrice()))->multiply($childCount);

            if($activityType == 1){

                $totalAdultRack     = (new Money($entity->getGroupAdultRackPrice()))->multiply($adultCount);
                $totalAdultNet      = (new Money($entity->getGroupAdultNetPrice()))->multiply($adultCount);
                $totalChildRack     = (new Money($entity->getGroupKidRackPrice()))->multiply($childCount);
                $totalChildNet      = (new Money($entity->getGroupKidNetPrice()))->multiply($childCount);
            }

            $totalRack = $totalAdultRack->add($totalChildRack);
            $totalNet = $totalAdultNet->add($totalChildNet);
        } else if ($entity->getPriceType() === IBasePriceEntity::PRICE_TYPE_FOR_THE_TRIP) {
            $totalRack = new Money($entity->getFixedRackPrice());
            $totalNet = new Money($entity->getFixedNetPrice());
        }
        if ($this->priceIncludesTax()) {
            $totalTax = $totalRack->multiplyByTax($entity->getTax());
        }

        return [ $totalRack, $totalNet, $totalTax ];
    }

    protected function buildAreaSummary(array $areaInputInfo, bool $forTo = true) {
        if ((int)$areaInputInfo['type'] === Area::TYPE_AIRPORT) {
            if ($forTo) {
                $this->setAreaTo_Airport($areaInputInfo['id'], $areaInputInfo['name'], $areaInputInfo['flightNumber'], $areaInputInfo['airlineCompany']);
            } else {
                $this->setAreaFrom_Airport($areaInputInfo['id'], $areaInputInfo['name'], $areaInputInfo['flightNumber'], $areaInputInfo['airlineCompany']);
            }
        } else if ((int)$areaInputInfo['type'] === Area::TYPE_AREA) {
            if( $areaInputInfo['googlePlaceID']  == null ){
                $areaInputInfo['googlePlaceID'] = "";
            } 
            if ($forTo) {
                $this->setAreaTo_Area($areaInputInfo['id'], $areaInputInfo['name'], $areaInputInfo['address'], $areaInputInfo['googlePlaceID']);
            } else {
                $this->setAreaFrom_Area($areaInputInfo['id'], $areaInputInfo['name'], $areaInputInfo['address'], $areaInputInfo['googlePlaceID']);
            }
        }
    }

    protected function buildAdditionalFees(EntityManager $em, array $areaInfo, AreaSummary $area, ?Money &$additionalFeeTax): void {
        $additionalFeeTax = new Money();

        /* @var AdditionalFee $addFee */
        $addFee = $em->getRepository(AdditionalFee::class)->findOneBy(['googlePlaceId' => $areaInfo['googlePlaceID']]);
        if ($addFee !== null) {
            $additionalFeeRack = new Money($addFee->getRackPrice());
            $additionalFeeNet = new Money($addFee->getNetPrice());
            $additionalFeeTax = $additionalFeeRack->multiplyByTax($addFee->getTax());

            $area->setAdditionalFee($additionalFeeRack, $additionalFeeNet, $addFee->getHotelName());
        }
    }

    /**
     * The total price without tax.
     *
     * @var SummaryElement
     */
    private $subtotalPrice = null;

    public function getSubtotalPrice(): SummaryElement {
        return $this->subtotalPrice;
    }

    /**
     * @param Money $rackPrice
     * @param Money $netPrice
     */
    public function setSubtotal(Money $rackPrice, Money $netPrice): void {
        $this->subtotalPrice = new SummaryElement($rackPrice, $netPrice, 'Subtotal');
    }

    protected function priceIncludesTax(): bool {
        return $_ENV['USE_TAX'] === "true";
    }

    /**
     * The tax price of the item.
     *
     * @var SummaryElement
     */
    private $totalTaxes = null;

    public function getTotalTaxes(): SummaryElement {
        return $this->totalTaxes;
    }

    /**
     * @param Money $rackPrice
     * @param string $rowTitle
     * @param string|null $displayPrice
     */
    public function setTotalTaxes(Money $rackPrice, string $rowTitle, ?string $displayPrice = null): void {
        $this->totalTaxes = new SummaryElement($rackPrice, $rackPrice, $rowTitle, $displayPrice);
    }

    /**
     * The total price of the item.
     *
     * @var SummaryElement
     */
    private $grandTotal = null;

    public function getGrandTotal(): SummaryElement {
        return $this->grandTotal;
    }

    /**
     * @param Money $rackPrice
     * @param Money $netPrice
     * @param string $rowTitle
     * @param string|null $displayPrice
     */
    public function setGrandTotal(Money $rackPrice, Money $netPrice, string $rowTitle, ?string $displayPrice = null): void {
        $this->grandTotal = new SummaryElement($rackPrice, $netPrice, $rowTitle, $displayPrice);
    }

    public function toOrderItem(EntityManager $em, OrderItem $item): OrderItem {
        $item->setChildCount($this->getChildCount());
        $item->setAdultCount($this->getAdultCount());
        $item->setPickDate($this->getPickUpDate());

        // Wipe any previous info from the order item (except history).
        $item->setPickTime(null);
        $item->setTourTime(null);
        $item->setPickAddress(null);
        $item->setPickGooglePlaceID(null);
        $item->setPickFlightNumber(null);
        $item->setPickAirlineCompany(null);
        $item->setPickAddFeeNet(null);
        $item->setPickAddFeeRack(null);
        $item->setDropAddress(null);
        $item->setDropGooglePlaceID(null);
        $item->setDropFlightNumber(null);
        $item->setDropAirlineCompany(null);
        $item->setDropAddFeeNet(null);
        $item->setDropAddFeeRack(null);
        $item->setRegularTimeFeeNet(null);
        $item->setRegularTimeFeeRack(null);
        $item->setFlightPickTimeFeeNet(null);
        $item->setFlightPickTimeFeeRack(null);
        $item->setFlightDropTimeFeeNet(null);
        $item->setFlightDropTimeFeeRack(null);
         
        $item->setAddons(new ArrayCollection());
        $item->setExtras(new ArrayCollection());
        $item->setProduct(null);
        $item->setActivity(null);
        $item->setComboChildren(new ArrayCollection());

        /* @var Area $area */
        $area = $em->getReference(Area::class, $this->getAreaFrom()->getIdentifier());
        $item->setPickArea($area);
        if ($this->areaFrom->getType() === Area::TYPE_AREA) {
            $item->setPickAddress($this->getAreaFrom()->getAddress());
            $item->setPickGooglePlaceID($this->getAreaFrom()->getGooglePlaceID());
        } else if ($this->areaFrom->getType() === Area::TYPE_AIRPORT) {
            $item->setPickFlightNumber($this->getAreaFrom()->getFlightNumber());
            $item->setPickAirlineCompany($this->getAreaFrom()->getAirlineCompany());
        }
 
        // reset airport data
        if ($this->isProduct()) {
            $item->setPickFlightNumber($this->getPickFlightNumber());
            $item->setPickAirlineCompany($this->getPickAirlineCompany());
        }

        if ($this->getAreaFrom()->hasAddFee()) {
            $item->setPickAddFeeNet($this->getAreaFrom()->getAdditionalFee()->getNetPrice());
            $item->setPickAddFeeRack($this->getAreaFrom()->getAdditionalFee()->getRackPrice());
        }

        $area = $em->getReference(Area::class, $this->getAreaTo()->getIdentifier());
        $item->setDropArea($area);
        if ($this->getAreaTo()->getType() === Area::TYPE_AREA) {
            $item->setDropAddress($this->getAreaTo()->getAddress());
            $item->setDropGooglePlaceID($this->getAreaTo()->getGooglePlaceID());
        } else if ($this->getAreaTo()->getType() === Area::TYPE_AIRPORT) {
            $item->setDropFlightNumber($this->getAreaTo()->getFlightNumber());
            $item->setDropAirlineCompany($this->getAreaTo()->getAirlineCompany());
        }
        if ($this->getAreaTo()->hasAddFee()) {
            $item->setDropAddFeeNet($this->getAreaTo()->getAdditionalFee()->getNetPrice());
            $item->setDropAddFeeRack($this->getAreaTo()->getAdditionalFee()->getRackPrice());
        }

        if ($this->anyAddons()) {
            foreach ($this->getAddons() as $addonID => $addon) {
                $itemAddon = new OrderItemHasAddon();

                $idPieces = explode("-", $addonID, 2);
                /* @var Addon $addonObj */
                $addonObj = $em->getRepository(Addon::class)->find($idPieces[0]);
                $itemAddon->setAddon($addonObj);

                if (!isset($idPieces[1])) {
                    $itemAddon->setPriceType($addonObj->getPriceType());
                } else {
                    /* @var AddonOption $addonOptionObj */
                    $addonOptionObj = $em->getRepository(AddonOption::class)->find($idPieces[1]);
                    $itemAddon->setAddonOption($addonOptionObj);
                    $itemAddon->setPriceType($addonOptionObj->getPriceType());
                }

                $addons = $em->getRepository(TaxConfig::class)->findBy([
                    'label' => "addons"
                ]); 
                if ( count($addons) > 0 ){
                    $addons = $addons[0];
                }
                
                $pickUpDate     = $this->getPickUpDate();
                $month          = $pickUpDate->format('n');
                $year           = $pickUpDate->format('Y');
                
                $tax = new Tax();

                $now            = new DateTime();
                $current_year   = $now->format("Y");
                if ( $year == $current_year ){
                    if( $month <= 6 ){
                        // JAN MAY RATE
                        $tax->setAmount( $addons->getJanMayRate() );
                        $tax->setLabel("VAT (".$addons->getJanMayRate()."%)");
                    }else{
                        // JUN DEC RATE 
                        $tax->setAmount( $addons->getJunDecRate() );
                        $tax->setLabel("VAT (".$addons->getJunDecRate()."%)");
                    }
                }else if ( $year < $current_year ){
                    // JAN MAY RATE
                    $tax->setAmount( $addons->getJanMayRate() );
                    $tax->setLabel("VAT (".$addons->getJanMayRate()."%)");
                }else if ( $year > $current_year ){
                    // JUN DEC RATE     
                    $tax->setAmount( $addons->getJunDecRate() );
                    $tax->setLabel("VAT (".$addons->getJunDecRate()."%)");
                }
                $itemAddon->setTax($addon->getRackPrice()->multiplyByTax($tax));                
                // $itemAddon->setLabel( $addon->getRowTitle() );
                $itemAddon->setLabel($addon->getAddonTitle()."<br> <small>(<em>Adults x ".$addon->getAdultQuantity().": $".$addonObj->getAdultRackPrice() * $addon->getAdultQuantity() ." USD</em>, <em>Children x ".$addon->getChildQuantity().": $".$addonObj->getChildRackPrice() * $addon->getChildQuantity( )." USD</em>, <em>Extra Transportation: $".$addonObj->getExtraTransportation()." USD</em>)</small> ");
                $itemAddon->setAddonTitle($addon->getAddonTitle());
                $itemAddon->setRackPrice($addon->getRackPrice());
                $itemAddon->setNetPrice($addon->getNetPrice());
                $itemAddon->setAdultQuantity($addon->getAdultQuantity());
                $itemAddon->setChildQuantity($addon->getChildQuantity());
                $itemAddon->setExtraTransportationQuantity($addon->getExtraTransportationQuantity());
                $itemAddon->setAdultRackPrice($addon->getAdultRackPrice());
                $itemAddon->setAdultNetPrice($addon->getAdultNetPrice());
                $itemAddon->setChildRackPrice($addon->getChildRackPrice());
                $itemAddon->setChildNetPrice($addon->getChildNetPrice());
                $itemAddon->setExtraTransportation( $addonObj->getExtraTransportation());

                $item->addAddon($itemAddon);
            }
        }

        if ($this->anyExtras()) {
            foreach ($this->getExtras() as $extraID => $extra) {
                $itemExtra = new OrderItemHasExtra();

                $idPieces = explode("-", $extraID, 2);
                /* @var Extra $extraObj */
                $extraObj = $em->getRepository(Extra::class)->find($idPieces[0]);
                $itemExtra->setExtra($extraObj);

                if (!isset($idPieces[1])) {
                    $itemExtra->setPriceType($extraObj->getPriceType());
                    // $itemExtra->setTax($extra->getRackPrice()->multiplyByTax($extraObj->getTax()));
                } else {
                    /* @var ExtraOption $extraOptionObj */
                    $extraOptionObj = $em->getRepository(ExtraOption::class)->find($idPieces[1]);
                    $itemExtra->setExtraOption($extraOptionObj);
                    $itemExtra->setPriceType($extraOptionObj->getPriceType());
                    // $itemExtra->setTax($extra->getRackPrice()->multiplyByTax($extraOptionObj->getTax()));
                }
                $extras = $em->getRepository(TaxConfig::class)->findBy([
                    'label' => "extras"
                ]);  
                if ( count($extras) > 0 ){
                    $extras = $extras[0];
                }
                $pickUpDate     = $this->getPickUpDate();
                $month          = $pickUpDate->format('n');
                $year           = $pickUpDate->format('Y');
                $tax = new Tax();
              
                $now            = new DateTime();
                $current_year   = $now->format("Y");
                if ( $year == $current_year ){
                    if( $month <= 6 ){
                        // JAN MAY RATE
                        $tax->setAmount( $extras->getJanMayRate() );
                        $tax->setLabel("VAT (".$extras->getJanMayRate()."%)");
                    }else{
                        // JUN DEC RATE 
                        $tax->setAmount( $extras->getJunDecRate() );
                        $tax->setLabel("VAT (".$extras->getJunDecRate()."%)");
                    }
                }else if ( $year < $current_year ){
                    // JAN MAY RATE
                    $tax->setAmount( $extras->getJanMayRate() );
                    $tax->setLabel("VAT (".$extras->getJanMayRate()."%)");
                }else if ( $year > $current_year ){
                    // JUN DEC RATE     
                    $tax->setAmount( $extras->getJunDecRate() );
                    $tax->setLabel("VAT (".$extras->getJunDecRate()."%)");
                }   
                $itemExtra->setTax($extra->getRackPrice()->multiplyByTax($tax));


                $itemExtra->setLabel($extra->getRowTitle());
                $itemExtra->setAddonTitle($extra->getAddonTitle());
                $itemExtra->setRackPrice($extra->getRackPrice());
                $itemExtra->setNetPrice($extra->getNetPrice());
                $itemExtra->setQuantity($extra->getQuantity());

                $item->addExtra($itemExtra);
            }
        }

        if ($this->getCustomerNotes()) {
            $item->setCustomerNotes($this->getCustomerNotes());
        }

        /* @var Product|Activity $ormItem */
        $ormItem = $this->getResolvedObject($em);
        if ($this->isProduct()) {
            $item->setProduct($ormItem);
        } else if ($this->isActivity()) {
            $item->setActivity($ormItem);
            $item->setActivityType($this->getActivityType());
        }
        $item->setTitleRackPrice($this->getTitlePrice()->getRackPrice());
        $item->setTitleNetPrice($this->getTitlePrice()->getNetPrice());
        $item->setSubtotalRack($this->getSubtotalPrice()->getRackPrice());
        $item->setSubtotalNet($this->getSubtotalPrice()->getNetPrice());
        $item->setTotalTax($this->getTotalTaxes()->getRackPrice());
        $item->setGrandTotal($this->getGrandTotal()->getRackPrice());
        $item->setGrandTotalNet($this->getGrandTotal()->getNetPrice());

        return $item;
    }
}
