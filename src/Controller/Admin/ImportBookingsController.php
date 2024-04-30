<?php

namespace App\Controller\Admin;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Service\Summary\PriceSummary;
use App\Wicrew\SaleBundle\Service\Summary\ProductSummary;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\CoreBundle\Service\SimpleXLSX\SimpleXLSX;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;

use DateTime;
use Exception;
use Throwable;

class ImportBookingsController extends BaseAdminController
{


    /**
     * @Route(path = "admin/import/bookings", name = "import_new_bookings")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importBookingsAction(Request $request)
    {
        try {
            $em = $this->getEM();
            $em->beginTransaction();
            $pathToExcel = __DIR__ . '/bookings_to_import.xlsx';
            $pathToExcel = str_replace( "src/Controller/Admin/", "", $pathToExcel);
            $xlsx = new SimpleXLSX($pathToExcel);
            $products_info          = [];

            if( $xlsx->success() ){

                $customerFirstName      = NULL;
                $customerLastName       = NULL;
                $customerEmail          = NULL;
                $customerTel            = NULL;
                $customerWhatsapp       = NULL;
                $customerCountry        = NULL;
                $customerNotes          = NULL;
                $customerInternalNotes  = NULL;
                $supplier_id            = NULL; // int supplier ID added on site
                $payment_type           = NULL; // 1 = Credit card, 2 = Cash, 3 = Cheque
                $checkout_as_quote      = NULL; // 0 = No, 1 = Yes
                $user_id                = NULL;
                $order_status           = NULL; // 0 = Pending, 1 = Paid, 2 = Cancelled
                $transportation_type    = NULL; // 1 = Private shuttles, 4 = Jeep-Boat-Jeep private, 6 = Water Taxi, 8 = Private Flight
                $booking_type           = NULL;
                $areaFrom               = NULL;
                $areaTo                 = NULL;
                $adultsCount            = NULL;
                $childCount             = NULL;
                $vehicle_type_name      = NULL;
                $booking_date           = NULL;
                $booking_time           = NULL;
                $airline                = NULL;
                $FlightNo               = NULL;
                $activity_name          = NULL;
                foreach( $xlsx->rows() as $row_key => $row ) {
                    $currentRowNum = $row_key + 1;
                    if ($row_key == 0) continue; 
                    $customerFirstName      = $row[8];
                    $customerLastName       = $row[9];
                    $customerEmail          = $row[10];
                    $customerTel            = $row[11];
                    $customerWhatsapp       = $row[12];
                    $customerCountry        = $row[13];
                    $customerNotes          = $row[14];
                    $customerInternalNotes  = $row[15];
                    if(!empty( $row[28] )) {

                        $supplier = $this->getDoctrine()->getRepository(Partner::class)->findBy([
                            'bizName' => $row[28]
                        ]);   
                        if (count($supplier) > 0)
                            $supplier_id            = $supplier[0]->getId();
                        else{
                            echo "Supplier Not found against `$row[28]` in row# ".  $currentRowNum ."\n";
                        }

                    } 
                    $payment_type           = $row[5];
                    if( trim(strtolower($payment_type)) == "credit card" ) $payment_type    = (int)1;
                    if( trim(strtolower($payment_type)) == "cash" ) $payment_type           = (int)2;
                    if( trim(strtolower($payment_type)) == "cheque" ) $payment_type         = (int)3;
                    
                    $checkout_as_quote      = $row[6]; // 0 = No, 1 = Yes
                    if( trim(strtolower($checkout_as_quote)) == "no" ) $checkout_as_quote    = (int)0;
                    if( trim(strtolower($checkout_as_quote)) == "yes" ) $checkout_as_quote   = (int)1;
                    
                    $user_id                = NULL;                    
                    $order_status           = $row[3];
                    // 0 = Pending, 1 = Paid, 2 = Cancelled
                    if( trim(strtolower($order_status)) == "pending" ) $order_status   = (int)0;
                    if( trim(strtolower($order_status)) == "paid" ) $order_status      = (int)1;
                    if( trim(strtolower($order_status)) == "cancelled" ) $order_status = (int)2;

                    $transportation_type    = $row[7]; // 1 = Private shuttles, 4 = Jeep-Boat-Jeep private, 6 = Water Taxi, 8 = Private Flight
                  
                    $booking_type           = $row[4];
                    $activity_name          = $row[18];
                    $areaFrom               = $row[19];
                    $areaTo                 = $row[21];
                    $adultsCount            = $row[16];
                    $childCount             = $row[17];
                    $vehicle_type_name      = $row[24];
                    $booking_date           = substr($row[0], -9);
                    $booking_time           = substr($row[1], 11);
                    $airline                = $row[22];
                    $FlightNo               = $row[23];
                    if(strtolower($booking_type) == "product"){
                        // Transportation type (prendre l'une des valeurs suivantes : Private shuttles, Shared shuttles, Jeep-Boat-Jeep shared, Jeep-Boat-Jeep private, Jeep-Boat-Jeep riding, Water Taxi, Private Flight) 
                        $transportationType = $this->getDoctrine()->getRepository(TransportationType::class)->findBy([
                            'name' => $transportation_type
                        ]);
                        if (count($transportationType) > 0)
                            $transportationType_id = $transportationType[0]->getId();
                        else{
                            echo "Transportation type not found against `$transportation_type` in row# ".  $currentRowNum ."\n";
                        }
    
                        // Area from (un nom dans BO > Area. à copier exactement un nom existant ou créer sinon. exemple : Montezuma)
                        $area_from = $this->getDoctrine()->getRepository(Area::class)->findBy([
                            'name' => $areaFrom
                        ]);
                        if (count($area_from) > 0)
                            $area_from_id   = $area_from[0]->getId();
                        else{
                            echo "Area from not found against `$areaFrom` in row# ".  $currentRowNum ."\n";
                        }
                        if (count($area_from) > 0)
                            $area_from_type = $area_from[0]->getType();
        
        
                        // Area to (un nom dans BO > Area. à copier exactement un nom existant ou créer sinon. exemple : Montezuma)
                        $area_to = $this->getDoctrine()->getRepository(Area::class)->findBy([
                            'name' => $areaTo
                        ]);     
                        if (count($area_to) > 0)
                            $area_to_id     = $area_to[0]->getId();
                        else{
                            echo "Area to not found against `$areaTo` in row# ".  $currentRowNum ."\n";
                        }
                        if (count($area_to) > 0)
                            $area_to_type   = $area_to[0]->getType();
        
                        $vehicle_type = $this->getDoctrine()->getRepository(VehicleType::class)->findBy([
                            'name' => $vehicle_type_name
                        ]);       
                        if (count($vehicle_type) > 0)
                            $vehicle_type_id = $vehicle_type[0]->getId();
                        else{
                            echo "Vehicle Type not found against `$vehicle_type_name` in row# ".  $currentRowNum ."\n";
                        }
                        $productRepository  = $this->getDoctrine()->getRepository(Product::class);
                        $products           = $productRepository->findBy( [
                            'transportationType' => $transportationType_id,
                            'areaFrom'           => $area_from_id,
                            'areaTo'             => $area_to_id,
                            'archived'           => 0,
                            'vehicleType'        => $vehicle_type_id
        
                        ] );
                        
                        if(count($products) > 0) 
                            $product_id     = $products[0]->getId(); 
                        else {
                            echo "\n\n"."Product data not found in database against row# ".  $currentRowNum ."\n";
                            echo "Searching product against following provided info:"."\n";
                            echo "Transportation Type: $transportation_type"."\n";
                            echo "Area From: $areaFrom"."\n";
                            echo "Area To: $areaTo"."\n";
                            echo "Vehicle Type: $vehicle_type_name"."\n";

                            continue;
                        }
                        $product        = $this->getDoctrine()->getRepository(Product::class)->find($product_id);
                        $products[]     = $product;
                        
                        $_temp_prod     =  array(
                            'id'            => $product_id,
                            'status'        => 0, // 0 = Pending, 1 = Done, 2 = Cancelled
                            'type'          => 'product',
                            'pickUpDate'    => $booking_date, 
                            'pickUpTime'    => $booking_time,
                            'adultCount'    => (int)$adultsCount,
                            'childCount'    => (int)$childCount,
                            'area_from'     =>
                                array(
                                    'nameAndType'       => '{"name":"'.$areaFrom.'","type":'.$area_from_type.',"id":'.$area_from_id.'}',
                                    'airlineCompany'    => $airline,
                                    'flightNumber'      => $FlightNo,
                                    'address'           => NULL,
                                    'googlePlaceID'     => NULL,
                                ),
                            'area_to'       =>
                                array(
                                    'nameAndType'       => '{"name":"'.$areaTo.'","type":'.  $area_to_type.',"id":'.$area_to_id.'}',
                                    'airlineCompany'    => $airline,
                                    'flightNumber'      => $FlightNo,
                                    'address'           => NULL,
                                    'googlePlaceID'     => NULL,
                                ),
                            'extras'        => array(
                                2 => array(
                                    8 => array(
                                        'label'     => 'extra_price',
                                        // 'enabled'   => 8,
                                        // 'quantity'  => 1,
                                        'quantity'  => 0,
                                    ),
                                    // 'enabled'  => 2,
                                ),
                                3 => array(
                                    12 => array(
                                        'label'     => 'extra_price',
                                        'quantity'  => 0,
                                    ),
                                ),
                                4 => array(
                                    16 => array(
                                        'label'     => 'extra_price',
                                        'quantity'  => 0,
                                    ),
                                ),
                                5 => array(
                                    20 => array(
                                        'label'     => 'extra_price',
                                        'quantity'  => 0,
                                    ),
                                ),
                            ),
                        );
                        array_push($products_info, $_temp_prod);
        
                    } else if(strtolower($booking_type) == "activity"){
                        
                        $activity_name = "Self-Guided Mistico Hanging Bridges";
                        $activity = $this->getDoctrine()->getRepository(Activity::class)->findBy([
                            'name'      => $activity_name,
                            'archived'  => 0
                        ]);       
                        $activity_id    = $activity[0]->getId();
                        $activity_area  = $activity[0]->getArea();
                        
                        $area_from = $this->getDoctrine()->getRepository(Area::class)->findBy([
                            'name' => trim($areaFrom)
                        ]);
                        
                        $area_from_id   = $area_from[0]->getId();
                        $area_from_type = $area_from[0]->getType();
        
                        $_temp_activity = array (
                            'id'            => $activity_id,
                            'type'          => 'activity',
                            'pickUpDate'    => $booking_date,
                            'tourTime'      => $booking_time,
                            'adultCount'    => $adultsCount,
                            'childCount'    => $childCount,
                            'area_from'     => array (
                                'nameAndType'       => '{"name":"'.$activity_area.'","type":'.$area_from_type.',"id":'.$area_from_id.'}',
                                'flightNumber'      => NULL,
                                'airlineCompany'    => NULL,
                                'address'           => NULL,
                                'googlePlaceID'     => NULL,
                            ),
                            'area_to'       => array (
                                'nameAndType'       => '{"name":"'.$activity_area.'","type":'.$area_from_type.',"id":'.$area_from_id.'}',
                                'flightNumber'      => NULL,
                                'airlineCompany'    => NULL,
                                'address'           => NULL,
                                'googlePlaceID'     => NULL,
                            ),
                        );
                        array_push($products_info, $_temp_activity);
        
                    } else if(strtolower($booking_type) == "extras"){
                        if($xlsx->rows()[$row_key-1][4] == "Product"){
                           
                            if( !array_key_exists("extras", $products_info[count($products_info) - 1] ) ){
                                echo "Previous product not found so can not  find it's extras\n"; continue;
                            }
                            $extras_val = $row[23];
                            $extras_val = explode(",", $extras_val);
                            foreach($extras_val as $extra_val){
                                $extra_val = explode(" * ", $extra_val);
                                $extra_name = $extra_val[0];
                                $extra_qty  = $extra_val[1];
                                
                                if($extra_name == "Imperial Beer (6 pack)"){
                                    $products_info[count($products_info) - 1]["extras"][2]["enabled"]       = 2;
                                    $products_info[count($products_info) - 1]["extras"][2][8]["enabled"]    = 8;
                                    $products_info[count($products_info) - 1]["extras"][2][8]["quantity"]   = (int)$extra_qty;


                                }else if($extra_name == "Toddler Car Seat"){
                                    
                                    $products_info[count($products_info) - 1]["extras"][3]["enabled"]        = 3;
                                    $products_info[count($products_info) - 1]["extras"][3][12]["enabled"]    = 12;
                                    $products_info[count($products_info) - 1]["extras"][3][12]["quantity"]   = (int)$extra_qty;


                                }else if($extra_name == "Infant Car Seat"){
                                    $products_info[count($products_info) - 1]["extras"][4]["enabled"]        = 4;
                                    $products_info[count($products_info) - 1]["extras"][4][16]["enabled"]    = 16;
                                    $products_info[count($products_info) - 1]["extras"][4][16]["quantity"]   = (int)$extra_qty;

                                }else if($extra_name == "Booster Car Seat"){

                                    $products_info[count($products_info) - 1]["extras"][5]["enabled"]        = 5;
                                    $products_info[count($products_info) - 1]["extras"][5][20]["enabled"]    = 20;
                                    $products_info[count($products_info) - 1]["extras"][5][20]["quantity"]   = (int)$extra_qty;
                                    
                                }
                                
                            }

                        }
                    }
                }
            } else {
                echo 'xlsx error: '.$xlsx->error();
                exit;
            }
            $bookings               = []; 
            foreach ($products_info as $product_info) {
                $summary = $this->getSummaryFromPOSTData($product_info);
                array_push($bookings, $summary);
                if ($summary->getAdultCount() <= 0) {
                    throw new Exception("Adult count must be greater than 0.");
                }
                if ($summary->getChildCount() < 0) {
                    throw new Exception("Child count must be greater than or equal to 0.");
                }
            }

            // CREATING NEW ORDER INSTANCE 
            $order = new Order();

            // ASSIGNING A SUPPLIER TO ORDER 
            /* @var Partner $supplier */
            $supplier       = null;
            if (isset($supplier_id)) {
                $supplier   = $em->getRepository(Partner::class)->findOneBy(['id' => $supplier_id]);
                if (!$supplier) {
                    throw $this->createNotFoundException('Partner not found.');
                }
                $order->setSupplier($supplier);
            }
            // ASSIGNING A SUPPLIER TO ORDER 

            // ASSIGNING PAYMENT TYPE TO ORDER
            if ($payment_type !== null) {
                $order->setPaymentType((int)$payment_type);
            } else {
                $order->setPaymentType(Order::PAYMENT_TYPE_CREDIT_CARD);
            }
            // ASSIGNING PAYMENT TYPE TO ORDER


            // ASSIGNING USER DATA AND QUOTE  TO ORDER
            $order->setFirstName(trim($customerFirstName));
            $order->setLastName(trim($customerLastName));
            $order->setEmail(trim($customerEmail));
            $order->setTel(trim($customerTel));
            $order->setWhatsapp(trim($customerWhatsapp));
            $order->setCountry(trim($customerCountry));
            $order->setUser($user_id);
            $order->setQuote($checkout_as_quote);
            $order->setNotes($customerInternalNotes);
            $order->setCustomerNotes($customerNotes);
            // ASSIGNING USER DATA AND QUOTE  TO ORDER

            $grandTotalRackPrice            = new Money();
            $grandTotalRackPriceToCharge    = new Money();

            // HERE WE WILL GET ALL DATA FOR BOOKINGS
            /* @var OrderItem[] $bookingsCharged */
            $bookingsCharged = array();
            foreach ($bookings as $summary) {
                $orderItem = $summary->toOrderItem($this->getEM(), new OrderItem());
                if ($order->getSupplier() !== null) {
                    $orderItem->setSupplierCommission($order->getSupplier()->getCommission());
                }
                $orderItem->setAdultRackPrice($summary->getAdultRackPrice());
                $orderItem->setChildRackPrice($summary->getChildRackPrice());
                $orderItem->setAdultNetPrice($summary->getAdultNetPrice());
                $orderItem->setChildNetPrice($summary->getChildNetPrice());
                $orderItem->setTaxValue($summary->getTax()->getAmount());
                $this->getEM()->persist($orderItem);
                $order->addItem($orderItem);
            
                $grandTotalRackPrice = $grandTotalRackPrice->add($summary->getGrandTotal()->getRackPrice());

                if ($summary instanceof ProductSummary && $summary->isBookingTooLate()) {
                    continue;
                }
                $grandTotalRackPriceToCharge = $grandTotalRackPriceToCharge->add($summary->getGrandTotal()->getRackPrice());

                $bookingsCharged[] = $orderItem;
            }


            $order->setStatus($order_status);
          
            $em->persist($order);
            $em->flush();
            $em->commit();
        } catch (Throwable $e) {
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse();
    }


    /**
     * @param array $productInfo
     *
     * @return PriceSummary
     *
     * @throws Exception
     */
    private function getSummaryFromPOSTData(array $productInfo): PriceSummary
    {
        global $kernel;
        $areaFrom               = $productInfo['area_from'];
        $areaFromNameAndType    = json_decode($areaFrom['nameAndType'], true);
        $areaFrom['name']       = $areaFromNameAndType['name'];
        $areaFrom['type']       = $areaFromNameAndType['type'];
        $areaFrom['id']         = $areaFromNameAndType['id'];
        unset($areaFrom['nameAndType']);
        $areaTo                 = $productInfo['area_to'];
        $areaToNameAndType      = json_decode($areaTo['nameAndType'], true);
        $areaTo['name']         = $areaToNameAndType['name'];
        $areaTo['type']         = $areaToNameAndType['type'];
        $areaTo['id']           = $areaToNameAndType['id'];
        unset($areaTo['nameAndType']);

        $pickUpDate = new DateTime($productInfo['pickUpDate']);

        if ($productInfo['type'] === 'product') {
            $productRepo = $this->getDoctrine()->getManager()->getRepository(Product::class);
            /* @var Product $product */
            $product = $productRepo->find($productInfo['id']);

            $hasOwnDepartureTime =
                ($product->getTransportationType()->isJeepBoatJeepType()
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
            $productUtils = $kernel->getContainer()->get('wicrew.product.utils');
            $addons = isset($productInfo['addons']) ? $productInfo['addons'] : null;
            $extras = isset($productInfo['extras']) ? $productInfo['extras'] : null;

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

            $tourTime = new DateTime($productInfo['tourTime']);
            $activityUtils = $kernel->getContainer()->get('wicrew.activity.utils');
            $summary = $activityUtils->getPriceSummary($activity, $productInfo['adultCount'], $productInfo['childCount'], $areaFrom, $areaTo, $pickUpDate, $tourTime);
        } else {
            throw new Exception('Unknown type given: ' . $productInfo['type']);
        }

        return $summary;
    }
}
