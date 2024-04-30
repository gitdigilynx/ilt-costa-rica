<?php


namespace App\Wicrew\SaleBundle\Service\Summary;


use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\AdditionalFeeBundle\Entity\AdditionalFee;
use App\Wicrew\AddonBundle\Entity\AddonOption;
use App\Wicrew\AddonBundle\Entity\ExtraOption;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\SaleBundle\Entity\Tax;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SystemConfigurationBundle\Entity\SystemConfiguration;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ProductSummary
 * @package App\Wicrew\SaleBundle\Service\Summary
 */
class ProductSummary extends PriceSummary {
    /**
     * @var string
     */
    private $maxPassengerNumber;

    public function getMaxPassengerNumber() {
        return $this->maxPassengerNumber;
    }

    /**
     * @var string
     */
    private $duration;

    public function getDuration() {
        return $this->duration;
    }

    /**
     * @var string
     */
    private $vehicleName;

    public function getVehicleName(): string {
        return $this->vehicleName;
    }


    /**
     * @var array
     */
    private $customServices;

    public function getCustomServices(): array {
        return $this->customServices ?? [];
    }

    
    /**
     * @var int
     */
    private $transportationType = -1;

    public function getTransportationType(): int {
        return $this->transportationType;
    }

    /* @var DateTime|null $pickUpTime */
    public $pickUpTime = null;

    public function getPickUpTime(): ?DateTime {
        return $this->pickUpTime;
    }

    /**
     * @var string
     */
    private $luggageWeight;

    public function getLuggageWeight(): string {
        return $this->luggageWeight;
    }

    public function setLuggageWeight(string $weight): void {
        $this->luggageWeight = $weight;
    }

    /**
     * @var string
     */
    private $passengerWeight;

    /**
     * @var bool
     */
    private $isConnectingFlight = true;

    public function getPassengerWeight(): string {
        return $this->passengerWeight;
    }

    public function setPassengerWeight(string $weight): void {
        $this->passengerWeight = $weight;
    }

    public function getIsConnectingFlight(): bool {
        return $this->isConnectingFlight;
    }

    public function setIsConnectingFlight(bool $isConnectingFlight): void {
        $this->isConnectingFlight = $isConnectingFlight;
    }

    /**
     * @var string
     */
    private $pickAirlineCompany = '';

    public function getPickAirlineCompany(): string {
        return $this->pickAirlineCompany;
    }

    public function setPickAirlineCompany(string $pickAirlineCompany): void {
        $this->pickAirlineCompany = $pickAirlineCompany;
    }

    /**
     * @var string
     */
    private $pickFlightNumber = '';

    public function getPickFlightNumber(): string {
        return $this->pickFlightNumber;
    }

    public function setPickFlightNumber(string $pickFlightNumber): void {
        $this->pickFlightNumber = $pickFlightNumber;
    }
    
    /**
     * @var SummaryElement|null
     */
    private $regularTimeFee = null;

    /**
     * @param Money $rack
     * @param Money $net
     * @param string $rowTitle
     * @param string $displayPrice
     */
    public function setRegularTimeFee(Money $rack, Money $net, string $rowTitle, string $displayPrice): void {
        $this->regularTimeFee = new SummaryElement($rack, $net, $rowTitle, $displayPrice);
    }

    /**
     * @var SummaryElement|null
     */
    private $flightPickTimeFee = null;

    /**
     * @param Money $rack
     * @param Money $net
     * @param string $rowTitle
     * @param string $displayPrice
     */
    public function setFlightPickTimeFee(Money $rack, Money $net, string $rowTitle, string $displayPrice): void {
        $this->flightPickTimeFee = new SummaryElement($rack, $net, $rowTitle, $displayPrice);
    }

    /**
     * @var SummaryElement|null
     */
    private $flightDropTimeFee = null;

    /**
     * @param Money $rack
     * @param Money $net
     * @param string $rowTitle
     * @param string $displayPrice
     */
    public function setFlightDropTimeFee(Money $rack, Money $net, string $rowTitle, string $displayPrice): void {
        $this->flightDropTimeFee = new SummaryElement($rack, $net, $rowTitle, $displayPrice);
    }

    /**
     * @var bool
     */
    private $bookingTooLate = false;

    /**
     * Is this booking too late to guarantee?
     *
     * @param EntityManager $em
     *
     * @throws Exception
     */
    public function setBookingTooLate(EntityManager $em): void {
        $configValue = $em->getRepository(SystemConfiguration::class)->getConfigValue('late-booking/hour_threshold');
        if ($configValue !== null && $this->getPickUpTime() !== null && $this->getPickUpDate() !== null) {
            $configValue = (int) $configValue;
            $daysDiff = intdiv($configValue, 24);
            $hoursDiff = $configValue % 24;

            $now = new DateTime();
            $pickHour = $this->getPickUpTime()->format('H');
            $pickMinute = $this->getPickUpTime()->format('i');

            $pickDateTime = new DateTime($this->getPickUpDate()->format('Y-m-d'));
            $pickDateTime = $pickDateTime->setTime($pickHour, $pickMinute);

            $difference = $pickDateTime->diff($now);
            if (
                $pickDateTime <= $now ||
                ($difference->d <= $daysDiff &&
                    ($difference->h < $hoursDiff || ($difference->h == $hoursDiff && $difference->i <= 0))
                )
            ) {
                $this->bookingTooLate = true;
                return;
            }
        }

        $this->bookingTooLate = false;
    }

    /**
     * @return bool
     */
    public function isBookingTooLate(): bool {
        return $this->bookingTooLate;
    }

    public function __construct(
        Product $product,
        EntityManager $em,
        TranslatorInterface $translator,
        KernelInterface $kernel,
        int $adultCount,
        int $childCount,
        array $areaFromInputInfo,
        array $areaToInputInfo,
        ?DateTime $pickUpTime   = null,
        ?DateTime $pickUpDate   = null,
        array $enabledAddons    = null,
        array $enabledExtras    = null,
        ?array $custom_services  = []
        ) {

        $adultRackPrice = $product->getAdultRackPrice();
        $childRackPrice = $product->getChildRackPrice();
        $adultNetPrice = $product->getAdultNetPrice();
        $childNetPrice = $product->getChildNetPrice();
        $childNetPrice = $product->getChildNetPrice();

        $tax = new Tax();
        $tax->setAmount("8.00");
        $tax->setLabel("VAT (8%)");
        $tax->setCreatedAt(new \DateTime());
        $tax->setModifiedAt(new \DateTime());
        
        

        parent::__construct(
            $product->getId(),
            $adultCount,
            $childCount,
            $pickUpDate,
            $adultRackPrice,
            $childRackPrice,
            $adultNetPrice,
            $childNetPrice,
            $tax
        );

        $this->pickUpTime = $pickUpTime;
        $maxPassengerNumber = '';
        if ($product->getVehicleType()) {
            $maxPassengerNumber = $product->getVehicleType()->getMaxPassengerNumber();    
        } 
        $this->maxPassengerNumber = $maxPassengerNumber;
        $this->duration = $product->getDuration() ;
        
        try {
            $this->setBookingTooLate($em);
        } catch (Exception $e) {
            $now = new DateTime("now");
            $now = $now->format('Y-m-d H:i');
            $str = "[$now]: $e";
            file_put_contents($kernel->getProjectDir() . '/var/log/error.log', $str);
        }
        $this->vehicleName = $product->getVehicleType()->getName();
        $this->transportationType = $product->getTransportationType()->getId();
        $this->customServices = $custom_services;

        $this->buildPriceSummary($pickUpDate, $product, $em, $translator, $areaFromInputInfo, $areaToInputInfo, $adultCount, $childCount, $enabledAddons, $enabledExtras, $custom_services);
    }

    public function isProduct(): bool {
        return true;
    }

    public function getResolvedObject(EntityManager $em): BaseEntity {
        return $em->getRepository(Product::class)->find($this->getIdentifier());
    }

    private function buildPriceSummary(
        ?DateTime $pickUpDate,
        Product $product,
        EntityManager $em,
        TranslatorInterface $translator,
        array $areaFromInputInfo,
        array $areaToInputInfo,
        int $adultCount = 0,
        int $childCount = 0,
        array $enabledAddons = null,
        array $enabledExtras = null,
        array $custom_services = null
        ): void {
        
        $novemberFirst = new DateTime('2023-11-01');

        if ($product->getTransportationType()->isPrivateType()) {
            
            if ($pickUpDate >= $novemberFirst) {
                
                if( $product->getNovemberFixedRackPrice() != 0 && $product->getNovemberFixedNetPrice() != 0 ) {

                    $productRackPrice   = new Money($product->getNovemberFixedRackPrice());
                    $productNetPrice    = new Money($product->getNovemberFixedNetPrice());
                }else{

                    $productRackPrice   = new Money($product->getFixedRackPrice());
                    $productNetPrice    = new Money($product->getFixedNetPrice());   
                }
                
            } else {
                $productRackPrice   = new Money($product->getFixedRackPrice());
                $productNetPrice    = new Money($product->getFixedNetPrice());    
            }
            
            if (strpos(strtolower($product->getTransportationType()), "jeep") !== false) { // IF PRIVATE JBJ AND PASSENGER EXCEEDING THAN 5, ADD PER PERSON COST FOR ADDITIONALS
                $totalPassengers = $this->getAdultCount() + $this->getChildCount();
                if( $totalPassengers > 5 ){
                    $passengersMoreThanFive = $totalPassengers - 5;
                    
                    global $kernel;
                    $utils                      = $kernel->getContainer()->get('wicrew.core.utils');
                    $additionalPassengerPrice   = $utils->getSystemConfigValues('additional/jbj', true);
                    // print_r($additionalPassengerPrice);
                    $additionalPassengerPrice   = $additionalPassengerPrice['additional']['jbj']['price'];
                    $additionalPassengerPrice   = new Money( $additionalPassengerPrice );
                    $priceToAdd                 = $additionalPassengerPrice->multiply( $passengersMoreThanFive );
                    $productRackPrice           = $productRackPrice->add($priceToAdd);

                }
            }    
        } else {
            $adultRackPrice     = new Money($product->getAdultRackPrice());
            $adultNetPrice      = new Money($product->getAdultNetPrice());
            $childRackPrice     = new Money($product->getChildRackPrice());
            $childNetPrice      = new Money($product->getChildNetPrice());
            
            if ($pickUpDate >= $novemberFirst) {
                if( $product->getNovemberAdultRackPrice() != 0 && $product->getNovemberAdultNetPrice() != 0 && $product->getNovemberChildRackPrice() == 0 && $product->getNovemberChildNetPrice() == 0 ){

                    $adultRackPrice     = new Money($product->getAdultRackPrice());
                    $adultNetPrice      = new Money($product->getAdultNetPrice());
                    $childRackPrice     = new Money($product->getChildRackPrice());
                    $childNetPrice      = new Money($product->getChildNetPrice());
                }else{

                    $adultRackPrice     = new Money($product->getNovemberAdultRackPrice());
                    $adultNetPrice      = new Money($product->getNovemberAdultNetPrice());
                    $childRackPrice     = new Money($product->getNovemberChildRackPrice());
                    $childNetPrice      = new Money($product->getNovemberChildNetPrice());
                }
                 
            } else {
                $adultRackPrice     = new Money($product->getAdultRackPrice());
                $adultNetPrice      = new Money($product->getAdultNetPrice());
                $childRackPrice     = new Money($product->getChildRackPrice());
                $childNetPrice      = new Money($product->getChildNetPrice());
            }
            

            $adultRackPrice     = $adultRackPrice->multiply($this->getAdultCount());
            $adultNetPrice      = $adultNetPrice->multiply($this->getAdultCount());
            $childRackPrice     = $childRackPrice->multiply($this->getChildCount());
            $childNetPrice      = $childNetPrice->multiply($this->getChildCount());

            $productRackPrice   = $adultRackPrice->add($childRackPrice);
            $productNetPrice    = $adultNetPrice->add($childNetPrice);
        }
        $productType = $product->getTransportationType()->getName();
        $this->setTitlePrice($productRackPrice, $productNetPrice, $productType);
        

        $shuttles = $em->getRepository(TaxConfig::class)->findBy([
            'label' => "shuttles"
        ]);
        if ( count($shuttles) > 0 ){
            $shuttles = $shuttles[0];
        }

        $water_taxi = $em->getRepository(TaxConfig::class)->findBy([
            'label' => "water-taxi"
        ]);    
        if ( count($water_taxi) > 0 ){
            $water_taxi = $water_taxi[0];
        } 

        $jbj = $em->getRepository(TaxConfig::class)->findBy([
            'label' => "jbj"
        ]);
        if ( count($jbj) > 0 ){
            $jbj = $jbj[0];
        }

        $flights = $em->getRepository(TaxConfig::class)->findBy([
            'label' => "flights"
        ]); 
        if ( count($flights) > 0 ){
            $flights = $flights[0];
        }

        $now            = new DateTime();
        $month          = $pickUpDate->format('n');
        $year           = $pickUpDate->format('Y');
        $current_year   = $now->format("Y");
        
       
        $tax = new Tax();
        if (strpos(strtolower($productType), 'shuttle') !== false) {
        
            if ( $year == $current_year ){
                if( $month <= 6 ){
                    // JAN MAY RATE
                    $tax->setAmount( $shuttles->getJanMayRate() );
                    $tax->setLabel("VAT (".$shuttles->getJanMayRate()."%)");
                }else{
                    // JUN DEC RATE 
                    $tax->setAmount( $shuttles->getJunDecRate() );
                    $tax->setLabel("VAT (".$shuttles->getJunDecRate()."%)");
                }
            }else if ( $year < $current_year ){
                // JAN MAY RATE
                $tax->setAmount( $shuttles->getJanMayRate() );
                $tax->setLabel("VAT (".$shuttles->getJanMayRate()."%)");
            }else if ( $year > $current_year ){
                // JUN DEC RATE     
                $tax->setAmount( $shuttles->getJunDecRate() );
                $tax->setLabel("VAT (".$shuttles->getJunDecRate()."%)");
            }  
        }
        if (strpos(strtolower($productType), 'water') !== false) {

            
            if ( $year == $current_year ){
                if( $month <= 6 ){
                    // JAN MAY RATE
                    $tax->setAmount( $water_taxi->getJanMayRate() );
                    $tax->setLabel("VAT (".$water_taxi->getJanMayRate()."%)");
                }else{
                    // JUN DEC RATE 
                    $tax->setAmount( $water_taxi->getJunDecRate() );
                    $tax->setLabel("VAT (".$water_taxi->getJunDecRate()."%)");
                }
            }else if ( $year < $current_year ){
                // JAN MAY RATE
                $tax->setAmount( $water_taxi->getJanMayRate() );
                $tax->setLabel("VAT (".$water_taxi->getJanMayRate()."%)");
            }else if ( $year > $current_year ){
                // JUN DEC RATE     
                $tax->setAmount( $water_taxi->getJunDecRate() );
                $tax->setLabel("VAT (".$water_taxi->getJunDecRate()."%)");
            }

        }
        if (strpos(strtolower($productType), 'jeep') !== false) {
          
            if ( $year == $current_year ){
                if( $month <= 6 ){
                    // JAN MAY RATE
                    $tax->setAmount( $jbj->getJanMayRate() );
                    $tax->setLabel("VAT (".$jbj->getJanMayRate()."%)");
                }else{
                    // JUN DEC RATE 
                    $tax->setAmount( $jbj->getJunDecRate() );
                    $tax->setLabel("VAT (".$jbj->getJunDecRate()."%)");
                }
            }else if ( $year < $current_year ){
                // JAN MAY RATE
                $tax->setAmount( $jbj->getJanMayRate() );
                $tax->setLabel("VAT (".$jbj->getJanMayRate()."%)");
            }else if ( $year > $current_year ){
                // JUN DEC RATE     
                $tax->setAmount( $jbj->getJunDecRate() );
                $tax->setLabel("VAT (".$jbj->getJunDecRate()."%)");
            }

        }
        if (strpos(strtolower($productType), 'jbj') !== false) {
        
            if ( $year == $current_year ){
                if( $month <= 6 ){
                    // JAN MAY RATE
                    $tax->setAmount( $jbj->getJanMayRate() );
                    $tax->setLabel("VAT (".$jbj->getJanMayRate()."%)");
                }else{
                    // JUN DEC RATE 
                    $tax->setAmount( $jbj->getJunDecRate() );
                    $tax->setLabel("VAT (".$jbj->getJunDecRate()."%)");
                }
            }else if ( $year < $current_year ){
                // JAN MAY RATE
                $tax->setAmount( $jbj->getJanMayRate() );
                $tax->setLabel("VAT (".$jbj->getJanMayRate()."%)");
            }else if ( $year > $current_year ){
                // JUN DEC RATE     
                $tax->setAmount( $jbj->getJunDecRate() );
                $tax->setLabel("VAT (".$jbj->getJunDecRate()."%)");
            }
        }
        if (strpos(strtolower($productType), 'flight') !== false) {
          
          
            if ( $year == $current_year ){
                if( $month <= 6 ){
                    // JAN MAY RATE
                    $tax->setAmount( $flights->getJanMayRate() );
                    $tax->setLabel("VAT (".$flights->getJanMayRate()."%)");
                }else{
                    // JUN DEC RATE 
                    $tax->setAmount( $flights->getJunDecRate() );
                    $tax->setLabel("VAT (".$flights->getJunDecRate()."%)");
                }
            }else if ( $year < $current_year ){
                // JAN MAY RATE
                $tax->setAmount( $flights->getJanMayRate() );
                $tax->setLabel("VAT (".$flights->getJanMayRate()."%)");
            }else if ( $year > $current_year ){
                // JUN DEC RATE     
                $tax->setAmount( $flights->getJunDecRate() );
                $tax->setLabel("VAT (".$flights->getJunDecRate()."%)");
            }
        }


        if ($this->priceIncludesTax()) {
            $productTax = new Money($productRackPrice);

            $productTax = $productTax->multiplyByTax($tax);
        } else {
            $productTax = new Money();
        }

        $totalRackPrice = clone $productRackPrice;
        $totalNetPrice = clone $productNetPrice;
        $totalTax = clone $productTax;

        $this->buildAreaSummary($areaFromInputInfo, false);
        $this->buildAreaSummary($areaToInputInfo);
        
        
        if ($areaFromInputInfo['type'] == Area::TYPE_AREA) {
            
            $applyAdditionalFeeFrom = false;
            $addFeeFrom = $em->getRepository(AdditionalFee::class)->findOneBy(['googlePlaceId' => $areaFromInputInfo['googlePlaceID']]);
            if ($addFeeFrom !== null) {
                $addFeeFromTypes = $addFeeFrom->getTypes(); // 1 = Transportation // 2 = Activity 
                if(in_array(1, $addFeeFromTypes)){
                    $applyAdditionalFeeFrom = true;
                }
            }

            if( $applyAdditionalFeeFrom ){

                $additionalFeeFromTax = new Money();
                $this->buildAdditionalFees($em, $areaFromInputInfo, $this->getAreaFrom(), $additionalFeeFromTax);
    
                if ($this->getAreaFrom()->hasAddFee()) {
                    $totalRackPrice = $totalRackPrice->add($this->getAreaFrom()->getAdditionalFee()->getRackPrice());
                    $totalNetPrice = $totalNetPrice->add($this->getAreaFrom()->getAdditionalFee()->getNetPrice());
                    if ($this->priceIncludesTax()) {
                        $totalTax = $totalTax->add($additionalFeeFromTax);
                    }
                }
            }
        }
        if ($areaToInputInfo['type'] == Area::TYPE_AREA) {
            
            
            $applyAdditionalFeeTo = false;
            $addFeeTo = $em->getRepository(AdditionalFee::class)->findOneBy(['googlePlaceId' => $areaToInputInfo['googlePlaceID']]);
            if ($addFeeTo !== null) {
                $addFeeToTypes = $addFeeTo->getTypes(); // 1 = Transportation // 2 = Activity 
                if(in_array(1, $addFeeToTypes)){
                    $applyAdditionalFeeTo = true;
                }
            }
            if( $areaFromInputInfo['googlePlaceID'] == $areaToInputInfo['googlePlaceID'] ){
                $applyAdditionalFeeTo = false;
            }
            if($applyAdditionalFeeTo){

                $additionalFeeToTax = new Money();
                $this->buildAdditionalFees($em, $areaToInputInfo, $this->getAreaTo(), $additionalFeeToTax);
    
                if ($this->getAreaTo()->hasAddFee()) {
                    $totalRackPrice = $totalRackPrice->add($this->getAreaTo()->getAdditionalFee()->getRackPrice());
                    $totalNetPrice = $totalNetPrice->add($this->getAreaTo()->getAdditionalFee()->getNetPrice());
                    if ($this->priceIncludesTax()) {
                        $totalTax = $totalTax->add($additionalFeeToTax);
                    }
                }
            }
        }

        if ($enabledAddons !== null) {
            [$totalAddonNetPrice, $totalAddonRackPrice, $totalAddonTax] = $this->buildAddonsSummary($enabledAddons, $product, $translator, $className = 'Addon');

            $totalRackPrice = $totalRackPrice->add($totalAddonRackPrice);
            $totalNetPrice = $totalNetPrice->add($totalAddonNetPrice);
            if ($this->priceIncludesTax()) {
                $totalTax = $totalTax->add($totalAddonTax);
            }
        }

        if ($enabledExtras !== null) {
            [$totalExtraNetPrice, $totalExtraRackPrice, $totalExtraTax] = $this->buildAddonsSummary($enabledExtras, $product, $translator, $className = 'Extra');

            $totalRackPrice = $totalRackPrice->add($totalExtraRackPrice);
            $totalNetPrice = $totalNetPrice->add($totalExtraNetPrice);
            if ($this->priceIncludesTax()) {
                $totalTax = $totalTax->add($totalExtraTax);
            }
        }
        if ($custom_services !== null) {
            foreach($custom_services as $custom_service){
                foreach($custom_service as $custom_service_label => $custom_service_value)
                $custom_service_price =  $custom_service_value;
                $custom_service_price =  new Money( $custom_service_price );
                $totalRackPrice = $totalRackPrice->add($custom_service_price);
                if ($this->priceIncludesTax()) {

                    $custom_service_tax = new Tax();
                    $custom_service_tax->setAmount("13.00");
                    $custom_service_tax->setLabel("VAT (13%)");
                    $custom_service_tax->setCreatedAt(new \DateTime());
                    $custom_service_tax->setModifiedAt(new \DateTime());
                    
                    
                    $customServiceTax = $custom_service_price->multiplyByTax( $custom_service_tax );
                    $totalTax = $totalTax->add($customServiceTax);

                }

            }
        }

        if ($this->pickUpTime !== null) {
            $currencySymbol = $translator->trans('currency.symbol');
            $currencyText = $translator->trans('currency.text');

            if ($product->getRegularPickEnabled() && $product->inRegularTimeRange($this->pickUpTime)) {
                $timeRack = new Money($product->getRegularPickRackPrice());
                $timeNet = new Money($product->getRegularPickNetPrice());
                $timeTax = $timeRack->multiplyByTax($product->getRegularPickTax());

                $label = "$currencySymbol$timeRack $currencyText";
                $this->setRegularTimeFee($timeRack, $timeNet, '', $label);
                $this->addRowBlock($timeRack, $timeNet, $translator->trans('sale.summary.time_fee.late_night'), $label);

                $totalRackPrice = $totalRackPrice->add($timeRack);
                $totalNetPrice = $totalNetPrice->add($timeNet);
                if ($this->priceIncludesTax()) {
                    $totalTax = $totalTax->add($timeTax);
                }
            }

            if ($product->getFlightPickEnabled() && $product->inFlightPickTimeRange($this->pickUpTime)) {
                $timeRack = new Money($product->getFlightPickRackPrice());
                $timeNet = new Money($product->getFlightPickNetPrice());
                $timeTax = $timeRack->multiplyByTax($product->getFlightPickTax());

                $label = "$currencySymbol$timeRack $currencyText";
                $this->setFlightPickTimeFee($timeRack, $timeNet, '', $label);
                $this->addRowBlock($timeRack, $timeNet, $translator->trans('sale.summary.time_fee.flight_pick'), $label);

                $totalRackPrice = $totalRackPrice->add($timeRack);
                $totalNetPrice = $totalNetPrice->add($timeNet);
                if ($this->priceIncludesTax()) {
                    $totalTax = $totalTax->add($timeTax);
                }
            }

            if ($product->getFlightDropEnabled() && $product->inFlightDropTimeRange($this->pickUpTime)) {
                $timeRack = new Money($product->getFlightDropRackPrice());
                $timeNet = new Money($product->getFlightDropNetPrice());
                $timeTax = $timeRack->multiplyByTax($product->getFlightDropTax());

                $label = "$currencySymbol$timeRack $currencyText";
                $this->setFlightDropTimeFee($timeRack, $timeNet, '', $label);
                $this->addRowBlock($timeRack, $timeNet, $translator->trans('sale.summary.time_fee.flight_drop'), $label);

                $totalRackPrice = $totalRackPrice->add($timeRack);
                $totalNetPrice = $totalNetPrice->add($timeNet);
                if ($this->priceIncludesTax()) {
                    $totalTax = $totalTax->add($timeTax);
                }
            }
        }
       
        $this->setSubtotal($totalRackPrice, $totalNetPrice);
        $this->setTotalTaxes($totalTax, $translator->trans('booking.tax'));
        $this->setGrandTotal($totalRackPrice->add($totalTax), $totalNetPrice, $translator->trans('sale.summary.total'));
    }

    /**
     * @param array $enabledAddons
     * @param Product $product
     * @param TranslatorInterface $translator
     * @param string $className 'Addon' or 'Extra'
     *
     * @return Money[] A 3-tuple of the net price, rack price and tax of all the enabled addons.
     */
    private function buildAddonsSummary(array $enabledAddons, Product $product, TranslatorInterface $translator, string $className): array {
        $totalAddonNetPrice     = new Money();
        $totalAddonRackPrice    = new Money();
        $totalAddonTax          = new Money();
        $quantities             = [];
        
        foreach ($enabledAddons as $addonID => $addonOptions) {
            if (!isset($addonOptions['enabled'])) { continue; }

            $addonRackPrice = new Money();
            $addonNetPrice = new Money();
            $addonTax = new Money();
            $addonRackPrices = [];
            $addonNetPrices = [];

            $addon = null;
            // ex : $addons = $product->getAddons()->getValues();
            global $kernel;
            $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
            if( $className == "Addon" ){
                $all_addons = $em->getRepository(Addon::class)->findAll( array('sortOrder' => 'ASC') );
            }else{
                $all_addons = $em->getRepository(Extra::class)->findAll( array('sortOrder' => 'ASC') );

            }
            $addons = [$product, 'get'.$className.'s']()->getValues();
            foreach ($all_addons as $possibleAddon) {
                if ($possibleAddon->getId() === (int)$addonID) {
                    $addon = $possibleAddon;
                    break;
                }
            }

            $addonRackPrice = new Money();
            $addonNetPrice = new Money();
            if( !is_null( $addon ) ){
                $options = $addon->getOptions();
                $optionsById = [];
                foreach ($options as $key => $option) {
                    $optionsById[$option->getId()] = $option;
                }

                foreach ($addonOptions as $addonOptionId => $addonOptionValue) {
                    if (!isset($addonOptionValue['enabled'])) { continue; }

                    $addonOption = $optionsById[$addonOptionId];
                    $addonOptionRackPrice = new Money($addonOption->getRackPrice());
                    $addonOptionNetPrice = new Money($addonOption->getNetPrice());

                    $quantity = $addonOptionValue['quantity'] ?? '1';

                    $addonOptionRackPrice = $addonOptionRackPrice->multiply($quantity);
                    $addonOptionNetPrice = $addonOptionNetPrice->multiply($quantity);

                    $addonRackPrice = $addonRackPrice->add($addonOptionRackPrice);
                    $addonNetPrice = $addonNetPrice->add($addonOptionNetPrice);

                    $quantities[$addonOptionValue['label']] = $quantity;

                    if ($addonOptionValue['label'] == Addon::ADDON_LABEL_ADULT) {
                        $addonRackPrices[Addon::ADDON_PRICE_DISPLAY_ADULT] = $addonOptionRackPrice;
                        $addonNetPrices[Addon::ADDON_PRICE_DISPLAY_ADULT] = $addonOptionNetPrice;
                    } elseif ($addonOptionValue['label'] == Addon::ADDON_LABEL_CHILD) {
                        $addonRackPrices[Addon::ADDON_PRICE_DISPLAY_CHILD] = $addonOptionRackPrice;
                        $addonNetPrices[Addon::ADDON_PRICE_DISPLAY_CHILD] = $addonOptionNetPrice;
                    } elseif ($addonOptionValue['label'] == Addon::ADDON_LABEL_EXTRA_TRANSPORTATION) {
                        $addonRackPrices[Addon::ADDON_PRICE_DISPLAY_EXTRA_TRANSPORTATION] = $addonOptionRackPrice;
                    } elseif ($addonOptionValue['label'] == Extra::EXTRA_LABEL_PRICE) {
                        $addonRackPrices[Extra::EXTRA_PRICE_DISPLAY_PRICE] = $addonOptionRackPrice;
                    }
                }

                $addonRackPrices[Addon::ADDON_PRICE_DISPLAY_TOTAL] = $addonRackPrice;
                $addonNetPrices[Addon::ADDON_PRICE_DISPLAY_TOTAL] = $addonNetPrice;

                // ex : $addonRackPriceWithDisount = App\Wicrew\AddonBundle\Entity\Addon::getPriceWithDiscount($addonRackPrice, $addon->getDiscountPercentage());
                $class = "App\Wicrew\AddonBundle\Entity\\".$className;
                $addonRackPriceWithDisount = $class::getPriceWithDiscount($addonRackPrice, $addon->getDiscountPercentage());
                $addonRackPrice = new Money($addonRackPriceWithDisount);

                $displayPrice = $translator->trans('currency.symbol') . "$addonRackPrice " . $translator->trans('currency.text');
                $addon_tax = new Tax();
                
                global $kernel;
                $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');

                $addons = $em->getRepository(TaxConfig::class)->findBy([
                    'label' => "addons"
                ]); 
                if ( count($addons) > 0 ){
                    $addons = $addons[0];
                }
                $pickUpDate     = new \DateTime();
                $month          = $pickUpDate->format('n');
                $year           = $pickUpDate->format('Y');
                $now            = new DateTime();
                $current_year   = $now->format("Y");
                if ( $year == $current_year ){
                    if( $month <= 6 ){
                        // JAN MAY RATE
                        $addon_tax->setAmount($addons->getJanMayRate());
                        $addon_tax->setLabel("VAT (".$addons->getJanMayRate()."%)");
                    }else{
                        // JUN DEC RATE 
                        $addon_tax->setAmount($addons->getJunDecRate());
                        $addon_tax->setLabel("VAT (".$addons->getJunDecRate()."%)");
                    }
                }else if ( $year < $current_year ){
                    // JAN MAY RATE
                    $addon_tax->setAmount($addons->getJanMayRate());
                    $addon_tax->setLabel("VAT (".$addons->getJanMayRate()."%)");
                }else if ( $year > $current_year ){
                    // JUN DEC RATE     
                    $addon_tax->setAmount($addons->getJunDecRate());
                    $addon_tax->setLabel("VAT (".$addons->getJunDecRate()."%)");
                }

                $addonTax = $addonRackPrice->multiplyByTax($addon_tax);

                $rowTitle = $this->getRowTitle($className, $quantities, $addonRackPrices, $addon, $translator);

                $addonTitle = $addon->getLabel();
                $index = $addon->getId();

                // ex : $this->addAddon($addon->getId(), $addonRackPrice, $addonNetPrice, $addon->getLabel(), $displayPrice, $quantities);
                [$this, 'add'.$className]($addon->getId(), $addonRackPrices, $addonNetPrices, $rowTitle, $displayPrice, $quantities, $addonTitle, $index);

                $totalAddonRackPrice = $totalAddonRackPrice->add($addonRackPrice);
                $totalAddonNetPrice = $totalAddonNetPrice->add($addonNetPrice);
                $totalAddonTax = $totalAddonTax->add($addonTax);
            }
        }

        return [
            $totalAddonNetPrice,
            $totalAddonRackPrice,
            $totalAddonTax
        ];
    }

    public function getRowTitle(string $className, ?array $quantities, ?array $addonRackPrices, $addon, TranslatorInterface $translator) {
        if( !is_null( $addon ) ){
            $rowTitle = $addon->getLabel() . "<br> <small>(</small>";
        
            if ($className == 'Addon') {
                $addonAdultQuantity = $quantities[Addon::ADDON_LABEL_ADULT] ?? 0;
                $addonAdultRackPrice = $addonRackPrices[Addon::ADDON_PRICE_DISPLAY_ADULT] ?? new Money('0.00');
                $addonChildQuantity = $quantities[Addon::ADDON_LABEL_CHILD] ?? 0;
                $addonChildRackPrice = $addonRackPrices[Addon::ADDON_PRICE_DISPLAY_CHILD] ?? new Money('0.00');
                $addonExtraTransportationQuantity = $quantities[Addon::ADDON_LABEL_EXTRA_TRANSPORTATION] ?? 0;
                $addonExtraTransportation = $addonRackPrices[Addon::ADDON_PRICE_DISPLAY_EXTRA_TRANSPORTATION] ?? new Money('0.00');

                // adult
                if ($addonAdultQuantity  != 0) {
                    $rowTitle .= "<small><em>" . $translator->trans('core.adults') . " x " .  $addonAdultQuantity . ": " . $translator->trans('currency.symbol') . $addonAdultRackPrice . " " . $translator->trans('currency.text') . "</em></small>";
                }

                if ($addonChildQuantity || $addonExtraTransportationQuantity) {
                    $rowTitle .= ",";
                }

                // child
                if ($addonChildQuantity  != 0) {
                    $rowTitle .= "<small> <em>" . $translator->trans('core.childs') . " x " .  $addonChildQuantity . ": " . $translator->trans('currency.symbol') . $addonChildRackPrice . " " . $translator->trans('currency.text') . "</em></small>";
                }

                if ($addonChildQuantity && $addonExtraTransportationQuantity) {
                    $rowTitle .= ",";
                }

                // extra transportation
                if ($addonExtraTransportationQuantity != 0) {
                    $rowTitle .= "<small> <em>" . $translator->trans('addon.extra_transportation') . ": " . $translator->trans('currency.symbol') . $addonExtraTransportation . " " . $translator->trans('currency.text') . "</em></small>";
                }
            } elseif ($className == 'Extra') {
                $addonQuantity = $quantities[Extra::EXTRA_LABEL_PRICE] ?? 0;
                $addonRackPrice = $addonRackPrices[Extra::EXTRA_PRICE_DISPLAY_PRICE] ?? new Money('0.00');

                if ($addonQuantity  != 0) {
                    $rowTitle .= "<small><em>" . $translator->trans('extra_price') . " x " .  $addonQuantity . ": " . $translator->trans('currency.symbol') . $addonRackPrice . " " . $translator->trans('currency.text') . "</em></small>";
                }
            }

            $rowTitle .= "<small>)</small>";

            return $rowTitle;
        }
    }

    public function toOrderItem(EntityManager $em, OrderItem $item): OrderItem {
        $item = parent::toOrderItem($em, $item);
        $item->setPickTime($this->getPickUpTime());
        $product = $item->getProduct();

        if ($this->regularTimeFee !== null) {
            $item->setRegularTimeFeeRack($this->regularTimeFee->getRackPrice());
            $item->setRegularTimeFeeNet($this->regularTimeFee->getNetPrice());
        }
        if ($this->flightPickTimeFee !== null) {
            $item->setFlightPickTimeFeeRack($this->flightPickTimeFee->getRackPrice());
            $item->setFlightPickTimeFeeNet($this->flightPickTimeFee->getNetPrice());
        }
        if ($this->flightDropTimeFee !== null) {
            $item->setFlightDropTimeFeeRack($this->flightDropTimeFee->getRackPrice());
            $item->setFlightDropTimeFeeNet($this->flightDropTimeFee->getNetPrice());
        }

        $item->setType(OrderItem::resolveProductType($product));
        if ($product->getTransportationType()->getId() === TransportationType::TYPE_AIRPLANE) {
            $item->setLuggageWeight($this->getLuggageWeight());
            $item->setPassengerWeight($this->getPassengerWeight());
        }

        // $item->setVehicle($this->getVehicle());

        return $item;
    }
}