<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\SaleBundle\Entity\OrderHistory;
use App\Wicrew\SaleBundle\Entity\OrderItemHasCustomService;
use App\Wicrew\DateAvailability\Entity\HistoryLog;
use App\Wicrew\CoreBundle\Controller\Controller as Controller;
use Throwable;
use DateTime;
use App\Wicrew\CoreBundle\Service\Money;
use Exception;


class DeleteCustomServiceController extends Controller
{
    /**
     * @Route("/delete/custom/service", name="delete_custom_service")
     */
    public function index(): JsonResponse
    {
        try {
            if ( isset( $_POST['service_id'] ) ) {
                $service_id = $_POST['service_id'];
            } else {
                $response = array( "status"    => "failed", 'error' => 'Custom service not found!' );
                throw new Exception( json_encode($response) );
            }

     
            $em = $this->getDoctrine()->getManager();
            $em->beginTransaction();
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $custom_service_repo    = $em->getRepository(OrderItemHasCustomService::class);
            $custom_service         = $custom_service_repo->find( $service_id );
            if (!$custom_service) {

                $response = array( "status"    => "failed", 'error' => 'Custom service not found in repo or already deleted!' );
                throw new Exception( json_encode($response) );
            }
            $custom_service_label   = $custom_service->getLabel();
            $ordered_item           = $custom_service->getOrderItem();
            $order                  = $ordered_item->getOrder();

            $custom_service_price   = $custom_service->getRackPrice();

            // Mark the entity for removal
            $em->remove($custom_service);


            // $payment_history = new OrderHistory();
            // $payment_history->setType(OrderHistory::TYPE_UPDATED_ITEM);
            // $payment_history->setOrder($ordered_item->getOrder());
            // $payment_history->setUser($user);
            // $payment_history->setAmount( (new Money($custom_service_price + ( $custom_service_price * 0.13 ) ))->negate() );
            // $payment_history->setNotes("Custom service ($custom_service_label) removed!");
            // $ordered_item->addHistory($payment_hissstory);
            // $em->persist($ordered_item);

            // LOGGING INTO HISTORYLOG
            $historyLog = new HistoryLog();
            $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser($user);
            $historyLog->setModifications("Custom Service $custom_service_label removed!" );
            $em->persist($historyLog);
            // LOGGING INTO HISTORYLOG
            $em->commit();
            $em->flush();

        } catch (Throwable $e) {
            $this->logError($e);
            $em->rollback();
            $response = array( "status"    => "failed", 'error' => $e );
            return new JsonResponse( json_encode($response) );
            exit;
        }
        $response = array( "status" => "success", 'message' => 'Custom service removed!' );
        return new JsonResponse( json_encode($response) );
    }

}
