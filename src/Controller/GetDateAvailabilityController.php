<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\DateAvailability\Entity\DateAvailability;

class GetDateAvailabilityController extends AbstractController
{
    /**
     * @Route("/get/date/availability", name="get_date_availability")
     */
    public function index( ): JsonResponse
    {
        $current_date       = $_GET["current_date"];
        $current_date       = date( "Y-m-d", strtotime( $current_date ) );
        $first_month_day    = date( 'Y-m-01', strtotime( $current_date ) );
        $last_month_day     = date( 'Y-m-t', strtotime( $current_date ) );
        $entityManager      = $this->getDoctrine()->getManager();


        $qb = $entityManager->createQueryBuilder('d')
            ->select('d.date')
            ->from(DateAvailability::class, 'd')
            ->where("d.date >= '$first_month_day'")
            ->andWhere("d.date <= '$last_month_day'")
            ->andWhere("d.availability = 0");
        $query = $qb->getQuery();
        $notAvailableDates  = $query->execute();
        $css_var = "<style>";
        foreach($notAvailableDates as $notAvailableDate){
            $date = $notAvailableDate["date"];
            $css_var .= 'td.fc-day[data-date="'.$date.'"] { background-color: pink !important; }';
        }
        $css_var .= "</style>";
        
        return new JsonResponse( $css_var );

        exit;
    }
}
