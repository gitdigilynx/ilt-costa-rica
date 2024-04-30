<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\DateAvailability\Entity\DateAvailability;

class DateAvailabilityController extends AbstractController
{
    /**
     * @Route("admin/date/availability", name="date_availability")
     */
    public function index(): JsonResponse
    {
        if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
            $start_date = $_POST['start_date'];
			$end_date   = $_POST['end_date'];
			// $start_date = date( "Y-m-d", strtotime( $start_date ) );
			// $end_date   = date( "Y-m-d", strtotime( $end_date ) );

            // $start_date = date('Y-m-d', strtotime( "+1 day", strtotime( $start_date ) ) );
			// $end_date   = date('Y-m-d', strtotime( "+1 day", strtotime( $end_date ) ) );
            $start_date     = explode('T',$start_date);
            $start_date     = $start_date[0];
            $end_date       = explode('T',$end_date);
            $end_date       = $end_date[0];
            


		} else {
			echo "Both Start & End Dates must be selected";
			exit;
		}

        if ( isset( $_POST['availability'] ) ) {
		
            $availability = $_POST['availability'];
		} else {
			echo "Availability value missing!";
			exit;
		}
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $entityManager)
        $entityManager = $this->getDoctrine()->getManager();
        
        $applied_dates = [];
      
        if($start_date != $end_date){
            $dates      = array();
            $current    = strtotime($start_date);
            $end_date   = strtotime($end_date);
            $stepVal    = '+1 day';
            while( $current <= $end_date ) {
                $dates[] = date('Y-m-d', $current);
                $current = strtotime($stepVal, $current);
            } 

            foreach($dates as $date){
                $specificDateRecords = $entityManager->getRepository(DateAvailability::class)->findBy(
                    ['date' => $date],
                );
                if (!$specificDateRecords) {

                    $dateAvailability = new DateAvailability();
                    $dateAvailability->setDate( $date );
                    $dateAvailability->setAvailability( $availability );
                    // tell Doctrine you want to (eventually) save the dateAvailability (no queries yet)
                    $entityManager->persist($dateAvailability);
                    // actually executes the queries (i.e. the INSERT query)
                    $entityManager->flush();
                    $_temp_arr["date"] = $date;
                    $_temp_arr["availability"] = $availability;
                    array_push($applied_dates, $_temp_arr);
                }else{
                    foreach($specificDateRecords as $specificDateRecord){
                        $specificDateRecord->setAvailability( $availability );
                        // tell Doctrine you want to (eventually) save the dateAvailability (no queries yet)
                        $entityManager->persist($specificDateRecord);
                        // actually executes the queries (i.e. the INSERT query)
                        $entityManager->flush();
                        $_temp_arr["date"] = $date;
                        $_temp_arr["availability"] = $availability;
                        array_push($applied_dates, $_temp_arr);
                    }
                }
            } 

        }else{
            
            $specificDateRecords = $entityManager->getRepository(DateAvailability::class)->findBy(
                ['date' => $start_date],
            );
            if (!$specificDateRecords) {

                $dateAvailability = new DateAvailability();
                $dateAvailability->setDate( $start_date );
                $dateAvailability->setAvailability( $availability );
                // tell Doctrine you want to (eventually) save the dateAvailability (no queries yet)
                $entityManager->persist($dateAvailability);
                // actually executes the queries (i.e. the INSERT query)
                $entityManager->flush();
                $_temp_arr["date"] = $start_date;
                $_temp_arr["availability"] = $availability;
                array_push($applied_dates, $_temp_arr);
            }else{
                foreach($specificDateRecords as $specificDateRecord){
                    $specificDateRecord->setAvailability( $availability );
                    // tell Doctrine you want to (eventually) save the dateAvailability (no queries yet)
                    $entityManager->persist($specificDateRecord);
                    // actually executes the queries (i.e. the INSERT query)
                    $entityManager->flush();
                    $_temp_arr["date"] = $start_date;
                    $_temp_arr["availability"] = $availability;
                    array_push($applied_dates, $_temp_arr);
                }
            }
        }
        $css_var = "<style>";
        if( count( $applied_dates ) > 0 ){
            foreach($applied_dates as $applied_date){
                if( $applied_date["availability"] == 1 ){
                    
                    $css_var .= 'td.fc-day[data-date="'.$applied_date["date"].'"] { background-color: #fff !important; }';
                }else{

                    $css_var .= 'td.fc-day[data-date="'.$applied_date["date"].'"] { background-color: pink !important; }';
                }
            }
        }
        $css_var .= "</style>";
        return new JsonResponse( $css_var );
        exit;
    }
}
