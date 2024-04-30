<?php

namespace App\Wicrew\SaleBundle\Controller;

use App\Wicrew\CoreBundle\Controller\Controller as Controller;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransportController extends Controller {
    /**
     * Transportation management save
     *
     * @Route(
     *     path = "transportationmanagement/driver_email",
     *     name="driver_email",
     *     methods={ "GET" }
     *     )
     *
     * @throws Exception
     */
    public function driverEmailAction() {
        // /* @var OrderItemHasDriver[] $itemDrivers */
        // $reset = true;
        // while ($reset) {
        //     $reset = false;
        //     $itemDrivers = $this->getEM()->getRepository(OrderItemHasDriver::class)->createQueryBuilder('oid')
        //         ->where("oid.sendEmail IS NOT NULL")
        //         ->getQuery()
        //         ->getResult();

        //     foreach ($itemDrivers as $itemDriver) {
        //         $timeThen = $itemDriver->getSendEmail();
        //         $timeNow = new DateTime("+1 hour");
        //         $interval = $timeThen->diff($timeNow);
        //         if ($interval->d >= 1) {
        //             if ($itemDriver->getDriver() === null || $itemDriver->getDriver()->getEmail() === null) {
        //                 $itemDriver->setSendEmail(null);
        //                 continue;
        //             }
        //             $utils = $this->get('wicrew.order.utils');
        //             $utils->sendDriverEmails($itemDriver->getDriver(), $this);
        //             echo($itemDriver->getId() . ", ");

        //             // Reset the list in case an assignment in it was just sent.
        //             $this->getEM()->flush();
        //             $reset = true;
        //             break;
        //         }
        //     }
        // }

        // $this->getEM()->flush();

        // if (count($itemDrivers) > 0) {
        //     return new Response('Banana sunday.');
        // } else {
        //     return new Response('Nothing here buds.');
        // }
    }
}
