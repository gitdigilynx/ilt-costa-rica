<?php


namespace App\Wicrew\SaleBundle\Service\Summary;


use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ActivityBundle\Entity\ActivityHasChild;
use App\Wicrew\AdditionalFeeBundle\Entity\AdditionalFee;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\CoreBundle\Entity\IBasePriceEntity;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\SaleBundle\Entity\OrderComboChild;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use DateTime;
use App\Wicrew\SaleBundle\Entity\Tax;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use Doctrine\ORM\EntityManager;
use Symfony\Contracts\Translation\TranslatorInterface;

use function PHPUnit\Framework\isNull;

class ActivitySummary extends PriceSummary {
    /* @var DateTime|null $tourTime */
    public $tourTime = null;

    public function getTourTime(): ?DateTime {
        return $this->tourTime;
    }

    private $childrenAges;

    /**
     * Get Children Ages
     * 
     * @return null|string 
     */
    public function getChildrenAges(): ?string 
    {
        return $this->childrenAges;
    }


    /**
     * @var array
     */
    private $customServices;

    public function getCustomServices(): array {
        return $this->customServices ?? [];

    }

    

    public function __construct(Activity $activity, EntityManager $em, TranslatorInterface $translator, int $activityType, int $adultCount, int $childCount, array $areaFromInputInfo, array $areaToInputInfo, ?DateTime $pickUpDate = null, ?DateTime $tourTime = null, ?array $custom_services = null, $childrenAges = null) {
        parent::__construct(
            $activity->getId(),
            $adultCount,
            $childCount,
            $pickUpDate,
            $activity->getAdultRackPrice(),
            $activity->getChildRackPrice(),
            $activity->getAdultNetPrice(),
            $activity->getChildNetPrice(),
            $activity->getTax(),
            $this->getCustomServices(),
            
        );
        $this->tourTime = $tourTime;
        $this->childrenAges = $childrenAges;
        $this->buildPriceSummary($activity, $em, $translator, $areaFromInputInfo, $areaToInputInfo, $activityType, $pickUpDate, $custom_services);
    }

    public function isActivity(): bool {
        return true;
    }

    public function getResolvedObject(EntityManager $em): BaseEntity {
        return $em->getRepository(Activity::class)->find($this->getIdentifier());
    }

    private function buildPriceSummary(Activity $activity, EntityManager $em, TranslatorInterface $translator, array $areaFromInputInfo, array $areaToInputInfo, int $activityType, $pickUpDate, ?array $custom_services = null ): void {
        $activityRackPrice  = new Money();
        $activityNetPrice   = new Money();

        $tours = $em->getRepository(TaxConfig::class)->findBy([
            'label' => "tours"
        ]); 
        if ( count($tours) > 0 ){
            $tours = $tours[0];
        } 
        if(!is_null( $pickUpDate )){

            $month          = $pickUpDate->format('n');
            $year           = $pickUpDate->format('Y');
            $now            = new DateTime();
            $current_year   = $now->format("Y");
            $totalTax = new Tax();
            if ( $year == $current_year ){
                if( $month <= 6 ){
                    // JAN MAY RATE
                    $totalTax->setAmount( $tours->getJanMayRate() );
                    $totalTax->setLabel("VAT (".$tours->getJanMayRate()."%)");
                }else{
                    // JUN DEC RATE 
                    $totalTax->setAmount( $tours->getJunDecRate() );
                    $totalTax->setLabel("VAT (".$tours->getJunDecRate()."%)");
                }
            }else if ( $year < $current_year ){
                // JAN MAY RATE
                $totalTax->setAmount( $tours->getJanMayRate() );
                $totalTax->setLabel("VAT (".$tours->getJanMayRate()."%)");
            }else if ( $year > $current_year ){
                // JUN DEC RATE     
                $totalTax->setAmount( $tours->getJunDecRate() );
                $totalTax->setLabel("VAT (".$tours->getJunDecRate()."%)");
            }
            
        }

        [ $activityRackPrice, $activityNetPrice, $totalTax ] = $this->getRackNetTaxTotal($activity, $this->getAdultCount(), $this->getChildCount(), $activityType, $pickUpDate, $em);

        // TODO: Re-implement.
        if ($activity->isTransportationRequired()) {
            $adultTransportRackPrice = (new Money($activity->getTransportAdultRackPrice()))->multiply($this->getAdultCount());
            $childTransportRackPrice = (new Money($activity->getTransportKidRackPrice()))->multiply($this->getChildCount());

            if ($this->priceIncludesTax()) {
                
                if(!is_null( $pickUpDate )){

                    $month          = $pickUpDate->format('n');
                    $year           = $pickUpDate->format('Y');
                    $now            = new DateTime();
                    $current_year   = $now->format("Y");
                    $tax = new Tax();
                    if ( $year == $current_year ){
                        if( $month <= 6 ){
                            // JAN MAY RATE
                            $tax->setAmount( $tours->getJanMayRate() );
                            $tax->setLabel("VAT (".$tours->getJanMayRate()."%)");
                        }else{
                            // JUN DEC RATE 
                            $tax->setAmount( $tours->getJunDecRate() );
                            $tax->setLabel("VAT (".$tours->getJunDecRate()."%)");
                        }
                    }else if ( $year < $current_year ){
                        // JAN MAY RATE
                        $tax->setAmount( $tours->getJanMayRate() );
                        $tax->setLabel("VAT (".$tours->getJanMayRate()."%)");
                    }else if ( $year > $current_year ){
                        // JUN DEC RATE     
                        $tax->setAmount( $tours->getJunDecRate() );
                        $tax->setLabel("VAT (".$tours->getJunDecRate()."%)");
                    }
                   
                }else{
                    $tax = $activity->getTransportationTax();
                }

                $totalTax = $adultTransportRackPrice->add($childTransportRackPrice)->multiplyByTax($tax);
            }
        }
       
        $this->setTitlePrice($activityRackPrice, $activityNetPrice, $activity->getName());

        $totalRackPrice = clone $activityRackPrice;
        $totalNetPrice = clone $activityNetPrice;

        $this->buildAreaSummary($areaFromInputInfo, false);
        $this->buildAreaSummary($areaToInputInfo);

        
        if ($areaFromInputInfo['type'] == Area::TYPE_AREA) {
            
            $applyAdditionalFeeFrom = false;
            $addFeeFrom = $em->getRepository(AdditionalFee::class)->findOneBy(['googlePlaceId' => $areaFromInputInfo['googlePlaceID']]);
            if ($addFeeFrom !== null) {
                $addFeeFromTypes = $addFeeFrom->getTypes(); // 1 = Transportation // 2 = Activity 
                if(in_array(2, $addFeeFromTypes)){
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
                if(in_array(2, $addFeeToTypes)){
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

        if(!is_null($custom_services)){
            foreach($custom_services as $custom_service){
                if(array_key_exists('name', $custom_service) && array_key_exists('value', $custom_service)  ){
                    $custom_service_name = $custom_service['name'];
                    $custom_service_rate = $custom_service['value'];

                    $totalRackPrice = $totalRackPrice->add( new Money($custom_service_rate) );
                    $totalTax       = $totalTax->add( new Money($custom_service_rate * 0.13) );
                }else{

                    foreach($custom_service as $custom_service_name => $custom_service_rate){
                        
                        $totalRackPrice = $totalRackPrice->add( new Money($custom_service_rate) );
                        $totalTax       = $totalTax->add( new Money($custom_service_rate * 0.13) );
                    }
                }
            }
        }
        $this->setSubtotal($totalRackPrice, $totalNetPrice);
        $this->setTotalTaxes($totalTax, $translator->trans('booking.tax'));
        $this->setGrandTotal($totalRackPrice->add($totalTax), $totalNetPrice, $translator->trans('sale.summary.total'));
    }

    /**
     * @param IBasePriceEntity $entity
     * @param int $adultCount
     * @param int $childCount
     *
     * @return Money[]
     */
    protected function getRackNetTaxTotal(IBasePriceEntity $entity, int $adultCount, int $childCount, int $activityType, $pickUpDate = null, $em = null): array {
        $totalRack = new Money();
        $totalNet = new Money();
        $totalTax = new Money();
        
        if ($entity->isCombo()) {
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

            $totalFixed = new Money($entity->getFixedNetPrice());

            $totalRack = $totalAdultRack->add($totalChildRack);

            $totalNet = $totalAdultNet->add($totalChildNet);
            $totalNet = $totalNet->add($totalFixed);

            if ($this->priceIncludesTax()) {
                $tax = new Tax();
                // ISKO DYNAMIC KERNA HA BAD MEIN, JAB TIME MILA.
                if(!is_null( $pickUpDate )){

                    $tours = $em->getRepository(TaxConfig::class)->findBy([
                        'label' => "tours"
                    ]); 
                    if ( count($tours) > 0 ){
                        $tours = $tours[0];
                    } 

                    $month          = $pickUpDate->format('n');
                    $year           = $pickUpDate->format('Y');
                    
                    $now            = new DateTime();
                    $current_year   = $now->format("Y");
                    if ( $year == $current_year ){
                        if( $month <= 6 ){
                            // JAN MAY RATE
                            $tax->setAmount( $tours->getJanMayRate() );
                            $tax->setLabel("VAT (".$tours->getJanMayRate()."%)");
                        }else{
                            // JUN DEC RATE 
                            $tax->setAmount( $tours->getJunDecRate() );
                            $tax->setLabel("VAT (".$tours->getJunDecRate()."%)");
                        }
                    }else if ( $year < $current_year ){
                        // JAN MAY RATE
                        $tax->setAmount( $tours->getJanMayRate() );
                        $tax->setLabel("VAT (".$tours->getJanMayRate()."%)");
                    }else if ( $year > $current_year ){
                        // JUN DEC RATE     
                        $tax->setAmount( $tours->getJunDecRate() );
                        $tax->setLabel("VAT (".$tours->getJunDecRate()."%)");
                    }
                   
                }
                $totalTax = $totalRack->multiplyByTax($tax);
            }
        } else {
            [ $totalRack, $totalNet, $totalTax ] = parent::getRackNetTaxTotal($entity, $adultCount, $childCount, $activityType);
        }
        $tax = new Tax();
        if(!is_null( $pickUpDate )){

            $tours = $em->getRepository(TaxConfig::class)->findBy([
                'label' => "tours"
            ]); 
            if ( count($tours) > 0 ){
                $tours = $tours[0];
            } 

            $month          = $pickUpDate->format('n');
            $year           = $pickUpDate->format('Y');

            $now            = new DateTime();
            $current_year   = $now->format("Y");
            if ( $year == $current_year ){
                if( $month <= 6 ){
                    // JAN MAY RATE
                    $tax->setAmount( $tours->getJanMayRate() );
                    $tax->setLabel("VAT (".$tours->getJanMayRate()."%)");
                }else{
                    // JUN DEC RATE 
                    $tax->setAmount( $tours->getJunDecRate() );
                    $tax->setLabel("VAT (".$tours->getJunDecRate()."%)");
                }
            }else if ( $year < $current_year ){
                // JAN MAY RATE
                $tax->setAmount( $tours->getJanMayRate() );
                $tax->setLabel("VAT (".$tours->getJanMayRate()."%)");
            }else if ( $year > $current_year ){
                // JUN DEC RATE     
                $tax->setAmount( $tours->getJunDecRate() );
                $tax->setLabel("VAT (".$tours->getJunDecRate()."%)");
            }
           
        }
        $totalTax = $totalRack->multiplyByTax($tax);
        return [ $totalRack, $totalNet, $totalTax ];
    }

    public function toOrderItem(EntityManager $em, OrderItem $item): OrderItem {
        $item = parent::toOrderItem($em, $item);
        $item->setTourTime($this->getTourTime());
        
        $tours = $em->getRepository(TaxConfig::class)->findBy([
            'label' => "tours"
        ]); 
        if ( count($tours) > 0 ){
            $tours = $tours[0];
        } 
        $pickUpDate     = $item->getPickDate();
        $month          = $pickUpDate->format('n');
        $year           = $pickUpDate->format('Y');

        $activity = $item->getActivity();
        if ($activity->isTransportationRequired()) {
            $item->setType(OrderItem::TYPE_ACTIVITY_TRANSPORTATION);
        } else {
            $item->setType(OrderItem::TYPE_ACTIVITY_REGULAR);
        }

        return $item;
    }
}
