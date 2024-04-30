<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\AreaChildren;

class GetAreasAvailability extends AbstractController
{
    /**
     * @Route("/get/areas/availability", name="get_areas_availability")
     */
    public function index(): JsonResponse
    {
        if( isset( $_GET["transportation_type"] ) ){
            $transportation_type = $_GET["transportation_type"];
        }else{
            $_response_array    = array(
                "status"            => "error",
                "error_message"     => "Transportation Type Is Required!",
            );
    
            return new JsonResponse( json_encode( $_response_array ) );
            exit;
        }
        
        if (strpos($transportation_type, 'private-shuttles') !== false) {
            $transportation_type = (int)1;
        }else if (strpos($transportation_type, 'private-flight') !== false) {
            $transportation_type = (int)8;
        }else if (strpos($transportation_type, 'water-taxi') !== false) {
            $transportation_type = (int)6;
        }else{
            
            $_response_array    = array(
                "status"            => "error",
                "error_message"     => "Transportation type is not valid!",
            );
    
            return new JsonResponse( json_encode( $_response_array ) );
            exit;
        }
        $selected_pickArea = null;
        if( isset( $_GET["selected_pickArea"] ) ){
            $selected_pickArea = $_GET["selected_pickArea"];
        }
        $areas_to   = array();
        $areas_from = array();
        
        $productRepository  = $this->getDoctrine()->getRepository(Product::class);
        if($transportation_type == 6 && !is_null($selected_pickArea) && !empty($selected_pickArea)){ // water-taxi and has pick area
            $products = $productRepository->findBy(
                [
                    'transportationType'    => $transportation_type,
                    'archived'              => 0,
                    'areaFrom'              => $selected_pickArea
                ]
            );
        }else{
            $products = $productRepository->findBy(
                [
                    'transportationType'    => $transportation_type,
                    'archived'              => 0  
                ]
            );
        }
        
        if( count( $products ) > 0 ){
            foreach( $products as $product ){

                if($product->getAreaFrom()){
                    array_push( $areas_from, $product->getAreaFrom()->getName() );

                }
                if($product->getAreaTo()){
                    array_push( $areas_to,   $product->getAreaTo()->getName() );

                }
            }
        }
        $areas_from = array_map('strtolower', array_unique(  $areas_from  ));
        $areas_to   = array_map('strtolower', array_unique(  $areas_to  ));
        // if a shuttle is Going from A to B, it can also do from B to A
        $areas_from = array_merge($areas_from, $areas_to );
        $areas_to   = array_merge($areas_from, $areas_to );
        // if a shuttle is Going from A to B, it can also do from B to A
        $_response_array    = array(
            "status"        => "OK",
            "areas_from"    => $areas_from,
            "areas_to"      => $areas_to,
        );

        return new JsonResponse( json_encode( $_response_array ) );
        exit;
    }

    /**
     * @Route("/get/all/areas", name="get_all_areas")
     */
    public function getAllAreas(): JsonResponse
    {
        if( isset( $_GET["transportation_type"] ) ){
            $transportation_type = $_GET["transportation_type"];
        }else{
            $_response_array    = array(
                "status"            => "error",
                "error_message"     => "Transportation Type Is Required!",
            );
    
            return new JsonResponse( json_encode( $_response_array ) );
            exit;
        }
        
        if (strpos($transportation_type, 'private-shuttles') !== false) {
            $transportation_type = (int)1;
        }else if (strpos($transportation_type, 'private-flight') !== false) {
            $transportation_type = (int)8;
        }else if (strpos($transportation_type, 'water-taxi') !== false) {
            $transportation_type = (int)6;
        }else{
            
            $_response_array    = array(
                "status"            => "error",
                "error_message"     => "Transportation type is not valid!",
            );
    
            return new JsonResponse( json_encode( $_response_array ) );
            exit;
        }

        $areas_to   = array();
        $areas_from = array();
        
        $productRepository  = $this->getDoctrine()->getRepository(Product::class);
        $products           = $productRepository->findBy(
            ['transportationType' => $transportation_type  ]
        );
        
        if( count( $products ) > 0 ){
            foreach( $products as $product ){

                if($product->getAreaFrom()){
                    $area_id                = $product->getAreaFrom()->getId();
                    $area_type              = $product->getAreaFrom()->getType();
                    $area_name              = $product->getAreaFrom()->getName();
                    $area_children_result   = $this->getDoctrine()->getRepository(AreaChildren::class)->findBy(
                        ['parentArea' => $product->getAreaFrom()  ]
                    );
                    
                    $area_children          = array();
                    foreach($area_children_result as $area_child){
                        $area_children[$area_child->getId()] = $area_child->getName();
                    }

                    $areas_from[ $area_id ] = array(
                        "type"      => $area_type,
                        "name"      => $area_name,
                        "children"  => $area_children,
                    );

                }
                if($product->getAreaTo()){
                    $area_id                = $product->getAreaTo()->getId();
                    $area_type              = $product->getAreaTo()->getType();
                    $area_name              = $product->getAreaTo()->getName();
                    $area_children_result   = $this->getDoctrine()->getRepository(AreaChildren::class)->findBy(
                        ['parentArea' => $product->getAreaTo()  ]
                    );
                    
                    $area_children          = array();
                    foreach($area_children_result as $area_child){
                        $area_children[$area_child->getId()] = $area_child->getName();
                    }
                    $areas_to[ $area_id ] = array(
                        "type"      => $area_type,
                        "name"      => $area_name,
                        "children"  => $area_children,
                    );
                }
            }
        }
        // if a shuttle is Going from A to B, it can also do from B to A
        $areas_from = $areas_from + $areas_to;
        $areas_to   = $areas_from + $areas_to;

        $_response_array    = array(
            "status"        => "OK",
            "areas_from"    => $areas_from,
            "areas_to"      => $areas_to,
        );

        return new JsonResponse( json_encode( $_response_array ) );
        exit;
    }
}
