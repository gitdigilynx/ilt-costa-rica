<?php

namespace App\Wicrew\SaleBundle\Service;
require_once( dirname(__DIR__, 4) . "/frontapp/vendor/autoload.php" );

use DrewM\FrontApp\FrontApp;
use App\Entity\User;
use App\Wicrew\CoreBundle\Controller\Controller;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Controller\Admin\TransportationManagementController;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderHistory;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use Exception;
use Stripe as StripeCore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * OrderService
 */
class OrderService {

    /**
     * Utils
     *
     * @var Utils
     */
    protected $utils;

    /**
     * Constructor
     *
     * @param Utils $utils
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);
    }

    /**
     * Get utils
     *
     * @return Utils
     */
    public function getUtils(): Utils {
        return $this->utils;
    }

    /**
     * Set utils
     *
     * @param Utils $utils
     *
     * @return OrderService
     */
    public function setUtils(Utils $utils): OrderService {
        $this->utils = $utils;
        return $this;
    }

    /**
     * @param Order $order
     * @param User|null $user
     * @param Money $grandTotal
     *
     * @return OrderHistory
     */
    public function createOrderHistory_InitialTotal(Order $order, ?User $user, Money $grandTotal): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_CREATED_ORDER);
        $history->setOrder($order);
        $history->setUser($user);

        $history->setAmount($grandTotal);
        $history->setNotes($translator->trans('sale.checkout.create.order'));

        return $history;
    }

      /**
     * @param Order $order
     * @param User|null $user
     * @param Money $discountedAmount
     *
     * @return OrderHistory
     */
    public function createOrderHistory_discounted(Order $order, ?User $user, Money $discountedAmount): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_UPDATED_ITEM);
        $history->setOrder($order);
        $history->setUser($user);

        $history->setAmount('-'.$discountedAmount);
        $history->setNotes("Discount has been added!");

        return $history;
    }

    /**
     * @param Order $order
     * @param User|null $user
     * @param string $stripeChargeID
     * @param string|null $stripChargeDescription
     * @param string $stripeResponse
     * @param Money $totalCharged
     * @param string|null $note
     *
     * @return OrderHistory
     */
    public function createOrderHistory_StripeCharge(Order $order, ?User $user, string $stripeChargeID, ?string $stripChargeDescription, string $stripeResponse, Money $totalCharged, ?string $note = null): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_CHARGED);
        $history->setOrder($order);
        $history->setUser($user);

        $history->setStripeChargeId($stripeChargeID);
        if ($stripChargeDescription !== null) { $history->setStripeStatementDescription($stripChargeDescription); }
        $history->setStripeResponseStatus($stripeResponse);
        $history->setAmount($totalCharged);
        $history->setNotes($note === null ? $translator->trans('sale.checkout.order.card.payment') : $note);

        return $history;
    }

     /**
     * @param Order $order
     * @param User|null $user
     * @param Money $totalCharged
     * @param string|null $note
     *
     * @return OrderHistory
     */
    public function createOrderHistory_CashCharge(Order $order, ?User $user, Money $totalCharged, ?string $note = null): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_CHARGED);
        $history->setOrder($order);
        $history->setUser($user);
        $history->setStripeResponseStatus("cash");
        $history->setAmount($totalCharged);
        $history->setNotes($note === null ? "Cash/Wire payment" : $note);

        return $history;
    }

    /**
     * @param Order $order
     * @param User $user
     * @param string $stripeRefundID
     * @param string|null $stripChargeDescription
     * @param string $stripeResponse
     * @param Money $totalRefunded
     * @param string $note
     * @param OrderItem $orderItem
     *
     * @return OrderHistory
     */
    public function createOrderHistory_StripeRefund(Order $order, User $user, string $stripeRefundID, ?string $stripChargeDescription, string $stripeResponse, Money $totalRefunded, string $note, OrderItem $orderItem = null): OrderHistory {
        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_REFUNDED);
        $history->setOrder($order);
        if ($orderItem)  $history->setOrderItem($orderItem);
        $history->setUser($user);

        $history->setStripeRefundId($stripeRefundID);
        if ($stripChargeDescription !== null) { $history->setStripeStatementDescription($stripChargeDescription); }
        $history->setStripeResponseStatus($stripeResponse);
        $history->setAmount($totalRefunded);
        $history->setNotes($note);

        return $history;
    }

    /**
     * @param OrderItem $newItem
     * @param OrderItem $oldItem
     * @param User $user
     *
     * @return OrderHistory
     */
    public function createOrderHistory_UpdatedItem(OrderItem $newItem, OrderItem $oldItem, User $user, $custom_note = null, $oldItem_addons = null): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_UPDATED_ITEM);
        $history->setOrder($newItem->getOrder());
        $history->setUser($user);

        $oldItemPrice = new Money($oldItem->getGrandTotal());
        $newItemPrice = new Money($newItem->getGrandTotal());
        $itemPriceRackDiff = $newItemPrice->subtract($oldItemPrice);
        $history->setAmount( $itemPriceRackDiff );
        
        if( $oldItem->getStatus() == 2 ) {
            if( $itemPriceRackDiff->lessThanOrEqualStr('0') ){
                $history->setAmount( new Money($newItemPrice) );
            }
        }
        $oldItem_addons = $oldItem->getAddons();
        $newItem_addons = $newItem->getAddons();
        
        if( $oldItem_addons != $newItem_addons && count( $oldItem_addons ) == count( $newItem_addons ) ){
            $old_item_addons = [];    
            $old_item_addons_labels = [];    

            foreach( $oldItem_addons as $itemHasAddon ){
              
                array_push($old_item_addons, array(
                    'id'                    => $itemHasAddon->getId(),
                    'label'                 => $itemHasAddon->getLabel(),
                    'adultQty'              => $itemHasAddon->getAdultQuantity(),
                    'childQty'              => $itemHasAddon->getChildQuantity(),
                    'adultRackPrice'        => $itemHasAddon->getAdultRackPrice(),
                    'childRackPrice'        => $itemHasAddon->getChildRackPrice(),
                    'extraTransportation'   => $itemHasAddon->getExtraTransportation(),

                    
                ));
                array_push( $old_item_addons_labels, $itemHasAddon->getLabel() );
            }


            $new_item_addons = [];   
            $new_item_addons_labels = [];    
 
            foreach( $newItem_addons as $itemHasAddon ){
              
                array_push($new_item_addons, array(
                    'id'                    => $itemHasAddon->getId(),
                    'label'                 => $itemHasAddon->getLabel(),
                    'adultQty'              => $itemHasAddon->getAdultQuantity(),
                    'childQty'              => $itemHasAddon->getChildQuantity(),
                    'adultRackPrice'        => $itemHasAddon->getAdultRackPrice(),
                    'childRackPrice'        => $itemHasAddon->getChildRackPrice(),
                    'extraTransportation'   => $itemHasAddon->getExtraTransportation(),

                ));

                array_push( $new_item_addons_labels, $itemHasAddon->getLabel() );
            }

            $added_addons = [];
            foreach( $newItem_addons as $itemHasAddon ){   
                if( !in_array($itemHasAddon->getLabel(), $old_item_addons_labels  ) ){
                    array_push( $added_addons,  array(
                        'id'                    => $itemHasAddon->getId(),
                        'label'                 => $itemHasAddon->getLabel(),
                        'adultQty'              => $itemHasAddon->getAdultQuantity(),
                        'childQty'              => $itemHasAddon->getChildQuantity(),
                        'adultRackPrice'        => $itemHasAddon->getAdultRackPrice(),
                        'childRackPrice'        => $itemHasAddon->getChildRackPrice(),
                        'extraTransportation'   => $itemHasAddon->getExtraTransportation(),
                        'addon_cost'            => ( ( $itemHasAddon->getAdultRackPrice() + $itemHasAddon->getChildRackPrice() + $itemHasAddon->getExtraTransportation() ) * 0.13 ) + ( $itemHasAddon->getAdultRackPrice() + $itemHasAddon->getChildRackPrice() + $itemHasAddon->getExtraTransportation() ),
                    ) );
                }
            }
    
            $deleted_addons = [];
            foreach( $oldItem_addons as $itemHasAddon ){   
                if( !in_array($itemHasAddon->getLabel(), $new_item_addons_labels  ) ){
                    array_push( $deleted_addons,  array(
                        'id'                    => $itemHasAddon->getId(),
                        'label'                 => $itemHasAddon->getLabel(),
                        'adultQty'              => $itemHasAddon->getAdultQuantity(),
                        'childQty'              => $itemHasAddon->getChildQuantity(),
                        'adultRackPrice'        => $itemHasAddon->getAdultRackPrice(),
                        'childRackPrice'        => $itemHasAddon->getChildRackPrice(),
                        'extraTransportation'   => $itemHasAddon->getExtraTransportation(),
                        'addon_cost'            => ( ( $itemHasAddon->getAdultRackPrice() + $itemHasAddon->getChildRackPrice() + $itemHasAddon->getExtraTransportation() ) * 0.13 ) + ( $itemHasAddon->getAdultRackPrice() + $itemHasAddon->getChildRackPrice() + $itemHasAddon->getExtraTransportation() ),
                    ) );
                }
            }
        }


        if( !empty( $deleted_addons ) ){
            foreach( $deleted_addons as $deleted_addon ){
                // echo "<pre>deleted_addon: ".print_r($deleted_addon, true)." </pre>"; 
                $history_new = new OrderHistory();
                $history_new->setType(OrderHistory::TYPE_UPDATED_ITEM);
                $history_new->setOrder($newItem->getOrder());
                $history_new->setUser($user);
                $history_new->setAmount( "-".new Money($deleted_addon["addon_cost"]) );
                $history_new->setNotes( "Addon removed from item" );
                $newItem->addHistory($history_new);
                $order = $newItem->getDlOrder();
                $order->addHistory($history_new);
            }
        }

        if( !empty( $added_addons ) ){
            $added_cost = 0;
            foreach( $added_addons as $added_addon ){ 
                // echo "<pre>added_addon: ".print_r($added_addon, true)." </pre>";
                $added_cost = $added_cost + $added_addon["addon_cost"];
            }
            $history->setAmount( new Money($added_cost) );
            $custom_note = "Addon added to item";
        }

        $history->setData($oldItem);
        if( is_null( $custom_note ) ){

            $history->setNotes( $translator->trans('sale.checkout.order.update.item') );
        }else{    
            $history->setNotes( $custom_note );
        }

        return $history;
    }

    /**
     * @param OrderItem $item
     * @param User $user
     *
     * @return OrderHistory
     */
    public function createOrderHistory_AddedItem(OrderItem $item, User $user): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_ADDED_ITEM);
        $history->setOrder($item->getOrder());
        $history->setUser($user);

        $history->setAmount($item->getGrandTotal());
        $history->setNotes($translator->trans('sale.checkout.order.add.item'));

        return $history;
    }

    /**
     * @param Order $order
     * @param User $user
     *
     * @return OrderHistory
     */
    public function createOrderHistory_CanceledOrder(Order $order, User $user): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_CANCELED_ORDER);
        $history->setOrder($order);
        $history->setUser($user);

        $history->setAmount($order->getGrandTotal()['grandTotal']->negate());
        $history->setNotes($translator->trans('order.canceled'));

        return $history;
    }

    /**
     * @param OrderItem $item
     * @param User $user
     *
     * @return OrderHistory
     */
    public function createOrderHistory_CanceledItem(OrderItem $item, User $user): OrderHistory {
        $translator = $this->getUtils()->getTranslator();

        $history = new OrderHistory();
        $history->setType(OrderHistory::TYPE_CANCELED_ITEM);
        $history->setOrder($item->getOrder());
        $history->setUser($user);

        $history->setData($item);
        $history->setAmount((new Money($item->getGrandTotal()))->negate());
        $history->setNotes($translator->trans('sale.checkout.order.cancel.item'));

        return $history;
    }

    /**
     * get orderitem child with specific type
     *
     * @param string $type
     * @param OrderItem $parent
     *
     * @return OrderItem
     */
    public function getChildWithType($type, $parent) {
        $em = $this->getUtils()->getEntityManager();
        return $em->getRepository(OrderItem::class)->findOneBy(['type' => $type, 'parent' => $parent]);
    }

    public function performCharge(Order $order, Money $amount): array {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        $stripeService = $this->getUtils()->getContainer()->get('wicrew.core.stripe');

        if ($amount->greaterThanStr('0')) {
            if ($order->getStripeCustomerId()) {
                try {
                    $result = $stripeService->createCharge($order->getStripeCustomerId(), $amount, "Payment on invoice #RJ".$order->getId() );
                    $response = $result;
                    if (isset($response['status']) && $response['status'] == 'success') {
                        $response['message'] = 'Successfully charged "' . $amount . '" ' . $stripeService->getCurrency();
                    }
                } catch (Exception $e) {
                    $response['status'] = 'failed';
                    $response['message'] = $e->getMessage();
                }
            } else {
                $response['status'] = 'failed';
                $response['message'] = 'The order has no Stripe customer ID';
            }
        } else {
            $response['message'] = 'No amount to charge.';
        }

        return $response;
    }

    public function performRefund(Order $order, Money $amount): array {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        if ($amount->lessThanOrEqualStr('0')) {
            $response['message'] = 'No amount to refund.';
            return $response;
        }

        $amountDueInfo = $order->getOrderHistoryTotal();
        $amountDue = $amountDueInfo['totalDue'];
        $amountDue = $amountDue->negate(); // Total is negative for refunds.

        if ($amountDue->lessThanOrEqualStr('0')) {
            $response['message'] = "There aren't any amounts that need to be refunded in the order's history.";
            return $response;
        }

        if ($amountDue->lessThan($amount)) {
            $response['message'] = 'Can only refund a maximum of ' . $amountDue;
            return $response;
        }

        $stripeService = $this->getUtils()->getContainer()->get('wicrew.core.stripe');
        $refundedAmount = new Money();


        $amountCanRefund_arr = array();
        $transaction_to_refund = "";
        // Check on all charges in the history.
        foreach ($order->getChargedHistory() as $idx => $chargedHistory) {
            $result = $stripeService->getCharge($chargedHistory->getStripeChargeId());

            if (isset($result['status']) && $result['status'] == 'success') {
                // Check available amount to refund
                if (isset($result['data']) && $result['data'] instanceof StripeCore\Charge) {
                    $amountCanRefundInCents = ($result['data']->amount - $result['data']->amount_refunded);
                    $amountCanRefund = Money::fromCents($amountCanRefundInCents);
                    if ( !$amountCanRefund->lessThanOrEqualStr('0') ) {
                        array_push($amountCanRefund_arr, $amountCanRefund);
                    }
                    if( $amountCanRefund == $amount ) {
                        $transaction_to_refund = $idx;
                    }
                   
                }

            } 
        }
        $amountCanRefund_str = implode($amountCanRefund_arr, ", ");
        if($transaction_to_refund == ""){
            // Check on all charges in the history.
            foreach ($order->getChargedHistory() as $idx => $chargedHistory) {
                $result = $stripeService->getCharge($chargedHistory->getStripeChargeId());

                if (isset($result['status']) && $result['status'] == 'success') {
                    // Check available amount to refund
                    if (isset($result['data']) && $result['data'] instanceof StripeCore\Charge) {
                        $amountCanRefundInCents = ($result['data']->amount - $result['data']->amount_refunded);
                        $amountCanRefund = Money::fromCents($amountCanRefundInCents);

                        if( $amountCanRefund >= $amount ) {
                            $transaction_to_refund = $idx;
                        }
                    
                    }

                } 
            }
        }
        if ($transaction_to_refund == "") {
            $response['status'] = 'failed';
            $response['message'] = "Can't refund the whole amount, Need to refund amount in following parts: $amountCanRefund_str";
            return $response;

        }
        
        // Check on all charges in the history.
        foreach ($order->getChargedHistory() as $idx => $chargedHistory) {
            $result = $stripeService->getCharge($chargedHistory->getStripeChargeId());

            if (isset($result['status']) && $result['status'] == 'success') {
                // Check available amount to refund
                if (isset($result['data']) && $result['data'] instanceof StripeCore\Charge) {
                    $amountCanRefundInCents = ($result['data']->amount - $result['data']->amount_refunded);
                    $amountCanRefund = Money::fromCents($amountCanRefundInCents);
                    
                    // CHECK WHICH TRANSACTION NEEDS TO BE REFUNDED
                    if( $transaction_to_refund != $idx ){
                        continue;
                    }
                    
                    // Is there any amount in this charge that can be refunded?
                    if ($amountCanRefund->greaterThanStr('0')) {
                        $amountToRefund = $amount->subtract($refundedAmount);
                        if ($amountToRefund->greaterThan($amountCanRefund)) {
                            $amountToRefund = $amountCanRefund;
                        }
                        $result = $stripeService->createRefund($chargedHistory->getStripeChargeId(), $amountToRefund);

                        // Successfully refunded?
                        if (isset($result['status']) && $result['status'] == 'success') {
                            $response = $result;
                            $refundedAmount = $refundedAmount->add($amountToRefund);

                            $response['message'] = 'Successfully refunded "' . $refundedAmount . '" from "' . $amountDue . '" ' . $stripeService->getCurrency();

                            if ($refundedAmount->equals($amount)) {
                                break;
                            }
                        } else {
                            $response = $result;
                            break;
                        }
                    }
                } else {
                    $response['status'] = 'failed';
                    $response['message'] = 'Can refund "' . $refundedAmount . '" from "' . $amountDue . '" ' . $stripeService->getCurrency();
                    break;
                }
            } else {
                $response['status'] = 'failed';
                $response['message'] = 'Invalid charge ID "' . $chargedHistory->getStripeChargeId() . '"';
                break;
            }
        }

        if ($refundedAmount->equals(new Money('0'))) {
            $response['status'] = 'failed';
            $response['message'] = 'Cannot refund on any charge';
        }

        return $response;
    }

    /**
     * @param OrderItemHasDriver $item
     *
     * @return string|null
     */
    public function getDriverVehicleImage(OrderItemHasDriver $item): ?string {
        if ($item->getVehicle() !== null) {
            $uploadHelper = $this->getUtils()->getContainer()->get('vich_uploader.templating.helper.uploader_helper');
            return $uploadHelper->asset($item->getVehicle()->getVehicleType(), 'imageFile');
        }
        return null;
    }

    public function getItemDriverVehicleImage(OrderItem $item): ?string {
        if ($item->getVehicle() !== null) {
            $uploadHelper = $this->getUtils()->getContainer()->get('vich_uploader.templating.helper.uploader_helper');
            return $uploadHelper->asset($item->getVehicle()->getVehicleType(), 'imageFile');
        }
        return null;
    }

    /**
     * @param OrderItem $item
     *
     * @return string|null
     */
    public function getOrderItemImage(OrderItem $item): ?string {
        $uploadHelper = $this->getUtils()->getContainer()->get('vich_uploader.templating.helper.uploader_helper');

        if ($item->getProduct() !== null) {
            return $uploadHelper->asset($item->getProduct()->getVehicleType(), 'imageFile');
        } else if ($item->getActivity() !== null && $item->getActivity()->getSlides()->count() > 0) {
            return $uploadHelper->asset($item->getActivity()->getSlides()->first(), 'imageFile');
        }

        return null;
    }

    /**
     * @param Partner $driver
     * @param TransportationManagementController|Controller $controller
     */
    public function sendDriverEmails(Partner $driver, $controller, $assignments = null, $orders = null, $type = 'product'): void {
        $coreUtils = $this->utils->getContainer()->get('wicrew.core.utils');
        $translator = $this->utils->getTranslator();

        if (!$orders && !$assignments) {
            return;
        }


        if ($type == 'new_note') {
            $subject    = "You've received an update note for this service";
            $template   = 'WicrewSaleBundle:Admin:Email/order.mail.newnotesdriver.html.twig';
        }else if ($type == 'Activity') {
            $subject    = "Supplier Email (Tours)";
            $template   = 'WicrewSaleBundle:Admin:Email/order.mail.assigndriver.html.twig';
        } else {
            $subject    = "Assign Driver Email ($type)";
            $template   = 'WicrewSaleBundle:Admin:Email/order.mail.assignsupplier.html.twig';
        }
        $bodyDriver = $controller->renderTwigToString($template, [
            'assignments'   => $assignments,
            'orders'        => $orders,
            'driver'        => $driver,
            'toAdmin'       => false
        ]);
     
        $from = $coreUtils->getContainer()->getParameter('system_email');
        $to = $coreUtils->getContainer()->getParameter('system_email');
        $replyTo = $from;

        $toDriver = $driver->getEmail();

        $mailer = $this->utils->getContainer()->get('wicrew.core.mailer');
      
        if (get_class($controller) == 'App\Wicrew\SaleBundle\Controller\Admin\ActivityManagementController') {
            $replyTo = 'activity@iltcostarica.com';
        }
       
        $mailer->send([
            'from'      => $from,
            'to'        => $toDriver,
            'replyTo'   => $replyTo,
            'subject'   => $subject,
            'body'      => $bodyDriver
        ]);

        foreach ($assignments as $item) {
            $item->setSendEmail(null);  
        } 
        foreach ($orders as $item) {
            $item->setSendEmail(null);  
        } 
       
    }

    /**
     * @param Partner $driver
     * @param TransportationManagementController|Controller $controller
     */
    public function sendDriverEmailsFromFront(Partner $driver, $controller, $assignments = null, $orders = null, $type = 'product'): void {
        $coreUtils = $this->utils->getContainer()->get('wicrew.core.utils');

        if (!$orders && !$assignments) {
            return;
        }


        if ($type == 'Activity') {
            $subject    = "Supplier Email (Tours)";
            $template   = 'WicrewSaleBundle:Admin:Email/order.mail.assigndriver.html.twig';
        } else {
            $subject    = "Assign Driver Email ($type)";
            $template   = 'WicrewSaleBundle:Admin:Email/order.mail.assignsupplier.html.twig';
        }
        $bodyDriver = $controller->renderTwigToString($template, [
            'assignments'   => $assignments,
            'orders'        => $orders,
            'driver'        => $driver,
            'toAdmin'       => false
        ]);
        

        $FrontApp = new FrontApp('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzY29wZXMiOlsic2hhcmVkOioiLCJwcml2YXRlOioiLCJwcm92aXNpb25pbmciXSwiaWF0IjoxNjYwNjQwMzE1LCJpc3MiOiJmcm9udCIsInN1YiI6IjJkN2ZmNWNmMDM3MWMyZDJkNWQyIiwianRpIjoiYzA3ZDdjZjg3NjViYTk3OSJ9.TVtZ8NAMUL1G-tYTkpmFGHpbylBIEaJyP2J3LMd0dPg');

        $comment    = (object) [ 'body' => $bodyDriver ];
        $inboxes    = $FrontApp->get("inboxes");
            
        if( array_key_exists( "_error", $inboxes ) ){ echo $inboxes["message"]; return; }
        
        $inbox_id   = "";
        foreach($inboxes["_results"] as $inboxes_result ){
            if( strtolower( $inboxes_result["name"] ) == strtolower( "teams new inbox" ) ){
                $inbox_id = $inboxes_result["id"];
            }
        }

        if( !empty( $inbox_id ) ){

            $create_conversation = $FrontApp->post("conversations", [
                'type'          => 'discussion',
                'inbox_id'      => $inbox_id,
                'subject'       => $subject,
                'comment'       => $comment,
            ]);
            
            if( array_key_exists( "_error", $create_conversation ) ){ echo $create_conversation["message"]; return; }

            print_r($create_conversation);
            
        }else{

        }
       
    }
}
