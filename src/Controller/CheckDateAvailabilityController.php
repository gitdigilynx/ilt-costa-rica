<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\DateAvailability\Entity\DateAvailability;
use App\Wicrew\DateAvailability\Entity\BookingBlockage;

class CheckDateAvailabilityController extends AbstractController
{
    /**
     * @Route("/check/date/availability", name="check_date_availability")
     */
    public function index(): JsonResponse
    {
        
        $selected_date      = $_GET["selected_date"];
        // $selected_date   = date( 'Y-m-d', strtotime( "+1 day", strtotime( $selected_date ) ) );
        $selected_date      = explode('T',$selected_date);
        $selected_date      = $selected_date[0];
        $entityManager      = $this->getDoctrine()->getManager();

        $qb = $entityManager->createQueryBuilder('d')
            ->select('d.availability')
            ->from(DateAvailability::class, 'd')
            ->where("d.date = '$selected_date'");
           
        $query = $qb->getQuery();
        $notAvailableDates  = $query->execute();
        if( count( $notAvailableDates ) > 0 ){
            if ( $notAvailableDates[0]["availability"] == 0 ){
                $availability = 'not-available';
            }else{
                $availability = 'available';
            } 

        }else{
            $availability = 'available';
        }

        $booking_blockages = $entityManager->getRepository(BookingBlockage::class)->createQueryBuilder('bb')
        ->where('bb.date = :selected_date')
        ->andWhere('bb.timeFrom IS NULL')
        ->andWhere('bb.timeTo IS NULL')
        ->andWhere('bb.activities IS EMPTY')
        ->setParameter('selected_date', new \DateTime($selected_date))
        ->getQuery()
        ->getResult();

        foreach($booking_blockages as $booking_blockage){
            if( count( $booking_blockage->getVehicleTypes() ) == 0  && count( $booking_blockage->getAreasFrom() ) == 0  && count( $booking_blockage->getAreasFrom() ) == 0 ){
                $availability = 'not-available'; 
                break; // Exit the loop as soon as availability is determined  
            }   
        }

        return new JsonResponse( $availability );

    }

    
    /**
     * @Route("/check/date/availability/multi", name="check_date_availability_multi")
     */
    public function checkDateAvailabilityMulti(): JsonResponse
    {
        
        $selected_dates      = $_GET["selected_date"];
        $availability = true;
        foreach( $selected_dates as $selected_date ){
            $selected_date      = explode('T',$selected_date);
            $selected_date      = $selected_date[0];
            $entityManager      = $this->getDoctrine()->getManager();

            $qb = $entityManager->createQueryBuilder('d')
                ->select('d.availability')
                ->from(DateAvailability::class, 'd')
                ->where("d.date = '$selected_date'");
            
            $query = $qb->getQuery();
            $notAvailableDates  = $query->execute();
            if( count( $notAvailableDates ) > 0 ){
                if ( $notAvailableDates[0]["availability"] == 0 ){
                    $availability = false;
                }
            }

            $booking_blockages = $entityManager->getRepository(BookingBlockage::class)->createQueryBuilder('bb')
            ->where('bb.date = :selected_date')
            ->andWhere('bb.timeFrom IS NULL')
            ->andWhere('bb.timeTo IS NULL')
            ->andWhere('bb.activities IS EMPTY')
            ->setParameter('selected_date', new \DateTime($selected_date))
            ->getQuery()
            ->getResult();
            
            foreach($booking_blockages as $booking_blockage){
                if( count( $booking_blockage->getVehicleTypes() ) == 0  && count( $booking_blockage->getAreasFrom() ) == 0  && count( $booking_blockage->getAreasFrom() ) == 0 ){
                    $availability = false;   
                    break; // Exit the loop as soon as availability is determined
                }   
            }
    
        }
        if($availability){
            return new JsonResponse( "available" );
        }else{
            return new JsonResponse( "not-available" );
        }
    }
}
