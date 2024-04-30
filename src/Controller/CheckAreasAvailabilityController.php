<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\DateAvailability\Entity\DateAvailability;
use App\Wicrew\DateAvailability\Entity\BookingBlockage;
use App\Wicrew\ProductBundle\Entity\Area;

class CheckAreasAvailabilityController extends AbstractController
{
      
    /**
     * @Route("/check/areas/availability", name="check_areas_availability")
     */
    public function index(): JsonResponse
    {
        $entityManager      = $this->getDoctrine()->getManager();
        $dl_area_from       = $_GET["dl_area_from"];      
        $dl_area_to         = $_GET["dl_area_to"];
        $selected_date      = $_GET["selected_date"];
        $selected_date      = new \DateTime($selected_date);
        $selected_date      = $selected_date->format('Y-m-d');
        $availability       = 'available';   
        $booking_blockages  = $entityManager->getRepository(BookingBlockage::class)->createQueryBuilder('bb')
        ->where('bb.timeFrom IS NULL')
        ->andWhere('bb.timeTo IS NULL')
        ->andWhere('bb.activities IS EMPTY')
        ->getQuery()
        ->getResult();
    
        if( count($booking_blockages) > 0 ){
            foreach( $booking_blockages as $booking_blockage ){
                $_blocked_vehicles = $booking_blockage->getVehicleTypes();
                if( count( $_blocked_vehicles ) ==0 ){

                    $_date      = ($booking_blockage->getDate()) ? $booking_blockage->getDate()->format('Y-m-d') : null;
                    
                    $_areas_from = [];  
                    if( count($booking_blockage->getAreasFrom()) > 0 ){
                        foreach($booking_blockage->getAreasFrom() as $_area_from){
                            array_push( $_areas_from, $_area_from->getId() );   
                        }
                    }
                    
                    $_areas_to = [];  
                    if( count($booking_blockage->getAreasTo()) > 0 ){
                        foreach($booking_blockage->getAreasTo() as $_area_to){
                            array_push( $_areas_to, $_area_to->getId() );   
                        }
                    }
                    if( (!is_null( $_date ) && $_date == $selected_date) || is_null($_date) ){
                        
                        if( !empty($_areas_from) && empty($_areas_to) ){
                            if( in_array($dl_area_from, $_areas_from) ){
                                $availability = 'not-available';   
                            }
                        }else if( empty($_areas_from) && !empty($_areas_to) ){
                            if( in_array($dl_area_to, $_areas_to) ){
                                $availability = 'not-available';   
                            }
                        }else if( !empty($_areas_from) && !empty($_areas_to) ){
                            if( in_array($dl_area_from, $_areas_from) && in_array($dl_area_to, $_areas_to) ){
                                $availability = 'not-available';   
                            }
                        }
                    }       
                }
            }
        }

        return new JsonResponse( $availability );

    }

     /**
     * @Route("/check/vehicle/availability", name="check_vehicle_availability")
     */
    public function checkVehicleAvailability(): JsonResponse
    {
        $entityManager          = $this->getDoctrine()->getManager();
        $selectedVehicleType    = $_GET["selectedVehicleType"];      
        $selected_date          = $_GET["selectedDate"];
        $selected_date          = new \DateTime($selected_date);
        $selected_date          = $selected_date->format('Y-m-d');
        $selectedAreaFrom       = $_GET["selectedAreaFrom"];
        $selectedAreaTo         = $_GET["selectedAreaTo"];

        $availability           = 'available';   

        $booking_blockages = $entityManager->getRepository(BookingBlockage::class)
        ->createQueryBuilder('b')
        ->where('b.activities IS EMPTY')
        ->getQuery()
        ->getResult();

        if( count($booking_blockages) > 0 ){
            foreach( $booking_blockages as $booking_blockage ){
                if( !empty($booking_blockage->getVehicleTypes()) ){
                    
                    $_vehicleTypes = [];  
                    if( count($booking_blockage->getVehicleTypes()) > 0 ){
                        foreach($booking_blockage->getVehicleTypes() as $_vehicle_type){
                            array_push( $_vehicleTypes, $_vehicle_type->getId() );   
                        }
                    }

                    $_areas_from = [];  
                    if( count($booking_blockage->getAreasFrom()) > 0 ){
                        foreach($booking_blockage->getAreasFrom() as $_area_from){
                            array_push( $_areas_from, $_area_from->getId() );   
                        }
                    }
                    
                    $_areas_to = [];  
                    if( count($booking_blockage->getAreasTo()) > 0 ){
                        foreach($booking_blockage->getAreasTo() as $_area_to){
                            array_push( $_areas_to, $_area_to->getId() );   
                        }
                    }

                    $blocked_date       = $booking_blockage->getDate() ? $booking_blockage->getDate()->format('Y-m-d') : null;
                    $blocked_timeFrom   = $booking_blockage->getTimeFrom() ? strtotime($booking_blockage->getTimeFrom()->format('H:i')) : null;
                    $blocked_timeTo     = $booking_blockage->getTimeTo() ? strtotime($booking_blockage->getTimeTo()->format('H:i')) : null;
                    if( in_array($selectedVehicleType, $_vehicleTypes) ){

                        if ( empty($_areas_from) && empty($_areas_to) && empty($blocked_timeFrom) && empty($blocked_timeTo) && (empty($blocked_date) || $blocked_date == $selected_date) ) {
                            $availability = 'not-available'; 
                        } elseif ( !empty($_areas_from) && empty($_areas_to) && empty($blocked_timeFrom) && empty($blocked_timeTo) && (empty($blocked_date) || ($blocked_date == $selected_date && in_array($selectedAreaFrom, $_areas_from))) ) {
                            $availability = 'not-available';
                        } elseif ( empty($_areas_from) && !empty($_areas_to) && empty($blocked_timeFrom) && empty($blocked_timeTo) && (empty($blocked_date) || ($blocked_date == $selected_date && in_array($selectedAreaTo, $_areas_to) )) ) {
                            $availability = 'not-available';
                        } elseif ( !empty($_areas_from) && !empty($_areas_to) && empty($blocked_timeFrom) && empty($blocked_timeTo) && (empty($blocked_date) || ($blocked_date == $selected_date && in_array($selectedAreaFrom, $_areas_from) && in_array($selectedAreaTo, $_areas_to) )) ) {
                            $availability = 'not-available';
                        }
                    }
                }
            }
        }

        return new JsonResponse( $availability );

    }

    /**
     * @Route("/check/time/availability", name="check_time_availability")
     */
    public function checkTimeAvailability(): JsonResponse
    {
        $entityManager       = $this->getDoctrine()->getManager();
        $availability        = 'available';   
        
        $selected_time       = $_GET["selected_time"];
        $selected_time       = strtotime($selected_time);
        
        $selected_date       = $_GET["selected_date"];
        $selected_date       = new \DateTime($selected_date);
        $selected_date       = $selected_date->format('Y-m-d');
        
        $selected_area_from  = json_decode($_GET["selected_area_from"]);
        $selected_area_to    = json_decode($_GET["selected_area_to"]);
        $selected_area_from  = $selected_area_from->id;
        $selected_area_to    = $selected_area_to->id;
        $selected_vehicle    = $_GET["selected_vehicle"];
        
        $booking_blockages = $entityManager->getRepository(BookingBlockage::class)
        ->createQueryBuilder('b')
        ->where('b.activities IS EMPTY')
        ->getQuery()
        ->getResult();

        if( count($booking_blockages) > 0 ){
            foreach( $booking_blockages as $booking_blockage ){
                
              
                $blocked_date       = $booking_blockage->getDate() ? $booking_blockage->getDate()->format('Y-m-d') : null;
                $blocked_timeFrom   = $booking_blockage->getTimeFrom() ? strtotime($booking_blockage->getTimeFrom()->format('H:i')) : null;
                $blocked_timeTo     = $booking_blockage->getTimeTo() ? strtotime($booking_blockage->getTimeTo()->format('H:i')) : null;
              
                
                $_vehicleTypes = [];  
                if( count($booking_blockage->getVehicleTypes()) > 0 ){
                    foreach($booking_blockage->getVehicleTypes() as $_vehicle_type){
                        array_push( $_vehicleTypes, $_vehicle_type->getId() );   
                    }
                }

                $_areas_from = [];  
                if( count($booking_blockage->getAreasFrom()) > 0 ){
                    foreach($booking_blockage->getAreasFrom() as $_area_from){
                        array_push( $_areas_from, $_area_from->getId() );   
                    }
                }
                
                $_areas_to = [];  
                if( count($booking_blockage->getAreasTo()) > 0 ){
                    foreach($booking_blockage->getAreasTo() as $_area_to){
                        array_push( $_areas_to, $_area_to->getId() );   
                    }
                }

                if (is_null($blocked_date) || $blocked_date == $selected_date) {
                    if (
                        (empty($_areas_from) && empty($_areas_to) && empty($_vehicleTypes)) ||
                        (!empty($_areas_from) && empty($_areas_to) && empty($_vehicleTypes) && in_array($selected_area_from, $_areas_from)) ||
                        (empty($_areas_from) && !empty($_areas_to) && empty($_vehicleTypes) && in_array($selected_area_to, $_areas_to)) ||
                        (!empty($_areas_from) && !empty($_areas_to) && empty($_vehicleTypes) && in_array($selected_area_from, $_areas_from) && in_array($selected_area_to, $_areas_to)) ||
                        (empty($_areas_from) && empty($_areas_to) && !empty($_vehicleTypes) && in_array($selected_vehicle, $_vehicleTypes)) ||
                        (!empty($_areas_from) && empty($_areas_to) && !empty($_vehicleTypes) && in_array($selected_area_from, $_areas_from) && in_array($selected_vehicle, $_vehicleTypes)) ||
                        (empty($_areas_from) && !empty($_areas_to) && !empty($_vehicleTypes) && in_array($selected_area_to, $_areas_to) && in_array($selected_vehicle, $_vehicleTypes)) ||
                        (!empty($_areas_from) && !empty($_areas_to) && !empty($_vehicleTypes) && in_array($selected_area_from, $_areas_from) && in_array($selected_area_to, $_areas_to) && in_array($selected_vehicle, $_vehicleTypes))
                    ) {
                        if (!is_null($blocked_timeFrom) && !is_null($blocked_timeTo)) {
                            if ($selected_time >= $blocked_timeFrom && $selected_time <= $blocked_timeTo) {
                                $availability = 'not-available';
                            }
                        } elseif (!is_null($blocked_timeFrom) && is_null($blocked_timeTo) && $selected_time >= $blocked_timeFrom) {
                            $availability = 'not-available';
                        } elseif (is_null($blocked_timeFrom) && !is_null($blocked_timeTo) && $selected_time <= $blocked_timeTo) {
                            $availability = 'not-available';
                        } elseif (is_null($blocked_timeFrom) && is_null($blocked_timeTo) ) {
                            $availability = 'not-available';
                        }
                    }
                }                               
            }
        }
        return new JsonResponse( $availability );

    }


    /**
     * @Route("/check/activity/availability", name="check_activity_availability")
     */
    public function checkActivityAvailability(): JsonResponse
    {
        $entityManager          = $this->getDoctrine()->getManager();
        $selected_activity      = $_GET["activity_id"];
        $selected_activity_type = $_GET["tour_type"];

        $selected_date          = $_GET["selected_date"];
        $selected_date          = new \DateTime($selected_date);
        $selected_date          = $selected_date->format('Y-m-d');

        $selected_time          = $_GET["selected_time"];
        $selected_time          = strtotime($selected_time);

        $availability           = 'available';   

        $booking_blockages = $entityManager->getRepository(BookingBlockage::class)
            ->createQueryBuilder('b')
            ->where('b.activities IS NOT EMPTY')
            ->getQuery()
            ->getResult();

        if( count($booking_blockages) > 0 ){

            foreach( $booking_blockages as $booking_blockage ){
                $blocked_date               = $booking_blockage->getDate() ? $booking_blockage->getDate()->format('Y-m-d') : null;
                $blocked_timeFrom           = $booking_blockage->getTimeFrom() ? strtotime($booking_blockage->getTimeFrom()->format('H:i')) : null;
                $blocked_timeTo             = $booking_blockage->getTimeTo() ? strtotime($booking_blockage->getTimeTo()->format('H:i')) : null;
                $blocked_activities         = $booking_blockage->getActivities();  
                $blocked_activities_type    = $booking_blockage->getActivityType();  
                if( strtolower($selected_activity_type) == strtolower($blocked_activities_type) ){
                    foreach( $blocked_activities as $blocked_activity ){
                        $blocked_activity_id = $blocked_activity->getId();
    
                        if( ( !is_null( $blocked_date ) && $selected_date == $blocked_date && $selected_activity == $blocked_activity_id ) || ( is_null( $blocked_date ) && $selected_activity == $blocked_activity_id ) ){
                            
                            if (!is_null($blocked_timeFrom) && !is_null($blocked_timeTo)) {
                                if ($selected_time >= $blocked_timeFrom && $selected_time <= $blocked_timeTo) {
                                    $availability = 'not-available';
                                }
                            } else if (!is_null($blocked_timeFrom) && is_null($blocked_timeTo) && $selected_time >= $blocked_timeFrom) {
                                $availability = 'not-available';
                            } else if (is_null($blocked_timeFrom) && !is_null($blocked_timeTo) && $selected_time <= $blocked_timeTo) {
                                $availability = 'not-available';
                            }else if ( is_null($blocked_timeFrom) && is_null($blocked_timeTo) ) {
                                $availability = 'not-available';
                            }
                        }   
                    }
                }
            }
        }
        return new JsonResponse( $availability );
    }


    /**
     * @Route("/check/jbj/availability", name="check_jbj_availability")
     */
    public function checkJbjAvailability(): JsonResponse
    {
        $selected_location      = $_GET["selected_location"];
        $selected_date          = $_GET["selected_date"];
        if(isset($_GET["selected_time"])){
            $select_jbj_type = 'Shared JBJ';
            $selected_time          = (array)json_decode($_GET["selected_time"]);
            $selected_time          = $selected_time['pickTime'];

        }else{
            $select_jbj_type    = 'Private JBJ';
            $selected_time      = $_GET["selected_picktime"];
        }
        if( empty($selected_location) ){
            return new JsonResponse( 'Please select JBJ location' );
        }

        if( $selected_date == 'Invalid date'){
            return new JsonResponse( 'Please select date' );
        }
        
        $selected_location      = (array)json_decode($_GET["selected_location"]);
        
        $selected_area_from     = $selected_location['fromID'];
        $selected_area_to       = $selected_location['toID']; 
        
        $entityManager          = $this->getDoctrine()->getManager();
        $selected_date          = new \DateTime($selected_date);
        $selected_date          = $selected_date->format('Y-m-d');
        $selected_time          = strtotime($selected_time);
        $availability           = 'available';   

        $booking_blockages = $entityManager->getRepository(BookingBlockage::class)
            ->createQueryBuilder('b')
            ->where('b.jbjType IS NOT NULL')
            ->getQuery()
            ->getResult();
        if( count($booking_blockages) > 0 ){

            foreach( $booking_blockages as $booking_blockage ){
                $blocked_date       = $booking_blockage->getDate() ? $booking_blockage->getDate()->format('Y-m-d') : null;
                $blocked_timeFrom   = $booking_blockage->getTimeFrom() ? strtotime($booking_blockage->getTimeFrom()->format('H:i')) : null;
                $blocked_timeTo     = $booking_blockage->getTimeTo() ? strtotime($booking_blockage->getTimeTo()->format('H:i')) : null;
                $blocked_jbj_type   = $booking_blockage->getJbjType();                

                $blocked_areas_from = [];  
                if( count($booking_blockage->getAreasFrom()) > 0 ){
                    foreach($booking_blockage->getAreasFrom() as $_area_from){
                        array_push( $blocked_areas_from, $_area_from->getId() );   
                    }
                }
                
                $blocked_areas_to = [];  
                if( count($booking_blockage->getAreasTo()) > 0 ){
                    foreach($booking_blockage->getAreasTo() as $_area_to){
                        array_push( $blocked_areas_to, $_area_to->getId() );   
                    }
                }
          
                $isFromBlocked  = !empty($blocked_areas_from) && in_array($selected_area_from, $blocked_areas_from);
                $isToBlocked    = !empty($blocked_areas_to) && in_array($selected_area_to, $blocked_areas_to);

                if (is_null($blocked_date) || $blocked_date == $selected_date) {
                    if (
                        ( empty($blocked_areas_from) && empty($blocked_areas_to) ) ||
                        ( empty($blocked_areas_to) && $isFromBlocked ) ||
                        ( empty($blocked_areas_from) && $isToBlocked ) ||
                        ( $isFromBlocked && $isToBlocked )
                    ){
                        if ( strtolower($blocked_jbj_type) == strtolower($select_jbj_type) ) {
                            if ( 
                                ( is_null($blocked_timeFrom) && is_null($blocked_timeTo) ) || 
                                ( !is_null($blocked_timeFrom) && !is_null($blocked_timeTo) && ($selected_time >= $blocked_timeFrom && $selected_time <= $blocked_timeTo) ) || 
                                ( !is_null($blocked_timeFrom) && is_null($blocked_timeTo) && $selected_time >= $blocked_timeFrom ) || 
                                ( is_null($blocked_timeFrom) && !is_null($blocked_timeTo) && $selected_time <= $blocked_timeTo )
                            ){
                                $availability = 'The JBJ with selected details is not available, Please change selected details.';
                            } 
                        }
                        
                    }
                }                         
            }
        }

        return new JsonResponse( $availability );
    }
}
