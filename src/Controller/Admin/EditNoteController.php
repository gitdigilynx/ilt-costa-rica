<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\DateAvailability\Entity\HistoryLog;
use DateTime;

use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;



class EditNoteController extends BaseAdminController
{
    /**
     * @Route("admin/edit/note", name="edit_note")
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
            
            if ( isset( $_POST['newNote'] ) ) {
                $new_note = $_POST['newNote'];
            } else {
                $response = array( "status"    => "failed", 'error' => 'New Note not added!!' );
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
            $new_value  = $new_note;
            
            if( $old_value != $new_value ){
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
                $user       = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                $historyLog->setModifications("Note edited from $old_value to $new_value for booking #RJ$order_id" );
                $em->persist($historyLog);
                $em->flush();
            }
            // LOGGING INTO HISTORYLOG

            $orderItem->setCustomerNotes( $new_note );

            $response = array( "status"    => "success" );

            $this->addFlash('success', "Note has been updated succesfully!");

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
