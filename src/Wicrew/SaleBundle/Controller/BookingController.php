<?php

namespace App\Wicrew\SaleBundle\Controller;

use App\Entity\User;
use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\CoreBundle\Controller\Controller as Controller;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\CoreBundle\Service\Stripe;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\ProductBundle\Entity\AreaChildren;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\ProductBundle\Service\ProductService;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use App\Wicrew\SaleBundle\Service\Summary\ActivitySummary;
use App\Wicrew\SaleBundle\Service\Summary\PriceSummary;
use App\Wicrew\SaleBundle\Service\Summary\ProductSummary;
use App\Wicrew\SaleBundle\Service\DiscountService;
use App\Wicrew\SaleBundle\Entity\Discount;
use App\Wicrew\DateAvailability\Entity\HistoryLog;
use App\Wicrew\SaleBundle\Entity\DiscountItem;
use DateTime;
use Exception;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Stripe\ApiResponse;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\AddonBundle\Entity\Addon;


class BookingController extends Controller {
  
    /**
     * Renders the booking form.
     *
     * @Route(
     *     path = "booking", name = "start_booking",
     *     methods = { "POST", "GET" }
     * )
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function bookingAction(Request $request) {  
        $auth_checker = $this->get('security.authorization_checker');
        $isRoleAdmin = $auth_checker->isGranted('ROLE_EMPLOYEE');
        if (!$isRoleAdmin && $request->getSession()->has('orderID')) {
            $request->getSession()->remove('orderID');
        }

        $activityIDs = [];
        $jbjType = $this->getRequestDataNoThrow($request, "jbjType", null);
        if ($jbjType !== null) {
            $jbjType = (int)$jbjType;
        }
        $productIDs = $this->getRequestDataNoThrow($request, "productIDs", null);
       
        if ($productIDs === null) {
            $activityIDs = $this->getRequestDataNoThrow($request, "activityIDs", null);
            if ($activityIDs === null) {
                if ($jbjType === null) {
                    return $this->render('@WicrewSale/Booking/Base/form.booking.404.html.twig');
                }
            }
        }
        if( is_array( $activityIDs ) ){
            $count_activityIDs  = (int)count($activityIDs);

        }else{
            $count_activityIDs  = 0;
        }

        if( is_array( $productIDs ) ){
            $count_productIDs  = (int)count($productIDs);

        }else{
            $count_productIDs  = 0;
        }

        $count_totalBooking = $count_activityIDs + $count_productIDs;


        /* @var PriceSummary[] $summaries */
        $summaries  = [];
        $placeGG    = [];

        /* @var Activity[] $activities */
        $activities = [];
        /* @var Product[] $products */
        $products   = [];

        // Data from search fields.
        $adultCounts    = $this->getRequestDataNoThrow($request, 'adultCounts', [1 => 0]);
        $activityType   = $this->getRequestDataNoThrow($request, 'activityType', [1 => 0]);
        $childCounts    = $this->getRequestDataNoThrow($request, 'childCounts', [1 => 0]);
        $childrenAges   = $this->getRequestDataNoThrow($request, 'childrenAges', [1 => '']);
        $actiontype     = $this->getRequestDataNoThrow($request, 'actiontype');
      
        $dates          = $this->getRequestDataNoThrow($request, 'pickDates', array());
        foreach ($dates as $key => $date) {
            if (is_string($date)) {
                $dates[$key] = new DateTime($date);
            }
        }

        $pickAreas = $this->getRequestDataNoThrow($request, 'pickAreas', null);
        $dropAreas = $this->getRequestDataNoThrow($request, 'dropAreas', null);
         
        /* @var bool[] $reverseAreas */
        $reverseAreas           = [];
        $dl_airlineCompany      = [];
        $dl_flightNumber        = [];
        $dl_childAreas          = [];
        $dl_areaAddress         = [];
        $dl_activity            = [];
        $temp_products          = $this->getRequestDataNoThrow($request, "products", null);

        $bookingNumber = array_key_first($adultCounts);
        
        // ADDING AREA_FROM & AREA_TO ADDRESS TO THE PARAMETERS TO SHOW N BOOKING FORM IF USER EDIT/MODIFY BOOKING
        $dl_address = $this->getRequestDataNoThrow($request, "dl_address", null);
        if ( isset( $dl_address[$bookingNumber]["area_from"] ) ) {
            $dl_areaAddress[$bookingNumber]["area_from"] = $dl_address[$bookingNumber]["area_from"];
        }
        
        if ( isset( $dl_address[$bookingNumber]["area_to"] ) ) {
            $dl_areaAddress[$bookingNumber]["area_to"] = $dl_address[$bookingNumber]["area_to"];
        }
        $all_area_children   = $this->get('doctrine.orm.entity_manager')->getRepository(AreaChildren::class)->findAll( );
        $childAreas = [];
        foreach ($all_area_children as $child_area) {
            $childAreas[$child_area->getId()] = [
                'id'        => $child_area->getId(),
                'name'      => $child_area->getName(),
                'parent_id' => $child_area->getParentArea()->getId(),
            ];
        }
        
        if (!empty($productIDs) && $jbjType < 1) {
            $productRepo = $this->getDoctrine()->getManager()->getRepository(Product::class);
            $productUtils = $this->get('wicrew.product.utils');
            foreach ($productIDs as $pid) {
                /* @var Product $product */
                $product = $productRepo->find($pid);
                if ($product === null) {
                    throw new BadRequestHttpException("Product with ID '$pid' not found.");
                }
                $products[] = $product;
                
                [$areaFrom, $areaTo] = $this->generateProductAreaSummaries($product);
                
                // Figure out if the areas need to be reversed.
                $pickAreaID =  isset($pickAreas[$bookingNumber]) ? $pickAreas[$bookingNumber] : 0;
                if (strpos( $pickAreaID, '-' ) !== false) {
                    $child_area_id  = substr($pickAreaID, strpos($pickAreaID, '-') + 1);
                    $pickAreaID     = substr($pickAreaID, 0, strpos($pickAreaID, '-'));
                    $dl_childAreas[$bookingNumber]["child_area_from"][$child_area_id] = $childAreas[$child_area_id]['name'];
                 
                }


                // Figure out if the areas need to be reversed.
                $dropAreaID =  isset($dropAreas[$bookingNumber]) ? $dropAreas[$bookingNumber] : 0;
                if (strpos( $dropAreaID, '-' ) !== false) {
                    $child_area_id  = substr($dropAreaID, strpos($dropAreaID, '-') + 1);
                    $dropAreaID     = substr($dropAreaID, 0, strpos($dropAreaID, '-'));
                    $dl_childAreas[$bookingNumber]["child_area_to"][$child_area_id] = $childAreas[$child_area_id]['name'];
                
                }
  
                if ($pickAreaID > 0) {
                    $reverseAreas[$bookingNumber] = $product->getAreaTo()->getId() === $pickAreaID;
                } else {
                    $reverseAreas[$bookingNumber] = false;
                }
               
                if ($reverseAreas[$bookingNumber]) {
                    $buffer     = $areaFrom;
                    $areaFrom   = $areaTo;
                    $areaTo     = $buffer;
                }

                $pickID = $this->getRequestDataNoThrow($request, 'pickGooglePlaceID', null);
                if ($pickID !== null) {
                    $areaFrom['googlePlaceID'] = $pickID;
                }
                $dropID = $this->getRequestDataNoThrow($request, 'dropGooglePlaceID', null);
                if ($dropID !== null) {
                    $areaTo['googlePlaceID'] = $dropID;
                }

                $pickTime   = $this->getRequestDataNoThrow($request, 'pickTime', null);
                $addons     = $this->getRequestDataNoThrow($request, 'addons', null);
                $extras     = $this->getRequestDataNoThrow($request, 'extras', null);
               
                if (!isset($adultCounts[$bookingNumber])) {
                    $adultCounts[$bookingNumber] = $adultCounts[1];
                }
                if (!isset($childCounts[$bookingNumber])) {
                    $childCounts[$bookingNumber] = $childCounts[1];
                }
                // echo "bookingNumber: $bookingNumber<pre>".print_r($dates, true)."</pre>";exit;
                $summaries[$bookingNumber] = $productUtils->getPriceSummary($product,
                    $adultCounts[$bookingNumber],
                    $childCounts[$bookingNumber],
                    $areaFrom, $areaTo, $pickTime, $dates[$bookingNumber], $addons, $extras);

                $this->generateGoogleMapsDropdowns($placeGG, $bookingNumber);

                if (!isset($dates[$bookingNumber])) {
                    $dates[$bookingNumber] = new DateTime("now");
                }
                
                // ADDING AirlineCompany TO THE PARAMETERS TO SHOW N BOOKING FORM IF USER EDIT/MODIFY BOOKING
                if (isset($temp_products[$bookingNumber]["area_from"]["airlineCompany"])) {
                    $dl_airlineCompany[$bookingNumber] = $temp_products[$bookingNumber]["area_from"]["airlineCompany"];
                }elseif (isset($temp_products[$bookingNumber]["area_to"]["airlineCompany"])) {
                    $dl_airlineCompany[$bookingNumber] = $temp_products[$bookingNumber]["area_to"]["airlineCompany"];
                }

                // ADDING FLIGHT NUMBER TO THE PARAMETERS TO SHOW N BOOKING FORM IF USER EDIT/MODIFY BOOKING
                if (isset($temp_products[$bookingNumber]["area_from"]["flightNumber"])) {
                    $dl_flightNumber[$bookingNumber] = $temp_products[$bookingNumber]["area_from"]["flightNumber"];
                }elseif (isset($temp_products[$bookingNumber]["area_to"]["flightNumber"])) {
                    $dl_flightNumber[$bookingNumber] = $temp_products[$bookingNumber]["area_to"]["flightNumber"];
                }
              
                
                $bookingNumber++;
            } 
        } else if (!empty($activityIDs)) {
            $activityRepo = $this->getDoctrine()->getManager()->getRepository(Activity::class);
            $activityUtils = $this->get('wicrew.activity.utils');

            foreach ($activityIDs as $aid) {
                /* @var Activity $activity */
                $activity = $activityRepo->find($aid);
                if ($activity === null) {
                    throw new BadRequestHttpException("Activity with ID '$aid' not found.");
                }
                $activities[] = $activity;

                $pickArea = $this->getRequestDataNoThrow($request, 'pickArea', null);
                if ($pickArea !== null) {
                    $areaFrom = $this->generateAreaSummary($pickArea);
                    $areaTo = $this->generateAreaSummary($this->getRequestData($request, 'dropArea'));
                } else {
                    $areaFrom = $this->generateAreaSummary($activity->getArea());
                    $areaTo = $areaFrom;
                }

                if (!isset($adultCounts[$bookingNumber])) {
                    $adultCounts[$bookingNumber] = $adultCounts[1];
                }
                if (!isset($childCounts[$bookingNumber])) {
                    $childCounts[$bookingNumber] = $adultCounts[1];
                }                
                
                // $custom_services = $this->getRequestDataNoThrow($request, "custom_services", null);
                // echo ":5here<pre>".print_r($custom_services, true)."</pre>";

                $summaries[$bookingNumber] = $activityUtils->getPriceSummary($activity, $adultCounts[$bookingNumber], $childCounts[$bookingNumber], $areaFrom, $areaTo, NULL,  NULL, $activityType[1], null, $childrenAges[$bookingNumber]);
                $this->generateGoogleMapsDropdowns($placeGG, $bookingNumber);

                if (!isset($dates[$bookingNumber])) {
                    $dates[$bookingNumber] = null;
                }

                $dl_tourTime        = $this->getRequestDataNoThrow($request, 'dl_tourTime', null);
                $_dl_activityType   = $this->getRequestDataNoThrow($request, 'dl_activityType', null);
                if( !is_null( $dl_tourTime ) ){
                    $dl_activity[$bookingNumber]["tourTime"] = $dl_tourTime[$bookingNumber];
                }
                if( !is_null( $_dl_activityType ) ){
                    if( is_array( $_dl_activityType ) ){
                        if( $_dl_activityType[$bookingNumber] == "Group" ){
                            $dl_activity[$bookingNumber]["activityType"] = 1;
                        }else{
                            $dl_activity[$bookingNumber]["activityType"] = 2;
                        }
                    }
                }

                $bookingNumber++;
            }
        } else if ($jbjType > 0) {
            $productUtils = $this->get('wicrew.product.utils');
            $products = $productUtils->productsByTransportationType($jbjType);

            if ($actiontype != 'modify') {
                $bookingNumber = 1;
            }
            
            // $dates[$bookingNumber] = null;
            if (!isset($dates[$bookingNumber])) {
                $dates[$bookingNumber] = null;
            }
            
            $this->generateGoogleMapsDropdowns($placeGG, $bookingNumber);
        }
      
        $renderTarget = '@WicrewSale/Booking/Base/form.booking.html.twig';
        $productUtils = $this->get('wicrew.product.utils');
        $jbj_products = $productUtils->productsByTransportationType(3); // SHARED JBJ

        $form = $this->createFormBuilder()->getForm();
        $passedParameters = $this->getRequestDataNoThrow($request, 'renderTargetParameters', array());
        $pickTime = $this->getRequestDataNoThrow($request, "pickTime", null);
        
        // ADDING PICKUP TIME TO THE PARAMETERS TO SHOW N BOOKING FORM IF USER EDIT/MODIFY BOOKING
        if($pickTime == null){      
            $pickTime  = $this->getRequestDataNoThrow($request, "pickUpTime", null);
        }
        if( $pickTime != null && !is_object( $pickTime ) ){
            if( is_array( $pickTime ) && !isset($pickTime[$bookingNumber])){
                $pickTime = new DateTime( $pickTime[$bookingNumber -1] );        
            }else if( is_array( $pickTime ) ){
                $pickTime = new DateTime( $pickTime[$bookingNumber] );        
            }
        }

        $referrer = $request->headers->get('referer', null);
        if ($referrer === null) {
            $referrer = $this->getRequestDataNoThrow($request, 'referrer', null);
        }

        $parameters = array_merge($passedParameters,
            [
                'form'                  => $form->createView(),
                'summaries'             => $summaries,
                'pickDates'             => $dates,
                'pickupTime'            => $pickTime,
                'adultCounts'           => $adultCounts,
                'childCounts'           => $childCounts,
                'childrenAges'          => $childrenAges,
                'placeGG'               => $placeGG,
                'reverseAreas'          => $reverseAreas,
                'referrer'              => $referrer,
                'dl_flightNumber'       => $dl_flightNumber,
                'dl_airlineCompany'     => $dl_airlineCompany,
                'dl_areaAddress'        => $dl_areaAddress,
                'dl_childAreas'         => $dl_childAreas,
                'dl_activity'           => $dl_activity,
                'count_totalBooking'    => $count_totalBooking,
            ]
        );
        $addons     = $this->getRequestDataNoThrow($request, 'addons', null);
        $extras     = $this->getRequestDataNoThrow($request, 'extras', null);
       
        $parameters['enabledAddons']['extra']  = $extras;
        $parameters['enabledAddons']['addon']  = $addons;
      
        $pickArea = $this->getRequestDataNoThrow($request, 'pickArea', null);
        if ($pickArea !== null) {
            $parameters['areaFrom'] = $pickArea;
            $parameters['areaTo'] = $this->getRequestData($request, 'dropArea');
        }

        $orderItemID = $this->getRequestDataNoThrow($request, 'orderItemID', null);
        if ($orderItemID !== null) {
            $parameters['formAction'] = $this->generateUrl('edit_order_item', ['id' => $orderItemID]);
            $parameters['submitButtonText'] = $this->translator()->trans('edit.order.item');
            $parameters['edit_order_item'] = "edit_order_item";
            $orderItem = $this->getDoctrine()->getManager()->getRepository(OrderItem::class)->findOneBy(['id' => $orderItemID]);
            $parameters['orderItem_obj'] = $orderItem;
            $all_extras = $this->getDoctrine()->getManager()->getRepository(Extra::class)->findAll( array('sortOrder' => 'ASC') );
            $all_addons = $this->getDoctrine()->getManager()->getRepository(Addon::class)->findAll( array('sortOrder' => 'ASC') );
            $parameters['all_extras'] = $all_extras;
            $parameters['all_addons'] = $all_addons;


        } else {
            $session = $request->getSession();
            if ($session->has('orderID')) {
                $parameters['formAction'] = $this->generateUrl('add_order_item', ['id' => $session->get('orderID')]);
                $parameters['submitButtonText'] = $this->translator()->trans('order.add.item');
            }
        }
        if ($actiontype == 'modify') {
            $parameters['formAction'] = $this->generateUrl('sale_editcartitem', ['id' => $session->get('orderID')]);
            $parameters['submitButtonText'] = "Continue to cart";
        }
        if (!empty($products)) {
            if ($products[0]->getTransportationType()->getId() === TransportationType::TYPE_AIRPLANE) {
                $renderTarget = '@WicrewProduct/Product/Booking/booking.flight.html.twig';
            }  
            if ($jbjType > 0) {
                $parameters['areas'] = $this->getEM()->getRepository(Area::class)->findAll(); 
                usort($parameters['areas'], function($a, $b) { 
                    if ($a->getName() == $b->getName()) {
                        return 0;
                    }
                    return ($a->getName() < $b->getName()) ? -1 : 1;
                });
                if (!empty($productIDs)) {
                    foreach ($products as $product) {
                        if ($product->getId() == $productIDs[0]) {
                            $productUtils = $this->get('wicrew.product.utils'); 
                            $parameters['selectedProduct'] = $product;
                            [$areaFrom, $areaTo] = $this->generateProductAreaSummaries($product);
                            $parameters['summaries'][$bookingNumber] = $productUtils->getPriceSummary($product, $adultCounts[$bookingNumber], $childCounts[$bookingNumber], $areaFrom, $areaTo, $pickTime, $dates[$bookingNumber]);
                            $this->generateGoogleMapsDropdowns($parameters['placeGG'], $bookingNumber);
                            break;
                        }
                    }
                }
            }
            $parameters['products'] = $products;
        } else if (!empty($activities)) {
            $parameters['activities'] = $activities;
            $parameters['areas'] = $this->getEM()->getRepository(Area::class)->findAll();
            usort($parameters['areas'], function($a, $b) { 
                if ($a->getName() == $b->getName()) {
                    return 0;
                }
                return ($a->getName() < $b->getName()) ? -1 : 1;
            });
        }
       

        $pickID = $this->getRequestDataNoThrow($request, 'pickGooglePlaceID', null);
        if ($pickID !== null) {
            $parameters['dl_googlePlaceID']['area_from'] = $pickID;
        }
        $dropID = $this->getRequestDataNoThrow($request, 'dropGooglePlaceID', null);
        if ($dropID !== null) {
            $parameters['dl_googlePlaceID']['area_to'] = $dropID;
        }

        $areaFrom_id = $this->getRequestDataNoThrow($request, 'areaFrom', null);
        if ($areaFrom_id !== null) {
            $parameters['areaFrom_id']  = $areaFrom_id;
            $parameters['areaTo_id']    = $this->getRequestData($request, 'areaTo');
        }
        $prodid = $this->getRequestDataNoThrow($request, 'prodid', null);
        if ($prodid !== null) {
            $parameters['dl_prod_id']  = $product->getId();
        }
        $dl_activityType = $request->request->get("dl_activityType", null);
        if ($dl_activityType === null) {
            $dl_activityType = $request->query->get("dl_activityType", null);
        }
        if ($dl_activityType === null) {
            $dl_activityType = $request->attributes->get("dl_activityType", null);
        }
        $dl_actionType     = $this->getRequestDataNoThrow($request, 'dl_actionType', null);
        $parameters['dl_activityType']  = $dl_activityType;
        $parameters['jbj_products']     = $jbj_products;
        $parameters['dl_actionType']    = $dl_actionType;
        $parameters['all_activities']   = $this->getEM()->getRepository(Activity::Class)->findBy( array( 'visibility' => 1, ), array('sortOrder' => 'ASC') );   
        $parameters['all_extras']       = $this->getEM()->getRepository(Extra::Class)->findBy( array('addByDefault'  => true), array('sortOrder' => 'ASC') );   
        
        if ($dl_actionType == 'edit_item') {
          
            $session                        = $request->getSession();
            $utils                          = $this->get('wicrew.core.utils');
            $discountService                = new DiscountService($utils);
            $discounts                      = $session->get('discounts',  []);
            $summaries                      = $this->getCartBookings($session);
            $discountValues                 = $discountService->getDiscountValuesFromSummaries($summaries, $discounts);
            $parameters['discountValues']   = $discountValues;
            $parameters['all_extras']       = $this->getEM()->getRepository(Extra::Class)->findAll( array('sortOrder' => 'ASC') );
            $parameters['custom_services']  = $request->attributes->get("custom_services", null);
    
        }
        $parameters['count_totalBooking']  = $count_totalBooking;

        return $this->render($renderTarget, $parameters);
    }

    private function generateAreaSummary(Area $area): array {
        return [
            'id'              => $area->getId(),
            'type'            => $area->getType(),
            'name'            => $area->getName(),
            'address'         => '', // Rest of these are filled in by the user so leave them blank.
            'googlePlaceID'   => '',
            'flightNumber'    => '',
            'airlineCompany'  => ''
        ];
    }

    private function generateProductAreaSummaries(Product $product): array {
        return [
            $this->generateAreaSummary($product->getAreaFrom()),
            $this->generateAreaSummary($product->getAreaTo())
        ];
    }

    private function generateGoogleMapsDropdowns(array &$placeGG, int $bookingNumber): void {
        $coreUtils = $this->get('wicrew.core.utils');

        $placeGG[] = $coreUtils->generateGoogleMapsDropdown(
            "additionalFee_search_from_$bookingNumber", "placeID_from_$bookingNumber");

        $placeGG[] = $coreUtils->generateGoogleMapsDropdown(
            "additionalFee_search_to_$bookingNumber", "placeID_to_$bookingNumber");
    }

    /**
     * Renders the booking form.
     *
     * @Route(name = "update_summary", path = "booking/update_summary", methods = { "POST" })
     *
     * @param Request $request
     *
     * @return Response
     *
     * @noinspection PhpUnused
     */
    public function updateSummaryAction(Request $request) {
        $returnHTML = null;
        $parameters = [];
        try {
            $bookingNumber = (int)$this->getRequestData($request, 'bookingNumber');

            $adultCount = (int)$this->getRequestData($request, 'adultCount');
            $childCount = (int)$this->getRequestData($request, 'childCount');
            $pickupTime = $request->request->get('pickupTime');
            $pickupTime = (is_null($pickupTime)) ? $pickupTime : $this->get('wicrew.core.utils')->strToTimeNoDefault($pickupTime);
            $pickupDate = new DateTime($request->request->get('pickupDate'));
            $areaFrom = $this->getRequestData($request, 'areaFrom');
            $areaTo = $this->getRequestData($request, 'areaTo');
            $custom_services = $this->getRequestDataNoThrow($request, 'custom_services', null);
            /* @var ActivitySummary|ProductSummary $summary */
            $productID = $this->getRequestDataNoThrow($request, 'productID');
            if ($productID !== null) {
                $productID = (int)$productID;
                $productUtils = $this->container->get('wicrew.product.utils');

                /* @var Product $product */
                $product = $this->getEM()->getRepository(Product::class)->find($productID);

                $addons = $this->getRequestDataNoThrow($request, 'addons');
                $extras = $this->getRequestDataNoThrow($request, 'extras'); 
                $summary = $productUtils->getPriceSummary($product, $adultCount, $childCount, $areaFrom, $areaTo, $pickupTime, $pickupDate, $addons, $extras, $custom_services);
               
                if ($summary->isBookingTooLate()) {
                    $parameters['lateNotice'] = $this->translator()->trans('booking.late_notice');
                }
            } else {
                $activityID = (int)$this->getRequestData($request, 'activityID');
                $activityUtils = $this->container->get('wicrew.activity.utils');
                $activityType = (int)$this->getRequestData($request, 'activityType');

                /* @var Activity $activity */
                $activity = $this->getEM()->getRepository(Activity::class)->find($activityID);
                $tourTime = $pickupTime;
                $activityTimes = $activity->getTourTime();
                $parameters["activityTimes"] = $activityTimes;
                $summary = $activityUtils->getPriceSummary($activity, $adultCount, $childCount, $areaFrom, $areaTo, $pickupDate, $tourTime, $activityType, $custom_services );
            }
            // echo "<pre>".print_r($custom_services, true)."</pre>";
            $returnHTML = $this->renderTwigToString('@WicrewSale/Booking/Base/summary.section.html.twig', [
                'summary'           => $summary,
                'bookingNumber'     => $bookingNumber,
                'customServices'    => $custom_services
             ]);

            $newSubtotal = $summary->getSubtotalPrice()->getRackPrice();
            $newTax = $summary->getTotalTaxes()->getRackPrice();
        } catch (Throwable $e) {
            $this->logError($e);
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(array_merge($parameters,
            [
                'html' => $returnHTML,
                'subtotal' => $newSubtotal->toCents(),
                'taxes' => $newTax->toCents()
            ])
        );
    }

    /**
     * @param array $productInfo
     *
     * @return PriceSummary
     *
     * @throws Exception
     */
    private function getSummaryFromPOSTData(array $productInfo): PriceSummary {  
        $areaFrom = $productInfo['area_from'];
        $areaFromNameAndType = json_decode($areaFrom['nameAndType'], true);
        $areaFrom['name'] = $areaFromNameAndType['name'];
        $areaFrom['type'] = $areaFromNameAndType['type'];
        $areaFrom['id'] = $areaFromNameAndType['id'];
        unset($areaFrom['nameAndType']); 
        $areaTo = $productInfo['area_to'];
        $areaToNameAndType = json_decode($areaTo['nameAndType'], true);
        $areaTo['name'] = $areaToNameAndType['name'];
        $areaTo['type'] = $areaToNameAndType['type'];
        $areaTo['id'] = $areaToNameAndType['id'];
        unset($areaTo['nameAndType']);
        if(array_key_exists('custom_services', $productInfo )){
            $custom_services = $productInfo['custom_services'];
        }else{
            $custom_services = null;
        }
        $pickUpDate = new DateTime($productInfo['pickUpDate']);
        
        if ($productInfo['type'] === 'product') {
            $productRepo = $this->getDoctrine()->getManager()->getRepository(Product::class);
            /* @var Product $product */
            $product = $productRepo->find($productInfo['id']);
            
            $hasOwnDepartureTime =
                (
                    $product->getTransportationType()->isJeepBoatJeepType()
                    && $product->getTransportationType()->getId() != TransportationType::TYPE_JEEP_BOAT_JEEP_PRIVATE
                )
                || $product->getTransportationType()->getId() === TransportationType::TYPE_WATER_TAXI;
                
            if (!$hasOwnDepartureTime) {
                if (substr($productInfo['pickUpTime'], 0, 2) == '0:') {
                    $productInfo['pickUpTime'] = str_replace('0:', '12:', $productInfo['pickUpTime']);
                }
                $pickUpTime = new DateTime($productInfo['pickUpTime']);
            } else {
                $pickUpTime = $product->getDepartureTime();
            }
            /* @var ProductService $productUtils */
            $productUtils = $this->container->get('wicrew.product.utils');
            $addons = isset($productInfo['addons']) ? $productInfo['addons'] : null;
            $extras = isset($productInfo['extras']) ? $productInfo['extras'] : null;
            if( !array_key_exists( "address", $areaFrom ) )
                $areaFrom["address"] = "";
            
            if( !array_key_exists( "address", $areaTo ) )
                $areaTo["address"] = "";

            if( !array_key_exists( "googlePlaceID", $areaFrom ) )            
                $areaFrom["googlePlaceID"] = ""; 

            if( !array_key_exists( "googlePlaceID", $areaTo ) )
                $areaTo["googlePlaceID"] = ""; 

            $summary = $productUtils->getPriceSummary($product, $productInfo['adultCount'], $productInfo['childCount'], $areaFrom, $areaTo, $pickUpTime, $pickUpDate, $addons, $extras, $custom_services);

            $isConnectingFlight = (isset($productInfo['connecting_flight']) && $productInfo['connecting_flight'] == 'no') ? false : true;
            $summary->setIsConnectingFlight($isConnectingFlight);
 
            if (isset($productInfo['area_from']['airlineCompany'])) {
                $summary->setPickAirlineCompany($productInfo['area_from']['airlineCompany']);
            }
            if (isset($productInfo['area_from']['flightNumber'])) {
                $summary->setPickFlightNumber($productInfo['area_from']['flightNumber']);
            } 
            
            if (isset($productInfo['luggageWeight'])) {
                $summary->setLuggageWeight($productInfo['luggageWeight']);
            }
            if (isset($productInfo['luggageWeight'])) {
                $summary->setPassengerWeight($productInfo['passengerWeight']);
            }
        } else if ($productInfo['type'] === 'activity') {
            $activityRepo = $this->getDoctrine()->getManager()->getRepository(Activity::class);
            /* @var Activity $activity */
            $activity = $activityRepo->find($productInfo['id']);

            $tourTime = new DateTime($productInfo['tourTime']);
            $activityUtils = $this->container->get('wicrew.activity.utils');
            $childrenAges = isset($productInfo['children_ages']) ? implode(', ', $productInfo['children_ages']) : null;
            $summary = $activityUtils->getPriceSummary($activity, $productInfo['adultCount'], $productInfo['childCount'], $areaFrom, $areaTo, $pickUpDate, $tourTime, (int)$productInfo['activityType'], $custom_services, $childrenAges);
            
            if($productInfo['activityType'] == 1){

                $summary->setAdultRackPrice(new Money( (int)$activity->getGroupAdultRackPrice() * (int)$productInfo['adultCount']  ) );
                $summary->setAdultNetPrice(new Money((int)$activity->getGroupAdultNetPrice() * (int)$productInfo['adultCount'] ) );
                $summary->setChildRackPrice(new Money((int)$activity->getGroupKidRackPrice() * (int)$productInfo['childCount'] ) );
                $summary->setChildNetPrice(new Money((int)$activity->getGroupKidNetPrice() * (int)$productInfo['childCount'] ) );
                $summary->setActivityType("Group");
           
            }else{
                $summary->setActivityType("Private");
            }
        
        } else {
            throw new Exception('Unknown type given: ' . $productInfo['type']);
        }
 
        return $summary;
    }

    /**
     * @param array $productInfo
     *
     * @return PriceSummary
     *
     * @throws Exception
     */
    private function getSummaryFromOrderItem($orderItem): PriceSummary {  
        $productInfo = [];
        if ($orderItem->getProduct()) {
            $productInfo['id'] = $orderItem->getProduct()->getId();
        } else if ($orderItem->getActivity()) {
            $productInfo['id'] = $orderItem->getActivity()->getId();
        }
        $productInfo['type'] = $orderItem->getProduct() ? 'product': 'activity';
        $productInfo['pickUpDate'] = $orderItem->getPickDate()->format('Y-m-d');//'January 20, 2022';
        $pickArea = $orderItem->getPickArea();
        if ($pickArea)  {
            $productInfo['area_from'] = [
                'nameAndType' => '{"name":"'.$pickArea->getName().'","type":'.$pickArea->getType().',"id":'.$pickArea->getId().'}',
                'flightNumber' => $orderItem->getPickFlightNumber(),
                'airlineCompany' => $orderItem->getPickAirlineCompany(),
                'address' => $orderItem->getPickAddress(),
                'googlePlaceID' => $orderItem->getPickGooglePlaceID(),
            ];
        }
        $dropArea = $orderItem->getDropArea(); 
        if ($dropArea)  {
            $productInfo['area_to'] = [
                'nameAndType' => '{"name":"'.$dropArea->getName().'","type":'.$dropArea->getType().',"id":'.$dropArea->getId().'}',
                'flightNumber' => $orderItem->getDropFlightNumber(),
                'airlineCompany' => $orderItem->getDropAirlineCompany(),
                'address' => $orderItem->getDropAddress(),
                'googlePlaceID' => $orderItem->getDropGooglePlaceID(),
            ];
        }
        $productInfo['adultCount'] = $orderItem->getAdultCount();
        $productInfo['childCount'] = $orderItem->getChildCount();
        if( $orderItem->getPickTime() != null ){
            $productInfo['pickUpTime'] = $orderItem->getPickTime()->format('H:i');
        }else{
            $productInfo['pickUpTime'] = null;
        }

        $areaFrom = $productInfo['area_from'];
        $areaFromNameAndType = json_decode($areaFrom['nameAndType'], true);
        $areaFrom['name'] = $areaFromNameAndType['name'];
        $areaFrom['type'] = $areaFromNameAndType['type'];
        $areaFrom['id'] = $areaFromNameAndType['id'];
        unset($areaFrom['nameAndType']); 
        $areaTo = $productInfo['area_to'];
        $areaToNameAndType = json_decode($areaTo['nameAndType'], true);
        $areaTo['name'] = $areaToNameAndType['name'];
        $areaTo['type'] = $areaToNameAndType['type'];
        $areaTo['id'] = $areaToNameAndType['id'];
        unset($areaTo['nameAndType']);

        $pickUpDate = new DateTime($productInfo['pickUpDate']);
        
        if ($productInfo['type'] === 'product') {
            $productRepo = $this->getDoctrine()->getManager()->getRepository(Product::class);
            /* @var Product $product */
            $product = $productRepo->find($productInfo['id']);
            
            $hasOwnDepartureTime =
                (
                    $product->getTransportationType()->isJeepBoatJeepType()
                    && $product->getTransportationType()->getId() != TransportationType::TYPE_JEEP_BOAT_JEEP_PRIVATE
                )
                || $product->getTransportationType()->getId() === TransportationType::TYPE_WATER_TAXI;
                
            if (!$hasOwnDepartureTime) {
                $pickUpTime = new DateTime($productInfo['pickUpTime']);
            } else {
                $pickUpTime = $product->getDepartureTime();
            }
            /* @var ProductService $productUtils */
            $productUtils = $this->container->get('wicrew.product.utils');
            $addons = isset($productInfo['addons']) ? $productInfo['addons'] : null;
            $extras = isset($productInfo['extras']) ? $productInfo['extras'] : null;
            
            if( !is_array( $addons ) ){
                if ($orderItem->getAddons()->count() > 0) {
                    $addons = [];
                    /* @var OrderItemHasAddon $addon */
                    foreach ($orderItem->getAddons() as $addon) { 
                        $addons[$addon->getAddon()->getId()]['addon_adult'] = $addon->getAdultQuantity();
                        $addons[$addon->getAddon()->getId()]['addon_child'] = $addon->getChildQuantity();
                        $addons[$addon->getAddon()->getId()]['addon_extra_transportation'] = $addon->getExtraTransportationQuantity();
                        $qty = $addon->getAdultQuantity() + $addon->getChildQuantity() + $addon->getExtraTransportationQuantity();
                        $addons[$addon->getAddon()->getId()]['enabled'] = $qty > 0;
                    }
                    $parameters['addons'] = $addons;
                }
            }
            
            if( !is_array( $extras ) ){
                if ($orderItem->getExtras()->count() > 0) {
                    $extras = [];
                    /* @var OrderItemHasAddon $addon */
                    foreach ($orderItem->getExtras() as $extra) {  
                        $extras[$extra->getExtra()->getId()]['extra_price'] = $extra->getQuantity();
                        $extras[$extra->getExtra()->getId()]['enabled'] = $extra->getQuantity() >  0;
                    }
                    $parameters['extras'] = $extras;
                }
            }

            $summary = $productUtils->getPriceSummary($product, $productInfo['adultCount'], $productInfo['childCount'], $areaFrom, $areaTo, $pickUpTime, $pickUpDate, $addons, $extras);
          
            $isConnectingFlight = (isset($productInfo['connecting_flight']) && $productInfo['connecting_flight'] == 'no') ? false : true;
            $summary->setIsConnectingFlight($isConnectingFlight);
 
            if (isset($productInfo['area_from']['airlineCompany'])) {
                $summary->setPickAirlineCompany($productInfo['area_from']['airlineCompany']);
            }
            if (isset($productInfo['area_from']['flightNumber'])) {
                $summary->setPickFlightNumber($productInfo['area_from']['flightNumber']);
            } 
            
            if (isset($productInfo['luggageWeight'])) {
                $summary->setLuggageWeight($productInfo['luggageWeight']);
            }
            if (isset($productInfo['luggageWeight'])) {
                $summary->setPassengerWeight($productInfo['passengerWeight']);
            }
        } else if ($productInfo['type'] === 'activity') {
            $activityRepo = $this->getDoctrine()->getManager()->getRepository(Activity::class);
            /* @var Activity $activity */
            $activity = $activityRepo->find($productInfo['id']);

            $tourTime = $orderItem->getTourTime();
            $activityUtils = $this->container->get('wicrew.activity.utils');

            $activityType = $orderItem->getActivityType(); 
            if ( strpos( strtolower( $activityType ), 'group' ) !== false ) {
                $activityType = 1;
            }else{
                $activityType = 2;
            }
            
            $summary = $activityUtils->getPriceSummary($activity, $productInfo['adultCount'], $productInfo['childCount'], $areaFrom, $areaTo, $pickUpDate, $tourTime, $activityType);
        } else {
            throw new Exception('Unknown type given: ' . $productInfo['type']);
        }
 
        return $summary;
    }

    /**
     * @Route(path = "sale/cart/add", name = "sale_addtocart", methods = { "POST" })
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function addCartAction(Request $request) {
        $session = $request->getSession();

        try {
            $resultInput = $request->request->get('products');

            /* @var array $productInfo */
            foreach ($resultInput as $productInfo) {
                $summary = $this->getSummaryFromPOSTData($productInfo);
                if ($summary->getAdultCount() <= 0) {
                    throw new Exception("Adult count must be greater than 0.");
                }
                if ($summary->getChildCount() < 0) {
                    throw new Exception("Child count must be greater than or equal to 0.");
                }

                $this->addToCart($session, $summary);
            }

            $referrer = $this->getRequestDataNoThrow($request, 'backToReferrer', null);
            if ($referrer !== null) {
                return $this->redirect('/');
            }
        } catch (Throwable $e) {
            $this->logError($e);
            dump($e);die;
            return $this->returnExceptionResponse($e);
        }

        return $this->redirectToRoute('sale_cart');
    }

    /**
     * @param SessionInterface $session
     * @param PriceSummary $summary
     *
     * @throws Exception
     */
    private function addToCart(SessionInterface $session, PriceSummary $summary): void {
        $sessionCart = $session->get('cart', null);
        if ($sessionCart !== null) {
            if (!$session->has('cartIndex')) {
                throw new Exception("Cart index not found.");
            }
            $index = $session->get('cartIndex') + 1;
            $session->set('cartIndex', $index);
            $sessionCart[$index] = $summary;
            $session->set('cart', $sessionCart);
        } else {
            $index = 1;
            $session->set('cart', [$index => $summary]);
            $session->set('cartIndex', $index);
        }
    }

    /**
     * @Route(path = "sale/cart/edit", name = "sale_editcartitem", methods = { "POST" })
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function editCartAction(Request $request) {
        $session = $request->getSession();

        try {
            $resultInput        = $request->request->get('products');

            /* @var array $productInfo */
            foreach ( $resultInput as $index => $productInfo ) {
                $summary = $this->getSummaryFromPOSTData($productInfo);
                if ($summary->getAdultCount() <= 0) {
                    throw new Exception("Adult count must be greater than 0.");
                }
                if ($summary->getChildCount() < 0) {
                    throw new Exception("Child count must be greater than or equal to 0.");
                }
                $sessionCart = $session->get('cart', null);
                if ($sessionCart !== null) {
                    if (!$session->has('cartIndex')) {
                        throw new Exception("Cart index not found.");
                    }
                    // $index = $session->get('cartIndex');
                    $session->set('cartIndex', $index);
                    $sessionCart[$index] = $summary;
                    $session->set('cart', $sessionCart);
                } else {
                    $index = 1;
                    $session->set('cart', [$index => $summary]);
                    $session->set('cartIndex', $index);
                }
            
            }

            $referrer = $this->getRequestDataNoThrow($request, 'backToReferrer', null);
            if ($referrer !== null) {
                return $this->redirect('/');
            }
        } catch (Throwable $e) {
            $this->logError($e);
            dump($e);die;
            return $this->returnExceptionResponse($e);
        }

        return $this->redirectToRoute('sale_cart');
    }

    /**
     * @param SessionInterface $session
     * @param Discount $discount
     *
     * @throws Exception
     */
    private function addDiscountToCart(SessionInterface $session, Discount $discount): void {
        $sessionDiscounts = $session->get('discounts',  []);

        $index = $discount->getId();
        $discount_added = array_key_exists($index, $sessionDiscounts);

        if (!$discount_added) {
            $sessionDiscounts[$index] = $discount;
            $session->set('discounts', $sessionDiscounts);
        }
    }

    /**
     * @param SessionInterface $session
     * @param int $discount_id
     *
     * @throws Exception
     */
    private function removeDiscountToCart(SessionInterface $session, int $discount_id): void {
        $sessionDiscounts = $session->get('discounts',  []);

        $index = $discount_id;

        if (isset($sessionDiscounts[$index])) {
            unset($sessionDiscounts[$index]);
            $session->set('discounts', $sessionDiscounts);
        }
    }

    /**
     * @param SessionInterface $session
     *
     * @return PriceSummary[]
     */
    private function getCartBookings(SessionInterface $session): array {
        /* @var PriceSummary[] $bookings */
        $bookings = [];

        if ($session->has('cart')) {
            /* @var array $sessionCart */
            $sessionCart = $session->get('cart');
            /* @var PriceSummary $summary */
            foreach ($sessionCart as $index => $summary) {
                $bookings[$index] = $summary;
            }
        }

        return $bookings;
    }

    /**
     * @Route(path = "sale/cart", name = "sale_cart")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cartAction(Request $request) {

        $session = $request->getSession();
        $sessionCart = $session->get('cart');

        try {
            $bookings = $this->getCartBookings($session);
        } catch (Exception $e) {
            $this->logError($e);
            var_dump($e->getMessage());
            die();
        }

        $parameters = [];

        $session = $request->getSession();
        $utils = $this->get('wicrew.core.utils');
        $discountService = new DiscountService($utils);
        $discounts = $session->get('discounts',  []);
        $summaries = $this->getCartBookings($session);
        $discountValues = $discountService->getDiscountValuesFromSummaries($summaries, $discounts);
        $parameters['discountValues'] = $discountValues;

        if (!empty($bookings)) {
            $parameters['bookings'] = $bookings;
        }

        return $this->render('WicrewSaleBundle:Checkout:cart.html.twig', $parameters);
    }

    /**
     * @Route(path = "sale/cart/deleteitem", name = "sale_cart_delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cartItemDeleteAction(Request $request) {
        $session = $request->getSession();
        try {
            $bookingNumber = (int)$this->getRequestData($request, 'bookingNumber');
            $sessionCart = $session->get('cart');

            if ($sessionCart !== null) {
                $sessionCart = $session->get('cart');
                if (isset($sessionCart[$bookingNumber])) {
                    unset($sessionCart[$bookingNumber]);
                    $session->set('cart', $sessionCart);
                } else {
                    throw new Exception("Booking with ID '$bookingNumber' not found.");
                }
            } else {
                throw new Exception("Cart is empty.");
            }
        } catch (Exception $e) {
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse();
    }

    /**
     * @Route(path = "sale/cart/deleteitemaddon", name = "sale_cart_delete_addon")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cartItemDeleteAddonAction(Request $request) {
        $session = $request->getSession();
        try {
            $bookingNumber = (int)$this->getRequestData($request, 'bookingNumber');
            $addonindex = (int)$this->getRequestData($request, 'addonindex');
            $addontype = (string)$this->getRequestData($request, 'addontype');

            $sessionCart = $session->get('cart');

            if ($sessionCart !== null) {
                $sessionCart = $session->get('cart');
                if (isset($sessionCart[$bookingNumber])) {
                    if ($addontype == 'addon') {
                        $booking = $sessionCart[$bookingNumber];
                        $addons = $booking->getAddons();
                        if (isset($addons[$addonindex])) {
                            unset($addons[$addonindex]);
                            $booking->setAddons($addons);
                            $sessionCart[$bookingNumber] = $booking;
                            $session->set('cart', $sessionCart);
                        } 
                    } else if ($addontype == 'extra') {
                        $booking = $sessionCart[$bookingNumber];
                        $addons = $booking->getExtras();
                        if (isset($addons[$addonindex])) {
                            unset($addons[$addonindex]);
                            $booking->setExtras($addons);
                            $sessionCart[$bookingNumber] = $booking;
                            $session->set('cart', $sessionCart);
                        } 
                    }
                } else {
                    throw new Exception("Booking with ID '$bookingNumber' not found.");
                }
            } else {
                throw new Exception("Cart is empty.");
            }
        } catch (Exception $e) {
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse();
    }

    /**
     * @Route(path = "sale/cart/updateitemnote", name = "sale_cart_udpate_note")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cartItemUpdateNoteAction(Request $request) {
        $session = $request->getSession();
        try { 
            $notes = $this->getRequestData($request, 'notes'); 
            
            $sessionCart = $session->get('cart');

            if ($sessionCart !== null) {
                $sessionCart = $session->get('cart'); 
                foreach ($notes as $bookingNumber => $note) {
                    if (isset($sessionCart[$bookingNumber])) {
                        $booking = $sessionCart[$bookingNumber];
                        $booking->setCustomerNotes($note);
                        $sessionCart[$bookingNumber] = $booking;
                    } else {
                        throw new Exception("Booking with ID '$bookingNumber' not found.");
                    } 
                }
                $session->set('cart', $sessionCart); 
            } else {
                throw new Exception("Cart is empty.");
            }
        } catch (Exception $e) {
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse();
    }

    /**
     * @Route(path = "sale/checkout", name = "sale_checkout")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkoutAction(Request $request) {
        $session = $request->getSession();
        $partners = null;
        $paymentTypes = [];
        $lateNotice = '';

        try {
            $auth_checker = $this->get('security.authorization_checker');
            $isRoleAdmin = $auth_checker->isGranted('ROLE_EMPLOYEE');

            $form = $this->createFormBuilder()->getForm();
 
            $bookings = $this->getRequestDataNoThrow($request, "bookings", $this->getCartBookings($session));
            $isPaymentMail = $this->getRequestDataNoThrow($request, "isPaymentMail", false);
            $itemsCustomServices = $this->getRequestDataNoThrow($request, "itemsCustomServices", null);
            $order = $this->getRequestDataNoThrow($request, "order", null);
            $isPaid = $this->getRequestDataNoThrow($request, "isPaid", false);
          
            foreach ($bookings as $summary) {
                if ($summary instanceof ProductSummary && $summary->isBookingTooLate()) {
                    $lateNotice = $this->translator()->trans('booking.late_notice');
                    break;
                }
            }

            $partners = $this->getEM()->getRepository(Partner::class)->findBy(
                ['type' => [Partner::TYPE_DRIVER, Partner::TYPE_AFFILIATE, Partner::TYPE_SUPPLIER, Partner::TYPE_TRAVEL_AGENT, Partner::TYPE_PARTNER]], ['bizName' => 'ASC']);
            if ($isRoleAdmin) {
                $translator = $this->get('translator');
                $paymentTypes = [
                    Order::PAYMENT_TYPE_CREDIT_CARD => $translator->trans('sale.payment.type.creditcard'),
                    Order::PAYMENT_TYPE_CASH => $translator->trans('sale.payment.type.cash'),
                    //Order::PAYMENT_TYPE_CHEQUE => $translator->trans('sale.payment.type.cheque'),
                ];
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            die();
        }

        $utils = $this->get('wicrew.core.utils');
        $discountService = new DiscountService($utils);
        $discounts = $session->get('discounts',  []); 
        $summaries = $this->getRequestDataNoThrow($request, "bookings", $this->getCartBookings($session));
        $discountValues = $discountService->getDiscountValuesFromSummaries($summaries, $discounts);
        
        $parameters = [
            'bookings' => $bookings,
            'lateNotice' => $lateNotice,
            'form' => $form->createView(),
            'isLoggedInAdmin' => $isRoleAdmin,
            'partners' => $partners,
            'paymentTypes' => $paymentTypes,
            'discountValues' => $discountValues,
            'isPaymentMail' => $isPaymentMail, 
            'isPaid' => $isPaid,
            'itemsCustomServices' => $itemsCustomServices,
        ];

        if ($order) {
            $parameters['order'] = $order;
        }else{
            $parameters['order'] = null;
        }

        return $this->render('WicrewSaleBundle:Checkout:checkout.html.twig', $parameters);
    }


    /**
     * @Route(path = "sale/checkout/save", name = "sale_checkout_save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkoutSaveAction(Request $request) {
        $session = $request->getSession();

        
        $translator = $this->get('translator');
        /* @var User|null $user */
        $user = null;
        $em = $this->getEM();

        try {
            $em->beginTransaction();

            $formInput = $request->request->get('form');
            $checkoutPostData = $formInput['checkout'];

            $stripeToken = $request->request->get('stripeToken');
            $orderUtils = $this->container->get('wicrew.order.utils');
            $token = $this->get('security.token_storage')->getToken();
            $user = $token->getUser() instanceof User ? $token->getUser() : null;

            $order = new Order();

            /* @var Partner $supplier */
            $supplier = null;
            if (isset($checkoutPostData['partner'])) {
                $supplier = $em->getRepository(Partner::class)->findOneBy(['id' => $checkoutPostData['partner']]);
                if (!$supplier) {
                    throw $this->createNotFoundException('Partner not found.');
                }
                $order->setSupplier($supplier);
            }
            $quote = isset($checkoutPostData['checkout_as_quote']) ? $checkoutPostData['checkout_as_quote'] : false;
            if( !$quote && isset($checkoutPostData['checkout_as_quote_withoutPaymentLink']) ){
                $quote = $checkoutPostData['checkout_as_quote_withoutPaymentLink'];
            }
            $paymentType = isset($checkoutPostData['payment_type']) ? $checkoutPostData['payment_type'] : null;
            if ($paymentType !== null) {
                $order->setPaymentType((int)$paymentType);
            } else {
                $order->setPaymentType(Order::PAYMENT_TYPE_CREDIT_CARD);
            }
            $customer_email = $checkoutPostData['contact']['email'];
            if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address!");
            }
            $order->setFirstName(trim($checkoutPostData['contact']['firstName']));
            $order->setLastName(trim($checkoutPostData['contact']['lastName']));
            $order->setEmail(trim($checkoutPostData['contact']['email']));
            $order->setTel(trim($checkoutPostData['contact']['tel']));
            $order->setWhatsapp(trim($checkoutPostData['contact']['whatsapp']));
            $order->setCountry(trim($checkoutPostData['contact']['country']));
            $order->setUser($user);
            $order->setQuote($quote);
            $order->setArchiveStatus(0); 
            // $order->setNotes($checkoutPostData['contact']['notes']);
            // $order->setCustomerNotes($checkoutPostData['contact']['notes']);
            

            $grandTotalRackPrice = new Money();
            $grandTotalRackPriceToCharge = new Money();
            $bookings = $this->getCartBookings($session);
            
            /* @var OrderItem[] $bookingsCharged */
            $bookingsCharged = array();
            if( count($bookings) > 0 ){
                foreach ($bookings as $summary) {
                    $orderItem = $summary->toOrderItem($this->getEM(), new OrderItem());
                    if ($order->getSupplier() !== null) {
                        $orderItem->setSupplierCommission($order->getSupplier()->getCommission());
                    }
                   
                    if ($summary instanceof ActivitySummary) {
                        $orderItem->setChildrenAges($summary->getChildrenAges());
                    }

                    $orderItem->setAdultRackPrice($summary->getAdultRackPrice());
                    $orderItem->setChildRackPrice($summary->getChildRackPrice());
                    $orderItem->setAdultNetPrice($summary->getAdultNetPrice());
                    $orderItem->setChildNetPrice($summary->getChildNetPrice());
    
                    
    
    
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
    
                    $tours = $em->getRepository(TaxConfig::class)->findBy([
                        'label' => "tours"
                    ]); 
                    if ( count($tours) > 0 ){
                        $tours = $tours[0];
                    } 
    
                    $pickUpDate     = $orderItem->getPickDate();
                    $month          = $pickUpDate->format('n');
                    $year           = $pickUpDate->format('Y');
                    $now            = new DateTime();
                    $current_year   = $now->format("Y");
                    
                
                    if($orderItem->getType() == "activity_regular" ){
                        $orderItem->setActivityType($summary->getActivityType());
    
                    }
                    
                    if($orderItem->getType() == "private_shuttle" ){
                      
                        if ( $year == $current_year ){
                            if( $month <= 6 ){
                                $orderItem->setTaxValue($shuttles->getJanMayRate());
                            }else{
                                $orderItem->setTaxValue($shuttles->getJunDecRate());
                            }
                        }else if ( $year < $current_year ){

                            $orderItem->setTaxValue($shuttles->getJanMayRate());
                            
                        }else if ( $year > $current_year ){

                            $orderItem->setTaxValue($shuttles->getJunDecRate());
                        }
        
    
                        $taxRate    = (int)$orderItem->getTaxValue();
                        $rackPrice  = (int)$orderItem->getTitleRackPrice();
                        $totalTax   = ($taxRate / 100) * $rackPrice;
                        $totalTax   = number_format((float)$totalTax, 2, '.', '');
                        $orderItem->setTotalTax("$totalTax");
                        
                    }else if($orderItem->getType() == "private_jbj" ){
                       
                       
                        if ( $year == $current_year ){
                            if( $month <= 6 ){
                                $orderItem->setTaxValue($jbj->getJanMayRate());
                            }else{
                                $orderItem->setTaxValue($jbj->getJunDecRate());
                            }
                        }else if ( $year < $current_year ){

                            $orderItem->setTaxValue($jbj->getJanMayRate());
                            
                        }else if ( $year > $current_year ){

                            $orderItem->setTaxValue($jbj->getJunDecRate());
                        }
    
                        $taxRate    = (int)$orderItem->getTaxValue();
                        $rackPrice  = (int)$orderItem->getTitleRackPrice();
                        $totalTax   = ($taxRate / 100) * $rackPrice;
                        $totalTax   = number_format((float)$totalTax, 2, '.', '');
                        $orderItem->setTotalTax("$totalTax");
    
                    }else if($orderItem->getType() == "shared_jbj" ){
                   
                        if ( $year == $current_year ){
                            if( $month <= 6 ){
                                $orderItem->setTaxValue($jbj->getJanMayRate());
                            }else{
                                $orderItem->setTaxValue($jbj->getJunDecRate());
                            }
                        }else if ( $year < $current_year ){

                            $orderItem->setTaxValue($jbj->getJanMayRate());
                            
                        }else if ( $year > $current_year ){

                            $orderItem->setTaxValue($jbj->getJunDecRate());
                        }
    
                        $taxRate    = (int)$orderItem->getTaxValue();
                        $rackPrice  = (int)$orderItem->getTitleRackPrice();
                        $totalTax   = ($taxRate / 100) * $rackPrice;
                        $totalTax   = number_format((float)$totalTax, 2, '.', '');
                        $orderItem->setTotalTax("$totalTax");
    
                    }else if($orderItem->getType() == "riding_jbj" ){
                        if ( $year == $current_year ){
                            if( $month <= 6 ){
                                $orderItem->setTaxValue($jbj->getJanMayRate());
                            }else{
                                $orderItem->setTaxValue($jbj->getJunDecRate());
                            }
                        }else if ( $year < $current_year ){

                            $orderItem->setTaxValue($jbj->getJanMayRate());
                            
                        }else if ( $year > $current_year ){

                            $orderItem->setTaxValue($jbj->getJunDecRate());
                        }
    
                        $taxRate    = (int)$orderItem->getTaxValue();
                        $rackPrice  = (int)$orderItem->getTitleRackPrice();
                        $totalTax   = ($taxRate / 100) * $rackPrice;
                        $totalTax   = number_format((float)$totalTax, 2, '.', '');
                        $orderItem->setTotalTax("$totalTax");
                        
                    }else if($orderItem->getType() == "water_taxi" ){
                      
                      
                        if ( $year == $current_year ){
                            if( $month <= 6 ){
                                $orderItem->setTaxValue($water_taxi->getJanMayRate());
                            }else{
                                $orderItem->setTaxValue($water_taxi->getJunDecRate());
                            }
                        }else if ( $year < $current_year ){

                            $orderItem->setTaxValue($water_taxi->getJanMayRate());
                            
                        }else if ( $year > $current_year ){

                            $orderItem->setTaxValue($water_taxi->getJunDecRate());
                        }
    
                        $taxRate    = (int)$orderItem->getTaxValue();
                        $rackPrice  = (int)$orderItem->getTitleRackPrice();
                        $totalTax   = ($taxRate / 100) * $rackPrice;
                        $totalTax   = number_format((float)$totalTax, 2, '.', '');
                        $orderItem->setTotalTax("$totalTax");
                        
                    }else if (strpos(strtolower($orderItem->getType()), "flight") !== false) {
    
                        
                          
                        if ( $year == $current_year ){
                            if( $month <= 6 ){
                                $orderItem->setTaxValue($flights->getJanMayRate());
                            }else{
                                $orderItem->setTaxValue($flights->getJunDecRate());
                            }
                        }else if ( $year < $current_year ){

                            $orderItem->setTaxValue($flights->getJanMayRate());
                            
                        }else if ( $year > $current_year ){

                            $orderItem->setTaxValue($flights->getJunDecRate());
                        }      
                    
                    }else if (strpos(strtolower($orderItem->getType()), "activity") !== false) {
    
                        if ( $year == $current_year ){
                            if( $month <= 6 ){
                                $orderItem->setTaxValue($tours->getJanMayRate());
                            }else{
                                $orderItem->setTaxValue($tours->getJunDecRate());
                            }
                        }else if ( $year < $current_year ){

                            $orderItem->setTaxValue($tours->getJanMayRate());
                            
                        }else if ( $year > $current_year ){

                            $orderItem->setTaxValue($tours->getJunDecRate());
                        }  
                    }
                    $orderItem->setArchiveStatus(0); 

                    if ( !isset( $checkoutPostData['checkout_as_quote_withoutPaymentLink'] ) ){
                        $this->getEM()->persist($orderItem);
                    }

                    $order->addItem($orderItem);
    
                    $grandTotalRackPrice = $grandTotalRackPrice->add($summary->getGrandTotal()->getRackPrice());
                   
                    if ($summary instanceof ProductSummary && $summary->isBookingTooLate()) {
                        continue;
                    }
                    $grandTotalRackPriceToCharge = $grandTotalRackPriceToCharge->add($summary->getGrandTotal()->getRackPrice());
    
                    $bookingsCharged[] = $orderItem;
                }
    
                $discounts = $session->get('discounts',  []);
                $utils = $this->get('wicrew.core.utils');
                $discountService = new DiscountService($utils);
                $discountValues = $discountService->getDiscountValuesFromSummaries($bookings, $discounts);
    
                foreach ($discountValues as $key => $discount) {
                    $discount0 = $discount['discount'];
                    $discountObj = $this->getEM()->getRepository(Discount::class)->findOneById($discount0->getId());
                    $grandTotalRackPriceToCharge = $grandTotalRackPriceToCharge->subtract($discount['discountRack']);
                    $grandTotalRackPrice = $grandTotalRackPrice->subtract($discount['discountRack']);
    
                    $discountItem =  new DiscountItem();
                    $discountItem->setName($discountObj->getName());
                    $discountItem->setCode($discountObj->getCode());
                    $discountItem->setTypeDiscount($discountObj->getTypeDiscount());
                    $discountItem->setUsedNumber($discountObj->getUsedNumber());
                    $discountItem->setQuantityPerUser($discountObj->getQuantityPerUser());
                    $discountItem->setDescription($discountObj->getDescription());
                    $discountItem->setReductionAmount($discountObj->getReductionAmount());
                    $discountItem->setReductionPercentage($discountObj->getReductionPercentage());
                    if ( !isset( $checkoutPostData['checkout_as_quote_withoutPaymentLink'] ) ){
                        $this->getEM()->persist($discountItem);
                    }
                    $order->addDiscountItem($discountItem);
    
                    $discountObj->setUsedNumber($discountObj->getUsedNumber()+1);
                    if ( !isset( $checkoutPostData['checkout_as_quote_withoutPaymentLink'] ) ){
                        $this->getEM()->persist($discountObj);
                    }
                }
                
                $orderHistory = $orderUtils->createOrderHistory_InitialTotal($order, $user, $grandTotalRackPrice);
                $order->addHistory($orderHistory);
    
                $order->setStatus(Order::STATUS_PENDING);
    
                if (
                    (isset($checkoutPostData['checkout_as_quote']) && $checkoutPostData['checkout_as_quote'])
                    || isset($checkoutPostData['checkout_as_backend'])
                    || (isset($checkoutPostData['checkout_as_quote_withoutPaymentLink']) && $checkoutPostData['checkout_as_quote_withoutPaymentLink'])
                    || (isset($checkoutPostData['payment_type']) && $checkoutPostData['payment_type'] == Order::PAYMENT_TYPE_CASH)
                ) {
                    $stripePayment = false;
                } else {
                    $stripePayment = true;
                }
                if ( !isset( $checkoutPostData['checkout_as_quote_withoutPaymentLink'] ) ){
                    $em->persist($order);
                    $em->flush();
                }

                $cardBrand = null;
                $last4Digits = null;
                /* @var array $customerCreationResponse */
                $customerCreationResponse = null; 
                if ($stripePayment) {
                    /* @var Stripe $stripeUtils */
                    $stripeUtils = $this->container->get('wicrew.core.stripe');
                    $customerCreationResponse = $stripeUtils->createCustomer($stripeToken, $checkoutPostData['contact']['email']);
    
                    if ($customerCreationResponse['status'] !== 'success') {
                        throw new Exception($customerCreationResponse['message']);
                    }
    
                    $order->setStripeCustomerId($customerCreationResponse['data']->id);
                    if ( floatval($grandTotalRackPriceToCharge->__toString()) > 0 ) {
                        $stripeCharge = $stripeUtils->createCharge($customerCreationResponse['data'], $grandTotalRackPriceToCharge, 'Payment on invoice #RJ' . $order->getId() );
                        /* @var ApiResponse $stripeResponse */
                        $stripeResponse = $stripeCharge['data']->getLastResponse();
                        
                        // if ($stripeResponse->code == Stripe::CHARGE_STATUS_SUCCESS && $grandTotalRackPrice->equals($grandTotalRackPriceToCharge)) {
                        if ($stripeResponse->code == Stripe::CHARGE_STATUS_SUCCESS ) {

                            $order->setStatus(Order::STATUS_PAID);
                            foreach ($bookingsCharged as $orderItem) {
                                $orderItem->setStatus(OrderItem::STATUS_PAID);
                            }
    
                            $cardBrand = $stripeResponse->json['source']['brand'];
                            $last4Digits = $stripeResponse->json['source']['last4'];
    
                            $order->setCardBrand($cardBrand);
                            $order->setLast4Digits($last4Digits);
                        }else{
                            $this->logError($stripeResponse);
                            $this->logError('grandTotalRackPrice: '.$grandTotalRackPrice);
                            $this->logError('grandTotalRackPriceToCharge: '.$grandTotalRackPriceToCharge);

                            throw new Exception('Card has beeen declined!');
                        }
                        $orderHistory = $orderUtils->createOrderHistory_StripeCharge(
                            $order,
                            $user,
                            $stripeResponse->json['id'],
                            $stripeResponse->json['description'],
                            $stripeCharge['status'],
                            $grandTotalRackPriceToCharge
                        );
                        $order->addHistory($orderHistory);
                    }
                } else if ($order->getPaymentType() === Order::PAYMENT_TYPE_CASH) {
                    $order->setStatus(Order::STATUS_PAID);
                    /* @var OrderItem $orderItem */
                    foreach ($order->getItems() as $orderItem) {
                        $orderItem->setStatus(OrderItem::STATUS_PAID);
                    }
                } 
                
                if ( !isset( $checkoutPostData['checkout_as_quote_withoutPaymentLink'] ) ){
                    $em->persist($order);
                    $em->flush();
                    foreach ($order->getItems() as $orderItem) {
                        $orderItem->setOrder($order);
                        $orderItem->setOrderId($order->getId());
                        $em->persist($orderItem);
                        $em->flush();
                    }
                    $em->persist($order);
                    $em->flush();
                }
           
                // send confirmation email
                $mailerService = $this->container->get('wicrew.core.mailer');
    
                $message = new \Swift_Message();
                $media_path = $this->get('kernel')->getProjectDir() . '/public/';
                $logoSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/logo.png'));
                $tripadvisorSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/tripadvisor.png'));
                $facebookSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/facebook.png'));
                $wopitaSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/wopita.png'));
                $imageItemSrcs = [];
                $orderUtils = $this->get('wicrew.order.utils');
                foreach ($order->getItems() as $key => $item) {
                    $imageItemSrcs[$order->getId()] = $orderUtils->getOrderItemImage($item);
                }
    
                $site_url       = $this->container->get('router')->getContext()->getBaseUrl();
                $utils          = $this->get('wicrew.core.utils');
                $q              = json_encode(['oid' => $order->getId()]);
                $data           = $utils->encrypt_decrypt($q, 'encrypt');
                $payment_link   = $site_url . $this->generateUrl('order_mailpayment_transaction') . '?q=' . $data;
                
                 
                
                if ( ( isset( $checkoutPostData['checkout_as_quote_withoutPaymentLink'] ) && $checkoutPostData['checkout_as_quote_withoutPaymentLink'] ) ) {
                    $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.quoteWithoutPaymentLink.html.twig', [
                        'order'             => $order,
                        'cardBrand'         => $cardBrand,
                        'last4Digits'       => $last4Digits,
                        'logoSrc'           => $logoSrc,
                        'tripadvisorSrc'    => $tripadvisorSrc,
                        'facebookSrc'       => $facebookSrc,
                        'wopitaSrc'         => $wopitaSrc,
                        'imageItemSrcs'     => $imageItemSrcs,
                        'payment_link'      => $payment_link,
                    ]);
                    $body_pdf = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.quoteWithoutPaymentLink.html.twig', [
                        'order'             => $order,
                        'cardBrand'         => $cardBrand,
                        'last4Digits'       => $last4Digits,
                        'isUsedInPdf'       => true,
                        'payment_link'      => $payment_link,
                    ]);
                }else{
                    $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                        'order'             => $order,
                        'cardBrand'         => $cardBrand,
                        'last4Digits'       => $last4Digits,
                        'logoSrc'           => $logoSrc,
                        'tripadvisorSrc'    => $tripadvisorSrc,
                        'facebookSrc'       => $facebookSrc,
                        'wopitaSrc'         => $wopitaSrc,
                        'imageItemSrcs'     => $imageItemSrcs,
                        'payment_link'      => $payment_link,
                    ]);
                    $body_pdf = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                        'order'             => $order,
                        'cardBrand'         => $cardBrand,
                        'last4Digits'       => $last4Digits,
                        'isUsedInPdf'       => true,
                        'payment_link'      => $payment_link,
        
                    ]);
                }
                $subject_trans_key = $order->getQuote() ? 'email.confirm.order.quote' : 'email.confirm.order';
                $subject = $translator->trans($subject_trans_key);
                $siteEmail = $this->container->getParameter('system_email');
                $customerEmail = $checkoutPostData['contact']['email'];
    
                $pdfOutputPath = $this->get('kernel')->getProjectDir() . '/var/log/confirm.pdf';
                $this->get('knp_snappy.pdf')->generateFromHtml($body_pdf, $pdfOutputPath, [
                    'margin-right' => '0mm',
                    'margin-left' => '0mm'
                ], true);
                $pdfAttachment = [
                    'path' => $pdfOutputPath,
                    'filename' => 'confirmation.pdf'
                ];
    
                //send to admin
                $mailerService->send([
                    'from' => $siteEmail,
                    'to' => $siteEmail,
                    'replyTo' => $customerEmail,
                    'subject' => $subject,
                    'body' => $body,
                    'attachments' => [ $pdfAttachment ]
                ]);
                //send to customer
                $isActivity = false;
                foreach ($order->getItems() as $orderItem) {
                    $hasActivity = $orderItem->getActivity();
                    if ($hasActivity) {
                        $isActivity = true;;
                        break;
                    }
                } 
                if (!$isActivity) {
                    $mailerService->send([
                        'from' => $siteEmail,
                        'to' => $customerEmail,
                        'subject' => $subject,
                        'body' => $body,
                        'attachments' => [ $pdfAttachment ]
                    ]);
                }
    
                //send to partner
                if (isset($checkoutPostData['partner'])) {
                    $mailerService->send([
                        'from' => $siteEmail,
                        'to' => $supplier->getEmail(),
                        'subject' => $subject,
                        'body' => $body,
                        'attachments' => [ $pdfAttachment ]
                    ]);
                }
    
                $session->remove('cart');
                $session->remove('cartIndex');
    
                $session->remove('discounts');
    
                $em->commit(); 
            }else{  
                throw new Exception("No bookings found.");
            }
        } catch (Throwable $e) {
            $this->logError($e);

            if( isset($stripeUtils) && isset($stripeResponse->json['id']) ){
                $stripeUtils->createRefund($stripeResponse->json['id'], $grandTotalRackPriceToCharge);
            }
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }
        return $this->returnSuccessResponse();
    }

    /**
     * @Route(path = "sale/checkout/mailpayment", name = "sale_checkout_mailpayment")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkoutMailPaymentAction(Request $request) {
        $session = $request->getSession();

        $em = $this->getEM();
        $translator = $this->get('translator');
        /* @var User|null $user */
        $user = null;

        try {
            $em->beginTransaction();

            $formInput                      = $request->request->get('form');
            $checkoutPostData               = $formInput['checkout'];
            $stripeToken                    = $request->request->get('stripeToken');
            $orderUtils                     = $this->container->get('wicrew.order.utils');
            $token                          = $this->get('security.token_storage')->getToken();
            $user                           = $token->getUser() instanceof User ? $token->getUser() : null;
            $orderID                        = $checkoutPostData['oid'];
            $order                          = $em->getRepository(Order::class)->find($orderID);
            $grandTotalRackPrice            = new Money( $order->getOrderHistoryTotal()['totalDue'] );
            $grandTotalRackPriceToCharge    = new Money( $order->getOrderHistoryTotal()['totalDue'] );                
            $checkoutPostData               = []; 
            $stripePayment                  = true;
            $user                           = $order->getUser();
            $checkoutPostData['contact']['email'] = $order->getEmail();

            $cardBrand = null;
            $last4Digits = null;
            /* @var array $customerCreationResponse */
            $customerCreationResponse = null; 
            if ($stripePayment) {
                /* @var Stripe $stripeUtils */
                $stripeUtils = $this->container->get('wicrew.core.stripe');
                $customerCreationResponse = $stripeUtils->createCustomer($stripeToken, $checkoutPostData['contact']['email']);

                if ($customerCreationResponse['status'] !== 'success') {
                    throw new Exception($customerCreationResponse['message']);
                }

                $order->setStripeCustomerId($customerCreationResponse['data']->id);
               
                if ($grandTotalRackPriceToCharge->greaterThanStr('0')) {
                    $stripeCharge = $stripeUtils->createCharge($customerCreationResponse['data'], $grandTotalRackPriceToCharge, 'Payment on invoice #RJ' . $orderID);
                    /* @var ApiResponse $stripeResponse */
                    $stripeResponse = $stripeCharge['data']->getLastResponse();
                    if ($stripeResponse->code == Stripe::CHARGE_STATUS_SUCCESS && $grandTotalRackPrice->equals($grandTotalRackPriceToCharge)) {
                        $order->setStatus(1); // PAID
                        foreach ($order->getItems() as $orderItem) { 
                            $orderItem->setStatus(1); // PAID
                        }

                        $cardBrand = $stripeResponse->json['source']['brand'];
                        $last4Digits = $stripeResponse->json['source']['last4'];
                    }
                    $orderHistory = $orderUtils->createOrderHistory_StripeCharge(
                        $order,
                        $user,
                        $stripeResponse->json['id'],
                        $stripeResponse->json['description'],
                        $stripeCharge['status'],
                        $grandTotalRackPriceToCharge
                    );
                    $order->addHistory($orderHistory);
                }
            } 

            $em->persist($order);
            $em->flush();

            // send confirmation email
            $mailerService = $this->container->get('wicrew.core.mailer');

            $message = new \Swift_Message();
            $media_path = $this->get('kernel')->getProjectDir() . '/public/';
            $logoSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/logo.png'));
            $tripadvisorSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/tripadvisor.png'));
            $facebookSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/facebook.png'));
            $wopitaSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/wopita.png'));
            $imageItemSrcs = [];
            $orderUtils = $this->get('wicrew.order.utils');
            foreach ($order->getItems() as $key => $item) {
                $imageItemSrcs[$order->getId()] = $orderUtils->getOrderItemImage($item);
            }
            
            $site_url       = $this->container->get('router')->getContext()->getBaseUrl();
            $utils          = $this->get('wicrew.core.utils');
            $q              = json_encode(['oid' => $order->getId()]);
            $data           = $utils->encrypt_decrypt($q, 'encrypt');
            $payment_link   = $site_url . $this->generateUrl('order_mailpayment_transaction') . '?q=' . $data;
            
             
            $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                'order'             => $order,
                'cardBrand'         => $cardBrand,
                'last4Digits'       => $last4Digits,
                'logoSrc'           => $logoSrc,
                'tripadvisorSrc'    => $tripadvisorSrc,
                'facebookSrc'       => $facebookSrc,
                'wopitaSrc'         => $wopitaSrc,
                'imageItemSrcs'     => $imageItemSrcs,
                'payment_link'      => $payment_link,
            ]);

            $body_pdf = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                'order'         => $order,
                'cardBrand'     => $cardBrand,
                'last4Digits'   => $last4Digits,
                'isUsedInPdf'   => true,
                'payment_link'  => $payment_link,
            ]);

            $subject_trans_key = $order->getQuote() ? 'email.confirm.order.quote' : 'email.confirm.order';
            $subject = $translator->trans($subject_trans_key);
            $siteEmail = $this->container->getParameter('system_email');
            $customerEmail = $checkoutPostData['contact']['email'];

            $pdfOutputPath = $this->get('kernel')->getProjectDir() . '/var/log/confirm.pdf';
            $this->get('knp_snappy.pdf')->generateFromHtml($body_pdf, $pdfOutputPath, [
                'margin-right' => '0mm',
                'margin-left' => '0mm'
            ], true);
            $pdfAttachment = [
                'path' => $pdfOutputPath,
                'filename' => 'confirmation.pdf'
            ];
  
            //send to admin
            $mailerService->send([
                'from' => $siteEmail,
                'to' => $siteEmail,
                'replyTo' => $customerEmail,
                'subject' => $subject,
                'body' => $body,
                'attachments' => [ $pdfAttachment ]
            ]);
            //send to customer
            $isActivity = false;
            foreach ($order->getItems() as $orderItem) {
                $hasActivity = $orderItem->getActivity();
                if ($hasActivity) {
                    $isActivity = true;;
                    break;
                }
            } 
            if (!$isActivity) {
                $mailerService->send([
                    'from' => $siteEmail,
                    'to' => $customerEmail,
                    'subject' => $subject,
                    'body' => $body,
                    'attachments' => [ $pdfAttachment ]
                ]);
            }

            //send to partner
            if (isset($checkoutPostData['partner'])) {
                $mailerService->send([
                    'from' => $siteEmail,
                    'to' => $supplier->getEmail(),
                    'subject' => $subject,
                    'body' => $body,
                    'attachments' => [ $pdfAttachment ]
                ]);
            }
 
            $em->commit(); 
        } catch (Throwable $e) {
            $this->logError($e);
            $em->rollback();dump($e);die;
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse();
    }

    /**
     * Renders the booking form.
     *
     * @Route(
     *     path = "sale/rope/{id}",
     *     name = "e",
     *     methods = { "GET" }
     * )
     */
    public function uhOrder(int $id) {
        $order = $this->getEM()->getRepository(Order::class)->findOneBy(['id' => $id]);
        $html = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
            'order' => $order,
            'cardBrand' => 'VISA',
            'last4Digits' => 1234
        ]);

        $sire_url = "https://{$_SERVER['HTTP_HOST']}";

       $html = preg_replace('/(<img.*?src=")(.*">)/',"$1$sire_url$2",$html);

        $outputPath = $this->get('kernel')->getProjectDir() . '/var/log/confirm.pdf';
        $this->get('knp_snappy.pdf')->generateFromHtml($html, $outputPath, [], true);

        return new Response($html);
    }

    /**
     * Renders the booking form.
     *
     * @Route(
     *     path = "sale/edit/{id}",
     *     name = "edit_order_item",
     *     methods = { "POST" }
     * )
     *
     * @param Request $request
     * @param int $id
     *
     * @return Response
     * @throws Exception
     */
    public function editOrderItemAction(Request $request, int $id) {
        $auth_checker = $this->get('security.authorization_checker');
        $isRoleAdmin = $auth_checker->isGranted('ROLE_EMPLOYEE');
        if (!$isRoleAdmin) {
            throw new Exception("No admin role found.");
        }
       
        $em = $this->getDoctrine()->getManager();
        $orderUtils = $this->container->get('wicrew.order.utils');
       
        $orderItem = $em->getRepository(OrderItem::class)->findOneBy(['id' => $id]);
        $orderID = $orderItem->getOrder()->getId();
        $oldItem_addons = $orderItem->getAddons();
      
        try {
            $em->beginTransaction();
           
            $bookingNumber  = 1;
            $resultInput    = $request->request->get('products'); 
            $summary        = $this->getSummaryFromPOSTData($resultInput[$bookingNumber]); 
            $summaries[$bookingNumber] = $this->getSummaryFromPOSTData($resultInput[$bookingNumber]);

            $oldItem        = clone $orderItem;
            $newItem        = $summary->toOrderItem($this->getEM(), $orderItem);
            $order          = $newItem->getOrder();
            // $em->persist($order);
            // $em->flush();
            $token = $this->get('security.token_storage')->getToken();
            /* @var User $user */
            $user = $token->getUser();
            $orderHistory = $orderUtils->createOrderHistory_UpdatedItem($newItem, $oldItem, $user, null, $oldItem_addons);
            $newItem->addHistory($orderHistory);
            
            $order->addHistory($orderHistory);


            if ($order->getOrderHistoryTotal()['totalDue']->greaterThanStr('0')) {
                $newItem->setStatus(0);
                $order->setStatus(Order::STATUS_PENDING);
                    
            }else{
                foreach ($order->getItems() as $item) {
                    if( $item->getStatus() != 2 ){ // CHECK IF ITEM STATUS IS CANCELLED DO NOTHING 
                        $item->setStatus(1); // PAID
                    }              
                }
                $newItem->setStatus(1);
                $order->setStatus(Order::STATUS_PAID);
            }

            $old_item_pickDate           = ( $oldItem->getPickDate() != null ) ? $oldItem->getPickDate()->format('d/m/Y') : 'Null'; 
            $old_item_pickTime           = ( $oldItem->getPickTime() != null ) ? $oldItem->getPickTime()->format('H:i:s') : 'Null'; 
            $old_item_pickTimeTransport  = ( $oldItem->getPickTimeTransport() != null ) ? $oldItem->getPickTimeTransport()->format('H:i:s') : 'Null'; 
            $old_item_pickArea           = $oldItem->getPickArea()->getName();
            $old_item_pickAddress        = $oldItem->getPickAddress();
            $old_item_dropArea           = $oldItem->getDropArea()->getName();
            $old_item_dropAddress        = $oldItem->getDropAddress();
            $old_item_adultCount         = $oldItem->getAdultCount();
            $old_item_childCount         = $oldItem->getChildCount();
            $old_item_addons             = $oldItem->getAddons();
            $old_item_extras             = $oldItem->getExtras();

            $new_item_pickDate           = ( $newItem->getPickDate() != null ) ? $newItem->getPickDate()->format('d/m/Y') : 'Null'; 
            $new_item_pickTime           = ( $newItem->getPickTime() != null ) ? $newItem->getPickTime()->format('H:i:s') : 'Null'; 
            $new_item_pickTimeTransport  = ( $newItem->getPickTimeTransport() != null ) ? $newItem->getPickTimeTransport()->format('H:i:s') : 'Null'; 
            $new_item_pickArea           = $newItem->getPickArea()->getName();
            $new_item_pickAddress        = $newItem->getPickAddress();
            $new_item_dropArea           = $newItem->getDropArea()->getName();
            $new_item_dropAddress        = $newItem->getDropAddress();
            $new_item_adultCount         = $newItem->getAdultCount();
            $new_item_childCount         = $newItem->getChildCount();
            $new_item_addons             = $newItem->getAddons();
            $new_item_extras             = $newItem->getExtras();


            $session            = $request->getSession();
            $discounts          = $session->get('discounts',  []);
            $utils              = $this->get('wicrew.core.utils');
            $discountService    = new DiscountService($utils);
            $discountValues     = $discountService->getDiscountValuesFromSummaries($summaries, $discounts);
            
            if( count( $discountValues ) > 0 ){
                foreach ($discountValues as $key => $discount) {
                    $discount0      = $discount['discount'];
                    $discountObj    = $this->getEM()->getRepository(Discount::class)->findOneById($discount0->getId());
                    $discountItem   =  new DiscountItem();
                    $discountItem->setName($discountObj->getName());
                    $discountItem->setCode($discountObj->getCode());
                    $discountItem->setTypeDiscount($discountObj->getTypeDiscount());
                    $discountItem->setUsedNumber($discountObj->getUsedNumber());
                    $discountItem->setQuantityPerUser($discountObj->getQuantityPerUser());
                    $discountItem->setDescription($discountObj->getDescription());
                    $discountItem->setReductionAmount($discountObj->getReductionAmount());
                    $discountItem->setReductionPercentage($discountObj->getReductionPercentage());
                    $this->getEM()->persist($discountItem);
                    $order->addDiscountItem($discountItem);
    
                    $discountObj->setUsedNumber($discountObj->getUsedNumber()+1);
                    $this->getEM()->persist($discountObj);
                }
                
                $orderHistory   = $orderUtils->createOrderHistory_discounted( $order, $user, $discount['discountRack'] );
                $orderDueAmount = new Money( $order->getOrderHistoryTotal()['totalDue'] );                
                
                $order->addHistory($orderHistory);
                $session->remove('orderID');
            }

            if( $old_item_pickDate != $new_item_pickDate ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service pick-up date changed from '$old_item_pickDate' to '$new_item_pickDate'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_pickTime != $new_item_pickTime ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service time changed from '$old_item_pickTime' to '$new_item_pickTime'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_pickTimeTransport != $new_item_pickTimeTransport ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service pick-up time changed from '$old_item_pickTimeTransport' to '$new_item_pickTimeTransport'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_pickArea != $new_item_pickArea ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service pick-up area changed from '$old_item_pickArea' to '$new_item_pickArea'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_pickAddress != $new_item_pickAddress ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service pick-up location changed from '$old_item_pickAddress' to '$new_item_pickAddress'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_dropArea != $new_item_dropArea ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service drop-off area changed from '$old_item_dropArea' to '$new_item_dropArea'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_dropAddress != $new_item_dropAddress ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service drop-off location changed from '$old_item_dropAddress' to '$new_item_dropAddress'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_adultCount != $new_item_adultCount ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service adult's qty. changed from '$old_item_adultCount' to '$new_item_adultCount'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( $old_item_childCount != $new_item_childCount ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service children's qty. changed from '$old_item_childCount' to '$new_item_childCount'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( count($old_item_addons) < count($new_item_addons) ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service new add-on(s) added!" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 
           
            if( count($old_item_extras) < count($new_item_extras) ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service new extra(s) added!" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

            if( count($old_item_addons) > count($new_item_addons) ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service add-on(s) removed!" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 
           
            if( count($old_item_extras) > count($new_item_extras) ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderID - Service extra(s) removed!" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } 

 
            $em->persist($order);
            $em->flush();
            $em->commit();
        } catch (Throwable $e) {
            $this->logError($e);
            return $this->returnExceptionResponse($e);
        }

        $this->addFlash('success', $this->translator()->trans('order.items.edit.success'));
        return $this->redirectToRoute('easyadmin', [
            'id'        => $orderID,
            'action'    => 'edit',
            'entity'    => 'Order'
        ]);
    }

    /**
     * Renders the booking form.
     *
     * @Route(
     *     path = "sale/add/{id}",
     *     name = "add_order_item",
     *     methods = { "POST" }
     * )
     *
     * @param Request $request
     * @param int $id
     *
     * @return Response
     * @throws Exception
     */
    public function addOrderItemAction(Request $request, int $id) {
        $auth_checker = $this->get('security.authorization_checker');
        $isRoleAdmin = $auth_checker->isGranted('ROLE_EMPLOYEE');
        if (!$isRoleAdmin) {
            throw new Exception("No admin role found.");
        }

        $orderUtils = $this->container->get('wicrew.order.utils');

        /* @var Order $order */
        $order = $this->getEM()->getRepository(Order::class)->findOneBy(['id' => $id]);

        try {
            $this->getEM()->beginTransaction();

            $resultInput = $request->request->get('products');
            $token = $this->get('security.token_storage')->getToken();
            /* @var User|null $user */
            $user = $token->getUser() instanceof User ? $token->getUser() : null;

            /* @var array $productInfo */
            foreach ($resultInput as $productInfo) {
                $summary = $this->getSummaryFromPOSTData($productInfo);
                $orderItem = $summary->toOrderItem($this->getEM(), new OrderItem());
                if ($order->getSupplier() !== null) {
                    $orderItem->setSupplierCommission($order->getSupplier()->getCommission());
                }
                $orderItem->setAdultRackPrice($summary->getAdultRackPrice());
                $orderItem->setChildRackPrice($summary->getChildRackPrice());
                $orderItem->setAdultNetPrice($summary->getAdultNetPrice());
                $orderItem->setChildNetPrice($summary->getChildNetPrice());
                $orderItem->setTaxValue($summary->getTax()->getAmount());
                $orderItem->setOrderId($id);
                $orderItem->setDlOrder($order);
                $orderItem->setArchiveStatus(0); 
                $this->getEM()->persist($orderItem);
                $order->addItem($orderItem);
                $order->setStatus(0); // Making order status to PENDING
                $orderHistory = $orderUtils->createOrderHistory_AddedItem($orderItem, $user);
                $order->addHistory($orderHistory);
            }


            // LOGGING INTO HISTORYLOG
            global $kernel;
            $historyLog         = new HistoryLog();
            $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
            $order_id           = $order->getId();
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
            $historyLog->setModifications("#RJ$order_id - New item Added!" );
            $this->getEM()->persist($historyLog);
            // $em->flush();
            // LOGGING INTO HISTORYLOG

            $session = $request->getSession();
            $session->remove('orderID');

            $this->getEM()->persist($order);
            $this->getEM()->flush();
            $this->getEM()->commit();
        } catch (Throwable $e) { 
            $this->logError($e);
            $this->getEM()->rollback();
            return $this->returnExceptionResponse($e);
        }

        $this->addFlash('success', $this->translator()->trans('core.create.success'));
        return $this->redirectToRoute('easyadmin', [
            'id' => $id,
            'action' => 'edit',
            'entity' => 'Order'
        ]);
    }

    /**
     * @Route(path = "sale/checkout/success", name = "sale_checkout_success")
     *
     * @return RedirectResponse|Response
     */
    public function checkoutSuccessAction() {
        return $this->render('WicrewSaleBundle:Checkout:checkout.success.html.twig', []);
    }

    /**
     * @Route(path = "sale/cart/contactus", name = "sale_contactus")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function contactUsAction(Request $request) {
        try {
            $translator = $this->get('translator');
            $mailerService = $this->container->get('wicrew.core.mailer');

            $resultInput = $request->request->get('products');

            /* @var ProductSummary[] $summaries */
            $summaries = [];

            /* @var array $productInfo */
            foreach ($resultInput as $bookingNumber => $productInfo) {
                $summaries[$bookingNumber] = $this->getSummaryFromPOSTData($productInfo);
            }
            if (!empty($summaries)) {
                $em = $this->getEM(); 
                /* @var User|null $user */
                $user = null;
                
                
                try {
                    $em->beginTransaction();

                    $formInput = $request->request->get('form');
                    $checkoutPostData = $formInput['checkout'];

                    $stripeToken = $request->request->get('stripeToken');
                    $orderUtils = $this->container->get('wicrew.order.utils');
                    $token = $this->get('security.token_storage')->getToken();
                    $user = $token->getUser() instanceof User ? $token->getUser() : null;

                    $order = new Order();
                    /* @var Partner $supplier */
                    $supplier = null;
                    if (isset($checkoutPostData['partner'])) {
                        $supplier = $em->getRepository(Partner::class)->findOneBy(['id' => $checkoutPostData['partner']]);
                        if (!$supplier) {
                            throw $this->createNotFoundException('Partner not found.');
                        }
                        $order->setSupplier($supplier);
                    }
                    $quote = isset($checkoutPostData['checkout_as_quote']) ? $checkoutPostData['checkout_as_quote'] : false;
                    $paymentType = isset($checkoutPostData['payment_type']) ? $checkoutPostData['payment_type'] : null;
                    if ($paymentType !== null) {
                        $order->setPaymentType((int)$paymentType);
                    } else {
                        $order->setPaymentType(Order::PAYMENT_TYPE_CREDIT_CARD);
                    }
                    $order->setFirstName(trim($checkoutPostData['contact']['firstName']));
                    $order->setLastName(trim($checkoutPostData['contact']['lastName']));
                    $order->setEmail(trim($checkoutPostData['contact']['email']));
                    $order->setTel(trim($checkoutPostData['contact']['tel']));
                    $order->setWhatsapp(trim($checkoutPostData['contact']['whatsapp']));
                    $order->setCountry(trim($checkoutPostData['contact']['country']));
                    $order->setUser($user);
                    $order->setQuote($quote);
                    $order->setArchiveStatus(0); 
                    // $order->setNotes($checkoutPostData['contact']['notes']);
                    // $order->setCustomerNotes($checkoutPostData['contact']['notes']);
                    
                    $grandTotalRackPrice = new Money();
                    $grandTotalRackPriceToCharge = new Money();
                    
                    /* @var OrderItem[] $bookingsCharged */
                    $bookingsCharged = array();
                    foreach ($summaries as $summary) {
                        $orderItem = $summary->toOrderItem($this->getEM(), new OrderItem());
                        if ($order->getSupplier() !== null) {
                            $orderItem->setSupplierCommission($order->getSupplier()->getCommission());
                        }
                        $orderItem->setAdultRackPrice($summary->getAdultRackPrice());
                        $orderItem->setChildRackPrice($summary->getChildRackPrice());
                        $orderItem->setAdultNetPrice($summary->getAdultNetPrice());
                        $orderItem->setChildNetPrice($summary->getChildNetPrice());
                        $orderItem->setTaxValue($summary->getTax()->getAmount());
                        $orderItem->setArchiveStatus(0); 
                        $this->getEM()->persist($orderItem);
                        $order->addItem($orderItem);
                        
                        $grandTotalRackPrice = $grandTotalRackPrice->add($summary->getGrandTotal()->getRackPrice());
                        
                        if ($summary instanceof ProductSummary && $summary->isBookingTooLate()) {
                            continue;
                        }
                        $grandTotalRackPriceToCharge = $grandTotalRackPriceToCharge->add($summary->getGrandTotal()->getRackPrice());
                        
                        $bookingsCharged[] = $orderItem;
                    }
                    
                    $orderHistory = $orderUtils->createOrderHistory_InitialTotal($order, $user, $grandTotalRackPrice);
                    $order->addHistory($orderHistory);
                    
                    $order->setStatus(Order::STATUS_PENDING);
                    
                    $em->persist($order);
                    $em->flush(); 
                    
                    foreach ($order->getItems() as $orderItem) {
                        $orderItem->setOrder($order);
                        $orderItem->setOrderId($order->getId());
                        $em->persist($orderItem);
                    }

                    $em->persist($order);
                    $em->flush();

                    $em->commit(); 
                } catch (Throwable $e) {
                    $this->logError($e);
                    $em->rollback();
                    return $this->returnExceptionResponse($e);
                } 
                
                $contact = $request->request->get('form')['checkout']['contact'];
                
                $body = $this->renderTwigToString('WicrewSaleBundle:Email:flight.contact.html.twig', [
                    'summaries' => $summaries,
                    'contact'   => $contact,
                    'order'     => $order
                ]);
                $mailerService->send([
                    'from'      => $this->container->getParameter('system_email'),
                    'to'        => $this->container->getParameter('system_email'),
                    'replyTo'   => $contact['email'],
                    'subject'   => $translator->trans('booking.flight.contact.subject'),
                    'body'      => $body
                ]);
            } else {
                throw new Exception("No bookings found.");
            }
        } catch (Exception $e) {
            return $this->returnExceptionResponse($e);
        }
        return $this->render('WicrewSaleBundle:Contact:contact.success.html.twig');
    }

    /**
     * @Route(path = "sale/contact/{type}", name = "sale_contact_page", defaults={"type"=""})
     *
     * @param $type
     
     *
     * @return Response
     */
    public function contactPageAction($type) {
        $form = $this->createFormBuilder()->getForm();
        $translator = $this->get('translator');
        $form->add('firstName', TextType::class, ['label' => $translator->trans('sale.checkout.firstName'), 'attr' => ['required' => 'required']])
            ->add('lastName', TextType::class, ['label' => $translator->trans('sale.checkout.lastName'), 'attr' => ['required' => 'required']])
            ->add('email', EmailType::class, ['label' => $translator->trans('sale.checkout.email'), 'attr' => ['required' => 'required']])
            ->add('country', TextType::class, ['label' => $translator->trans('sale.checkout.country'), 'attr' => ['required' => 'required']])
            ->add('notes', TextareaType::class, ['label' => $translator->trans('sale.checkout.notes'), 'attr' => ['required' => 'required']])
            ->add('type', HiddenType::class, ['attr' => ['value' => $type]]);

        return $this->render('WicrewSaleBundle:Contact:contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path = "sale/contactsend", name = "sale_contact_send")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contactSendAction(Request $request) {
        $translator     = $this->get('translator');
        $contact        = $request->request->get('form');
        $mailerService  = $this->container->get('wicrew.core.mailer');


        try {
            if( !isset($contact["g-recaptcha-response"]) || empty($contact["g-recaptcha-response"]) ){
                return new JsonResponse( [
                    'status' => 'error',
                    'message' => "Please fill the reCaptcha to proof that you are a human!"
                ] );
            }

            if (isset($contact['type']) && $contact['type'] == 'requestbooking') {
                $subject = $translator->trans('booking.request.notallow');
                $body = $this->renderTwigToString('WicrewSaleBundle:Email:contact.bookingrequest.html.twig', [
                    'contact' => $contact
                ]);
            } else {
                $subject = 'Contact from ' . $contact['firstName'] . ' ' . $contact['lastName'];
                $body = $this->renderTwigToString('WicrewSaleBundle:Email:contact.us.html.twig', [
                    'contact' => $contact
                ]);
            }
    
            $mailerService->send(
                [
                    'from'      => $this->container->getParameter('system_email'),
                    'to'        => $this->container->getParameter('system_email'),
                    'replyTo'   => $contact['email'],
                    'subject'   => $subject,
                    'body'      => $body
                ]
            );
    
        } catch (Throwable $e) {
            $this->logError($e);
            return $this->returnExceptionResponse($e);
        }
        return $this->returnSuccessResponse();
    }

    /**
     * validate discount code
     *
     * @Route("/validateDiscountCode", name="validate_discount_code")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function validateDiscountCodeAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $is_valid = false;
        $html = '';

        try {
            $discount_code = $request->request->get('discount_code');
            $discount = $em->getRepository(Discount::class)->findOneByCode($discount_code);

            if ($discount) {
                $is_valid = $discount->getUsedNumber() <= $discount->getQuantityPerUser();

                if ($is_valid) {
                    $session = $request->getSession();
                    $this->addDiscountToCart($session, $discount);

                    $discounts = $session->get('discounts',  []);

                    $utils = $this->get('wicrew.core.utils');
                    $discountService = new DiscountService($utils);
                    $summaries = $this->getCartBookings($session);
                    $discountValues = $discountService->getDiscountValuesFromSummaries($summaries, $discounts);

                    $html = $this->renderTwigToString('@WicrewSale/Booking/Discount/discount_list.html.twig', [
                        'discountValues' => $discountValues
                    ]);
                }
            }
        } catch (Throwable $e) {
            $this->logError($e);
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['discount_code_valid' => $is_valid, 'discount_code' => $discount_code, 'html' => $html]);
    }

    /**
     * remove discount code
     *
     * @Route("/removeDiscountCode", name="remove_discount_code")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeDiscountCodeAction(Request $request) {
        $html = '';

        try {
            $discount_id = (int) $request->request->get('discount_id');

            $session = $request->getSession();
            $this->removeDiscountToCart($session, $discount_id);

            $discounts = $session->get('discounts',  []);

            $utils = $this->get('wicrew.core.utils');
            $discountService = new DiscountService($utils);
            $summaries = $this->getCartBookings($session);
            $discountValues = $discountService->getDiscountValuesFromSummaries($summaries, $discounts);

            $html = $this->renderTwigToString('@WicrewSale/Booking/Discount/discount_list.html.twig', [
                'discountValues' => $discountValues
            ]);

        } catch (Throwable $e) {
            $this->logError($e);
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['html' => $html]);
    }

    /**
     * @Route(path = "testmail", name = "testmail")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function testMailAction(Request $request) {
 
        $translator = $this->get('translator');
        $mailer     = $this->container->get('wicrew.core.mailer');

        $mailer->send([
            'from'      => 'info@iltcostarica.com',
            'to'        => 'mansoor.walterinteractive@gmail.com', 
            'subject'   => 'test',
            'body'      => 'test'
        ]);
      
        echo 'Test email sent to mansoor.walterinteractive@gmail.com';die;
    } 

    /**
     * Charge Stripe payment
     *
     * @Route(path = "order/mailpayment/stripe", name = "order_mailpayment_transaction")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function mailpaymentStripeAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
 
        $parameters = $renderTargetParameters = $bookings = $custom_services = [];
        $q = $request->query->get('q');
        $utils = $this->get('wicrew.core.utils');
        $data = $utils->encrypt_decrypt($q, 'decrypt');
        $data = json_decode($data, true);
        $oid = $data['oid'];
        $order = $em->getRepository(Order::class)->find($oid);

        $orderItems = $order->getItems();
        foreach ($orderItems as $key => $orderItem) {
            if( $orderItem->getStatus() == 0 ){
                $summary = $this->getSummaryFromOrderItem($orderItem);
                
                $tax_amount     = new Money(0.00);
                $rack_amount    = $order->getOrderHistoryTotal()['totalDue'];
                $net_amount     = $order->getOrderHistoryTotal()['totalDue'];
                $summary->setSubtotal($rack_amount, $net_amount);
                $summary->setTotalTaxes($tax_amount, "Tax");
                $summary->setGrandTotal($rack_amount->add($tax_amount), $net_amount, "Total");
                $_customServices    = $orderItem->getCustomServices();
                $customServices     = [];
                foreach($_customServices as $customService){
                    
                    $customServiceLabel     = $customService->getLabel(); 
                    $customServiceRackPrice = new Money($customService->getRackPrice()); 
                    $customServiceTax       = new Money(( $customService->getRackPrice() * 0.13));
                    $custom_service_data = array(
                        "name"  => $customServiceLabel,
                        "value" => $customServiceRackPrice,
                        "tax"   => $customServiceTax,
                    );
                    $customServices[] = $custom_service_data;
                    $summary->setSubtotal( $rack_amount->add($customServiceRackPrice), $net_amount );
                    $summary->setTotalTaxes( $tax_amount->add($customServiceTax), "Tax" );
                    $summary->setGrandTotal( $rack_amount->add($tax_amount)->add($customServiceRackPrice)->add($customServiceTax), $net_amount, "Total" );
                            
                }
                
                $bookings[] = $summary;
                $custom_services[] = $customServices;
            }
        }
        if(count($bookings) == 0 ){
            foreach ($orderItems as $key => $orderItem) {
                $summary = $this->getSummaryFromOrderItem($orderItem);
                
                $tax_amount     = new Money(0.00);
                $rack_amount    = $order->getOrderHistoryTotal()['totalDue'];
                $net_amount     = $order->getOrderHistoryTotal()['totalDue'];
                $summary->setSubtotal($rack_amount, $net_amount);
                $summary->setTotalTaxes($tax_amount, "Tax");
                $summary->setGrandTotal($rack_amount->add($tax_amount), $net_amount, "Total");
                $_customServices    = $orderItem->getCustomServices();
                $customServices     = [];
                foreach( $_customServices as $customService ){
                    
                    $customServiceLabel     = $customService->getLabel(); 
                    $customServiceRackPrice = new Money($customService->getRackPrice()); 
                    $customServiceTax       = new Money(( $customService->getRackPrice() * 0.13));
                    $custom_service_data = array(
                        "name"  => $customServiceLabel,
                        "value" => $customServiceRackPrice,
                        "tax"   => $customServiceTax,
                    );
                    $customServices[] = $custom_service_data;
                    $summary->setSubtotal( $rack_amount->add($customServiceRackPrice), $net_amount );
                    $summary->setTotalTaxes( $tax_amount->add($customServiceTax), "Tax" );
                    $summary->setGrandTotal( $rack_amount->add($tax_amount)->add($customServiceRackPrice)->add($customServiceTax), $net_amount, "Total" );
                            
                }
        
                $bookings[] = $summary;
                $custom_services[] = $customServices;
            }
            
        }
        $isPaid = $order->getStatus() == Order::STATUS_PAID;
      
        
        $parameters['bookings']                 = $bookings;
        $parameters['isPaymentMail']            = true;
        $parameters['order']                    = $order;
        $parameters['isPaid']                   = $isPaid;
        $parameters['itemsCustomServices']      = $custom_services;
        return $this->forward('WicrewSaleBundle:Booking:checkout', $parameters);
    }
    
    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->last_request['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

}

