<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\AreaChildren;

class GetToAreasAagainstSelected extends AbstractController
{
    /**
     * @Route("/get/to/areas/options", name="get_to_areas_options")
     */
    public function index(): JsonResponse
    {
        if( isset( $_GET["selected_value"] ) ){
            $selected_value = $_GET["selected_value"];
        }else{
            $_response_array    = array(
                "status"            => "error",
                "error_message"     => "Selected Value Is Required!",
            );
    
            return new JsonResponse( json_encode( $_response_array ) );
            exit;
        }
        
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

        $productRepository  = $this->getDoctrine()->getRepository(Product::class);        
        $productsFrom           = $productRepository->findBy(
            [
                'areaFrom'              => $selected_value,
                'transportationType'    => $transportation_type,
                'archived'              => 0,

            ]
        );

        $products = $productsFrom;
        $areas_to   = array();
        if( count( $products ) > 0 ){
            foreach( $products as $product ){
                if($product->getAreaTo()){
                    $areas_to[$product->getAreaTo()->getId()] = $product->getAreaTo()->getName();
                }
            }
        }
        else{
            $_response_array    = array(
                "status"            => "error",
                "error_message"     => "No TO area Available, Select any other From option!",
            );
    
            return new JsonResponse( json_encode( $_response_array ) );
            exit;
        }     
       
        $areas_to           = json_decode(json_encode($areas_to));
        $_response_array    = array(
            "status"        => "OK",
            "areas_to"      => $areas_to,
        );

        return new JsonResponse( json_encode( $_response_array ) );
        exit;
    }
}
