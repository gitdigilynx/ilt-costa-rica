<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\DateAvailability\Entity\HistoryLog;
use DateTime;


class DeleteNoteController extends BaseAdminController
{
    /**
     * @Route("admin/delete/note", name="delete_note")
     */
    public function index(): JsonResponse
    {
        try {
            if ( isset( $_POST['orderid'] ) ) {
                $order_id = $_POST['orderid'];
            } else {
                $response = array( "status"    => "failed", 'error' => 'Order ID not found!!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            if ( isset( $_POST['itemid'] ) ) {
                $item_id = $_POST['itemid'];
            } else {
                $response = array( "status"    => "failed", 'error' => 'Item ID not found!!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }
         
            $em = $this->getEM();
            $em->beginTransaction();

            $orderItemRepo              = $this->getDoctrine()->getManager()->getRepository(OrderItem::class);
            /* @var Order $order */
            $orderItem                  = $orderItemRepo->find( $item_id );
            // LOGGING INTO HISTORYLOG
            $old_value  = $orderItem->getCustomerNotes();
            if( !empty( trim($old_value ) ) ){
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
                $user       = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                $historyLog->setModifications("Note( $old_value ) deleted for booking #RJ$order_id" );
                $em->persist($historyLog);
                $em->flush();
            }    
            // LOGGING INTO HISTORYLOG
            $orderItem->setCustomerNotes( " " );

            $response = array( "status"    => "success" );

            $em->persist($orderItem);
            $em->flush();
            $em->commit();

        } catch (Throwable $e) {
            $em->rollback();
            $response = array( "status"    => "failed", 'error' => $e );
            return new JsonResponse( json_encode($response) );
            exit;
        }
        return new JsonResponse( json_encode($response) );
        exit;
    }
}
