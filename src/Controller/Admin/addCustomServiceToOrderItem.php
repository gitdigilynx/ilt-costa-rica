<?php


namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasCustomService;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use Symfony\Component\HttpFoundation\Request;
use DateTime;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use Throwable;
use App\Wicrew\DateAvailability\Entity\HistoryLog;

    
/**
 * Add Custom Service To Order Item
 */
class addCustomServiceToOrderItem extends BaseAdminController
{
    /**
     * @Route("admin/add/custom_service", name="add_custom_service_to_orderitem")
     * 
     * @param Request $request
     */
    public function addCustomService_func(Request $request): JsonResponse
    {
        try {
            $em     = $this->getEM();
            $em->beginTransaction();
            
            $customServiceLabel = $request->request->get('customServiceLabel');
            $customServicePrice = $request->request->get('customServicePrice');
            $item = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('item_id')]);
            $customServiceTaxRate = 13;
            
            $customServiceTax   = ($customServiceTaxRate / 100) * $customServicePrice;
            $oldItem    = clone $item; 

            // ADDING CUSTOM SERVICE TO ORDER ITEM
            $itemCustomService = new OrderItemHasCustomService();
            
          
            $itemCustomService->setLabel($customServiceLabel);
            $itemCustomService->setRackPrice($customServicePrice);
            
          
            $item->addCustomService($itemCustomService);

            // ADDON ADDED TO ORDER ITEM. NOW CHANGE PRICES FOR ORDER ITEM
            $item->setTitleRackPrice( $item->getTitleRackPrice() + $customServicePrice );
            $item->setSubtotalRack( $item->getSubtotalRack() + $customServicePrice );
            $item->setTotalTax( $item->getTotalTax() + $customServiceTax );
            $item->setGrandTotal( $item->getGrandTotal() + $customServicePrice + $customServiceTax );
           

            $item->setStatus(0); // MAKING ORDERITEM STATUS TO PENDING. 

            $em->persist($item);

            // ADDING TO PAYMENT HISTORY
            global $kernel;
            $token          = $kernel->getContainer()->get('security.token_storage')->getToken();
            $orderUtils     = $kernel->getContainer()->get('wicrew.order.utils');
            $newItem        = $item;
            /* @var User $user */
            $user           = $token->getUser();
            $custom_note    = $customServiceLabel." added to order item!";
            $orderHistory   = $orderUtils->createOrderHistory_UpdatedItem($newItem, $oldItem, $user, $custom_note);
            $newItem->addHistory($orderHistory);
            $order          = $newItem->getOrder();
            $order->addHistory($orderHistory);
            $em->persist($order);
            // ADDING TO PAYMENT HISTORY
            
            // LOGGING INTO HISTORYLOG
            // global $kernel;
            $historyLog = new HistoryLog();
            $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
            $order_id = $item->getOrder()->getId();
            $historyLog->setModifications("#RJ$order_id - ". $customServiceLabel . "  Added." );
            $em->persist($historyLog);
            $em->flush();
            // LOGGING INTO HISTORYLOG
    
            
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
            $order->setStatus(0); // MAKING ORDER STATUS TO PENDING/UNPAID
            $em->persist($order);
            $em->flush();
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
            $em->commit();


            return new JsonResponse( json_encode([
                'status'   => "success",
                'message'  => "Custom Service Added!",
            ]));

        } catch (Throwable $e) {
       
            $em->rollback();
            return new JsonResponse( json_encode([
                'status'   => "failed",
                'message'  => $e->getMessage(),
            ]));
        }

    }

}
