<?php


namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use Throwable;

    
/**
 * Tax Configuration Controller
 */
class TaxConfigurationController extends BaseAdminController
{
    /**
     * @Route("admin/taxConfig", name="TaxConfiguration")
     */
    public function index(): Response
    {
      
        $shuttles = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
            'label' => "shuttles"
        ]);
        if ( count($shuttles) > 0 ){
            $shuttles = $shuttles[0];
        }

        $water_taxi = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
            'label' => "water-taxi"
        ]);    
        if ( count($water_taxi) > 0 ){
            $water_taxi = $water_taxi[0];
        } 

        $jbj = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
            'label' => "jbj"
        ]);
        if ( count($jbj) > 0 ){
            $jbj = $jbj[0];
        }

        $flights = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
            'label' => "flights"
        ]); 
        if ( count($flights) > 0 ){
            $flights = $flights[0];
        }

        $tours = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
            'label' => "tours"
        ]); 
        if ( count($tours) > 0 ){
            $tours = $tours[0];
        } 

        $addons = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
            'label' => "addons"
        ]); 
        if ( count($addons) > 0 ){
            $addons = $addons[0];
        }

        $extras = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
            'label' => "extras"
        ]);  
        if ( count($extras) > 0 ){
            $extras = $extras[0];
        }
        
        return $this->render('tax_config/index.html.twig', [
            'shuttles'      => $shuttles,
            'water_taxi'    => $water_taxi,
            'jbj'           => $jbj,
            'flights'       => $flights,
            'tours'         => $tours,
            'addons'        => $addons,
            'extras'        => $extras,
        ]);
    }

    /**
     * @Route("admin/taxConfig/save", name="save_tax_config")
     */
    public function saveAction(): JsonResponse
    {
        try {

        
            $em = $this->getEM();
            $em->beginTransaction();

            $shuttles = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
                'label' => "shuttles"
            ]);     
            if (count($shuttles) == 0){

                $shuttles = new TaxConfig();
                $shuttles->setLabel("shuttles");
            }else{
                $shuttles = $shuttles[0];
            }

            $water_taxi = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
                'label' => "water-taxi"
            ]);     
            if (count($water_taxi) == 0){

                $water_taxi = new TaxConfig();
                $water_taxi->setLabel("water-taxi");
            }else{
                $water_taxi = $water_taxi[0];
            }
        
            $jbj = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
                'label' => "jbj"
            ]);     
            if (count($jbj) == 0){
                $jbj = new TaxConfig();
                $jbj->setLabel("jbj");
            }else{
                $jbj = $jbj[0];
            }

            $flights = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
                'label' => "flights"
            ]);     
            if (count($flights) == 0){
                $flights = new TaxConfig();
                $flights->setLabel("flights");
            }else{
                $flights = $flights[0];
            }
        

            $tours = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
                'label' => "tours"
            ]);     
            if (count($tours) == 0){
                $tours = new TaxConfig();
                $tours->setLabel("tours");
            }else{
                $tours = $tours[0];
            }

            $addons = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
                'label' => "addons"
            ]);     
            if (count($addons) == 0){
                $addons = new TaxConfig();
                $addons->setLabel("addons");
            }else{
                $addons = $addons[0];
            }

            $extras = $this->getDoctrine()->getRepository(TaxConfig::class)->findBy([
                'label' => "extras"
            ]);     
            if (count($extras) == 0){
                $extras = new TaxConfig();
                $extras->setLabel("extras");
            }else{
                $extras = $extras[0];
            }


            if ( isset( $_POST['shuttles_tax_jan_may'] ) ) {
                $shuttles_tax_jan_may = $_POST['shuttles_tax_jan_may'];
                
                $shuttles->setJanMayRate($shuttles_tax_jan_may);


            } else {
                $response = array( "status"    => "failed", 'message' => 'shuttles_tax_jan_may data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['watertaxi_tax_jan_may'] ) ) {
                $watertaxi_tax_jan_may = $_POST['watertaxi_tax_jan_may'];

                $water_taxi->setJanMayRate($watertaxi_tax_jan_may);
                

            } else {
                $response = array( "status"    => "failed", 'message' => 'watertaxi_tax_jan_may data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['jbj_tax_jan_may'] ) ) {
                $jbj_tax_jan_may = $_POST['jbj_tax_jan_may'];

                $jbj->setJanMayRate($jbj_tax_jan_may);
               
                
            } else {
                $response = array( "status"    => "failed", 'message' => 'jbj_tax_jan_may data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['flights_tax_jan_may'] ) ) {
                $flights_tax_jan_may = $_POST['flights_tax_jan_may'];

                $flights->setJanMayRate($flights_tax_jan_may);
              
                
            } else {
                $response = array( "status"    => "failed", 'message' => 'flights_tax_jan_may data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['tours_tax_jan_may'] ) ) {
                $tours_tax_jan_may = $_POST['tours_tax_jan_may'];
                
                $tours->setJanMayRate($tours_tax_jan_may);
                
            } else {
                $response = array( "status"    => "failed", 'message' => 'tours_tax_jan_may data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['addons_tax_jan_may'] ) ) {
                $addons_tax_jan_may = $_POST['addons_tax_jan_may'];
               
                $addons->setJanMayRate($addons_tax_jan_may);
                
            } else {
                $response = array( "status"    => "failed", 'message' => 'addons_tax_jan_may data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['extras_tax_jan_may'] ) ) {
                $extras_tax_jan_may = $_POST['extras_tax_jan_may'];
                
                $extras->setJanMayRate($extras_tax_jan_may);
                
            } else {
                $response = array( "status"    => "failed", 'message' => 'extras_tax_jan_may data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['shuttles_tax_jun_dec'] ) ) {
                $shuttles_tax_jun_dec = $_POST['shuttles_tax_jun_dec'];
                $shuttles->setJunDecRate( $shuttles_tax_jun_dec );
            } else {
                $response = array( "status"    => "failed", 'message' => 'shuttles_tax_jun_dec data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }
            
            if ( isset( $_POST['watertaxi_tax_jun_dec'] ) ) {
                $watertaxi_tax_jun_dec = $_POST['watertaxi_tax_jun_dec'];
                $water_taxi->setJunDecRate( $watertaxi_tax_jun_dec );
            } else {
                $response = array( "status"    => "failed", 'message' => 'watertaxi_tax_jun_dec data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }
            
            if ( isset( $_POST['jbj_tax_jun_dec'] ) ) {
                $jbj_tax_jun_dec = $_POST['jbj_tax_jun_dec'];
                $jbj->setJunDecRate( $jbj_tax_jun_dec );
            } else {
                $response = array( "status"    => "failed", 'message' => 'jbj_tax_jun_dec data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }
            
            if ( isset( $_POST['flights_tax_jun_dec'] ) ) {
                $flights_tax_jun_dec = $_POST['flights_tax_jun_dec'];
                $flights->setJunDecRate( $flights_tax_jun_dec );
            } else {
                $response = array( "status"    => "failed", 'message' => 'flights_tax_jun_dec data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }
            
            if ( isset( $_POST['tours_tax_jun_dec'] ) ) {
                $tours_tax_jun_dec = $_POST['tours_tax_jun_dec'];
                $tours->setJunDecRate( $tours_tax_jun_dec );
            } else {
                $response = array( "status"    => "failed", 'message' => 'tours_tax_jun_dec data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }
            
            if ( isset( $_POST['addons_tax_jun_dec'] ) ) {
                $addons_tax_jun_dec = $_POST['addons_tax_jun_dec'];
                $addons->setJunDecRate( $addons_tax_jun_dec );
            } else {
                $response = array( "status"    => "failed", 'message' => 'addons_tax_jun_dec data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }
            
            if ( isset( $_POST['extras_tax_jun_dec'] ) ) {
                $extras_tax_jun_dec = $_POST['extras_tax_jun_dec'];
                $extras->setJunDecRate( $extras_tax_jun_dec );
            } else {
                $response = array( "status"    => "failed", 'message' => 'extras_tax_jun_dec data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            
            $em->persist($shuttles);
            $em->persist($water_taxi);
            $em->persist($jbj);
            $em->persist($flights);
            $em->persist($tours);
            $em->persist($addons);
            $em->persist($extras);
            $em->commit();
            $em->flush();




            $this->addFlash('success', "Tax rates has been saved!");
            $response = array( "status" => "success", "message" => "Tax rates has been saved!" );
            return new JsonResponse( json_encode($response) );

        } catch (Throwable $e) {
            $em->rollback();
            $response = array( "status"    => "failed", 'message' => $e->getMessage() );
            return new JsonResponse( json_encode($response) );
        }
    }
}



