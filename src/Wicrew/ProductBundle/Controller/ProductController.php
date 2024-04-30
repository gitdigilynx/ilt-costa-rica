<?php

namespace App\Wicrew\ProductBundle\Controller;

use App\Wicrew\CoreBundle\Controller\Controller as Controller;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\ProductBundle\Entity\AreaChildren;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use DateTime;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable; 

class ProductController extends Controller {
    /**
     * Transportation page
     *
     * @Route(path = "product/transportation/{type}", name = "product_transportation")
     *
     * @param Request $request
     * @param string $type
     *
     * @return Response
     */
    public function transportationTypeAction(Request $request, string $type) {
        $utils = $this->get('wicrew.core.utils');
        $utils->checkForOrderEditSession($request);

        if ($type === TransportationType::TYPE_JEEP_BOAT_JEEP_URL) {
            return $this->render('@WicrewProduct/Product/product.jeep_boat_jeep.html.twig');
        }

        if ($type === TransportationType::TYPE_WATER_TAXI_URL) {
            return $this->render('@WicrewProduct/Product/product.watertaxi.html.twig');
        }


        /* @var TransportationType[] $result */
        $result = $this->getEM()->getRepository(TransportationType::class)->findBy(['urlPath' => $type]);
        if (count($result) !== 1) {
            return null;
        }

        return $this->render('@WicrewProduct/Product/product.main_transportation.html.twig', [
            'transportType' => $result[0]
        ]);
    }

    /** 
     * @Route(path = "product/displayLimitMessage", name = "product_displayLimitMessage")
     *
     * @param Request $request 
     *
     * @return Response
     */
    public function displayLimitMessageAction(Request $request) {
        $displayLimitMessage = false;

        $vehicle_data = (array) $request->request->get('vehicle_data');

        $em = $this->get('doctrine.orm.entity_manager');

        foreach ($vehicle_data as $key => $vehicle) {
            $search_from    = $vehicle['search_from'];
            $search_to      = $vehicle['search_to'];
            $search_from    = explode("-", $search_from, 2);
            $search_to      = explode("-", $search_to, 2);

            $passenger_count = $vehicle['passenger_count'];
 
            $areaFrom   = $em->getRepository(Area::class)->findOneById($search_from[0]);
            $areaTo     = $em->getRepository(Area::class)->findOneById($search_to[0]);
            
            $products = $em->getRepository(Product::class)->findBy([
                'areaFrom'      => $areaFrom,
                'areaTo'        => $areaTo,
                'vehicleType'   => VehicleType::HYUNDAI_H1,
                'archived'      => false
            ]);

            $product_result = null;
            
            foreach ($products as $key => $product) {
                $vehiculeType = $product->getVehicleType();
                if (
                    $vehiculeType->getMinPassengerNumber() <= $passenger_count &&
                    $passenger_count <= $vehiculeType->getMaxPassengerNumber()
                ) { 
                    $product_result = $product;
                    break;
                }
            }

            if($product_result && $product_result->getNovemberFixedRackPrice() == 0  ) {
                if ( $product_result->getFixedRackPrice() == 0 ) {
                    $displayLimitMessage = true; 
                    break;
                }
            }
        }
  
        return new JsonResponse(['displayLimitMessage' => $displayLimitMessage]);
    }

    /**
     * Transportation search page
     *
     * @Route(path = "product/search", name = "product_search", methods = { "GET" })
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function searchProductAction(Request $request) {
        $utils = $this->get('wicrew.core.utils');
        $utils->checkForOrderEditSession($request);

        $productUtils = $this->container->get('wicrew.product.utils');

        /* @var Product[][] $results */
        $results = array();
        /* @var DateTime|null $returnDate */
        $returnDate = null;

        $other_shared   = array();
        $other_private  = array();
        
        /* @var string|null $bannerSource */
        $bannerSource = null;

        $error = false;

        try {
            $tripType = (int)$this->getRequestData($request, "tripSearchType");

            /* @var int[] $transportTypes */
            $transportTypes = $this->getRequestDataNoThrow($request, 'transportTypes', array());
            $transportTypes = array_map(function ($value) {
                return intval($value);
            }, $transportTypes);

            // Shared shuttle can't be multi-destination.
            if (in_array(TransportationType::TYPE_SHARED_SHUTTLE, $transportTypes) && $tripType === Product::TRIP_TYPE_MULTI_DESTINATION) {
                if (($key = array_search(TransportationType::TYPE_SHARED_SHUTTLE, $transportTypes)) !== false) {
                    unset($transportTypes[$key]);
                }
            }

            if (count($transportTypes) > 1) {
                $bannerSource = '@WicrewProduct/Product/Headers/header.transport.html.twig';
            } else {
                $bannerSource = TransportationType::getStaticHeaderPath($transportTypes[0]);
            }

            $trips = $this->getRequestData($request, "trip");
            if ($tripType === Product::TRIP_TYPE_ROUND_TRIP) {
                $trips[2] = $trips[1];
                $buffer = $trips[2]["pickArea"];
                $trips[2]["pickArea"] = $trips[2]["dropArea"];
                $trips[2]["dropArea"] = $buffer;
                $trips[2]["dateFrom"] = $trips[2]["dateTo"];
            }

            foreach ($trips as $index => $trip) {
                $areaFromID = $trip["pickArea"];
                $areaToID   = $trip["dropArea"];
                if(strpos($areaFromID, '-') !== false) {
                    $areaFromID = substr($areaFromID, 0, strpos($areaFromID, '-'));
                }    
                if(strpos($areaToID, '-') !== false) {
                    $areaToID = substr($areaToID, 0, strpos($areaToID, '-'));
                }        
                
                
                $adultCount = $trip["adultCount"];
                $childCount = $trip["childCount"];

                $em             = $this->get('doctrine.orm.entity_manager');
                $areaFrom       = $em->getRepository(Area::class)->findOneById($areaFromID);
                $areaTo         = $em->getRepository(Area::class)->findOneById($areaToID);
                $other_airport  = array();
                
                if( $areaFrom->getHasAirport() && $areaTo->getHasAirport() ){
                    $areaFromAirport         = $areaFrom->getAirport();
                    $areaToAirport           = $areaTo->getAirport();
                    $other_airport[$index]   = $productUtils->searchProduct(array( 8 ) /* Private flght transportation type */, $areaFromAirport->getId(), $areaToAirport->getId(), $adultCount, $childCount);
                    foreach( $other_airport[$index] as $other_airport_prod_key => $other_airport_prod ){
                        if( $other_airport_prod->getAreaFrom()->getId() !=  $areaFromAirport->getId() ){
                            unset($other_airport[$index][$other_airport_prod_key]);
                        }
                    }
                
                }

                $results[$index] = $productUtils->searchProduct($transportTypes, $areaFromID, $areaToID, $adultCount, $childCount);

                foreach( $results[$index] as $results_prod_key => $results_prod ){
                    if( $results_prod->getAreaFrom()->getId() !=  $areaFrom->getId() ){
                        unset($results[$index][$results_prod_key]);
                    }
                }
                
                $other_private[$index]    = $productUtils->searchProduct( array_diff( array(1,4,8),  $transportTypes) , $areaFromID, $areaToID, $adultCount, $childCount);
                
                foreach( $other_private[$index] as $other_private_prod_key => $other_private_prod ){
                    if( $other_private_prod->getAreaFrom()->getId() !=  $areaFrom->getId() ){
                        unset($other_private[$index][$other_private_prod_key]);
                    }
                }
                
                $other_shared[$index]     = $productUtils->searchProduct( array_diff( array(2,3,5,6),  $transportTypes) , $areaFromID, $areaToID, $adultCount, $childCount);
                $jbj_included = 0;
                foreach( $other_shared[$index] as $other_shared_prod_key => $other_shared_prod ){
                    
                    if( $other_shared_prod->getAreaFrom()->getId() !=  $areaFrom->getId() ){
                        unset($other_shared[$index][$other_shared_prod_key]);
                    }
                    if( $other_shared_prod->getTransportationType()->getId() == 3 ){
                        $jbj_included++;
                    }
                    if( $jbj_included > 2 ){
                        unset($other_shared[$index][$other_shared_prod_key]);
                    }

                }

                if ($tripType === Product::TRIP_TYPE_ONE_WAY) {
                    break;
                }
                if ($tripType === Product::TRIP_TYPE_ROUND_TRIP && $index === 2) {
                    break;
                }
            }
        } catch (Throwable $e) {
            $error = true;
            $this->logError($e);
        }

        return $this->render('WicrewProductBundle:Product/Search:product.result.html.twig', [
            'results'           => $results,
            'other_private'     => $other_private,
            'other_shared'      => $other_shared,
            'other_airport'     => $other_airport,
            'trip'              => isset($trips) ? $trips : null,
            'transportTypes'    => isset($transportTypes) ? $transportTypes : array(),
            'returnDate'        => $returnDate,
            'tripType'          => $tripType = $this->getRequestDataNoThrow($request, "tripSearchType", null),
            'bannerSource'      => $bannerSource,
            'error'             => $error
        ]);
    }


     /**
     * Get Sub Areas For from select field. 
     *
     * @Route(path = "sub/areas", name = "get_sub_areas")
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function getSubFromAreas(Request $request) {
        try {
            $em = $this->get('doctrine.orm.entity_manager');
            $area_children   = $em->getRepository(AreaChildren::class)->findAll( );
            
            $childAreas = [];
            foreach ($area_children as $child_area) {
                $childAreas[] = [
                    'id'        => $child_area->getId(),
                    'name'      => $child_area->getName(),
                    'parent_id' => $child_area->getParentArea()->getId(),
                ];
            }
            
            return new JsonResponse([
                'status'            => 'ok',
                'area_children'  => $childAreas,
            ]);

        } catch (Throwable $e) {
            $this->logError($e);
            return new JsonResponse([
                'status'    => 'error',
                'message'   => $e->getMessage()
            ]);
        }
    }
}
