<?php

namespace App\Wicrew\SaleBundle\Controller\Admin;

use App\Entity\User;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\CoreBundle\Service\Stripe;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderHistory;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasAddon;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use App\Wicrew\SaleBundle\Service\OrderService;
use Exception;
use RuntimeException;
use DateTime;
use App\Wicrew\DateAvailability\Entity\HistoryLog;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * OrderController
 */
class OrderController extends BaseAdminController {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array {
        return parent::getSubscribedServices() + [
                'wicrew.order.utils' => OrderService::class,
                'wicrew.core.stripe' => Stripe::class
            ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistEntity($entity) {
        parent::persistEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function updateEntity($entity) {
        //$em = $this->getDoctrine()->getManager();
        //$order = $em->getRepository(Order::class)->findOneBy(['id' => $entity->getId()]);
        $postData = $this->request->request->get('order');
        if ($entity instanceof Order && $entity->getStatus() == Order::STATUS_CANCELLED && $postData['prestatus'] != Order::STATUS_CANCELLED) {
            $token = $this->get('security.token_storage')->getToken();
            /* @var User $user */
            $user = $token->getUser();
            $orderUtils = $this->get('wicrew.order.utils');
            $orderHistory = $orderUtils->createOrderHistory_CanceledOrder($entity, $user);
            $entity->addHistory($orderHistory);
        }
        parent::updateEntity($entity);
    }

    /**
     * Save order notes
     *
     * @Route(path = "order/savenotes", name = "save_order_notes")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveOrderNotesAction(Request $request) {
        try {
            $em = $this->getDoctrine()->getManager();
            $order = $em->getRepository(Order::class)->findOneBy(['id' => $request->request->get('oid')]);
            $oldOrderNotes = $order->getNotes();
            $order->setNotes($request->request->get('notes'));

            $em->persist($order);
            $em->flush();
            $newOrderNotes = $order->getNotes();
            if( $oldOrderNotes != $newOrderNotes ){
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ".$request->request->get('oid')." - Order Notes changed from '$oldOrderNotes' to '$newOrderNotes'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }    

            // $utils = $this->container->get('wicrew.core.utils');
            // $this->addFlash('success', $utils->getTranslator()->trans('order.addnotes.success'));

            $status = 'success';
        } catch (Exception $e) {
            $status = 'error';
            //            $html = $e->getMessage();
        }
        $data = ['status' => $status];

        return new JsonResponse($data);
    }

    /**
     * Save order notes
     *
     * @Route(methods = { "GET" }, path = "order/edit_item", name = "order_item_edit")
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function orderItemEditAction(Request $request) {
        $parameters = [];
        /* @var OrderItem $item */
        $item = $this->getEM()->getRepository(OrderItem::class)->find($request->query->get('id'));

        $parameters['orderItemID'] = $item->getId();

        $bookingNumber = 1;
        $parameters['adultCounts'] = [$bookingNumber => $item->getAdultCount()];
        $parameters['childCounts'] = [$bookingNumber => $item->getChildCount()];
        $parameters['pickDates'] = [$bookingNumber => $item->getPickDate()];
        $parameters['pickTime'] = $item->getPickTime();

        $parameters['pickAreas'] = [1 => $item->getPickArea()->getId()];
        $parameters['dropArea'] = $item->getDropArea();
        $parameters['pickGooglePlaceID'] = $item->getPickGooglePlaceID();
        $parameters['dropGooglePlaceID'] = $item->getDropGooglePlaceID();

        $parameters['referrer'] = $this->generateUrl('easyadmin', [
            'id' => $item->getOrder()->getId(),
            'action' => 'edit',
            'entity' => 'Order'
        ]);
       
        if ($item->getAddons()->count() > 0) {
            $addons = [];
            /* @var OrderItemHasAddon $addon */
            
            foreach ($item->getAddons() as $addon) { 
                $addons[$addon->getAddon()->getId()]['addon_adult'] = $addon->getAdultQuantity();
                $addons[$addon->getAddon()->getId()]['addon_child'] = $addon->getChildQuantity();
                $addons[$addon->getAddon()->getId()]['addon_extra_transportation'] = $addon->getExtraTransportationQuantity();
                $qty = $addon->getAdultQuantity() + $addon->getChildQuantity() + $addon->getExtraTransportationQuantity();
                $addons[$addon->getAddon()->getId()]['enabled'] = $qty > 0;
            }
            
            $parameters['addons'] = $addons;
        }

        if ($item->getExtras()->count() > 0) {
            $extras = [];
            /* @var OrderItemHasAddon $addon */
            
            foreach ($item->getExtras() as $extra) {  
                $extras[$extra->getExtra()->getId()]['extra_price'] = $extra->getQuantity();
                $extras[$extra->getExtra()->getId()]['enabled'] = $extra->getQuantity() >  0;
            }
            
            $parameters['extras'] = $extras;
        }
          
        // Parameters that need to be passed to the twig template that's eventually rendered.
        $parametersForRenderTarget = [];
        $parametersForRenderTarget['enabledAddons']['addon'] = isset($parameters['addons']) ? $parameters['addons'] : [];
        $parametersForRenderTarget['enabledAddons']['extra'] = isset($parameters['extras']) ? $parameters['extras'] : [];

        $parametersForRenderTarget['pickArea'] = $item->getPickArea();
        $parametersForRenderTarget['pickAddress'] = $item->getPickAddress();
        $parametersForRenderTarget['pickGooglePlaceID'] = $item->getPickGooglePlaceID();
        $parametersForRenderTarget['pickFlight'] = $item->getPickFlightNumber();
        $parametersForRenderTarget['pickAirline'] = $item->getPickAirlineCompany();

        $parametersForRenderTarget['dropArea'] = $item->getDropArea();
        $parametersForRenderTarget['dropAddress'] = $item->getDropAddress();
        $parametersForRenderTarget['dropGooglePlaceID'] = $item->getDropGooglePlaceID();
        $parametersForRenderTarget['dropFlight'] = $item->getDropFlightNumber();
        $parametersForRenderTarget['dropAirline'] = $item->getDropAirlineCompany();

        if ($item->getType() === OrderItem::TYPE_PRIVATE_FLIGHT) {
            $parametersForRenderTarget['luggagesWeight'] = [$bookingNumber => $item->getLuggageWeight()];
            $parametersForRenderTarget['passengersWeight'] = [$bookingNumber => $item->getPassengerWeight()];
        }

        if ($item->getProduct() !== null) {
            $product = $item->getProduct();
            $parameters['productIDs'] = [$product->getId()];
            if ($product->getTransportationType()->isJeepBoatJeepType()) {
                $parameters['jbjType'] = $product->getTransportationType()->getId();
            }
        } else if ($item->getActivity() != null) {
            $parameters['activityIDs'] = [$item->getActivity()->getId()];
            $parametersForRenderTarget['selectedTourTime'] = $item->getTourTime();
            $activityType = $item->getActivityType(); 
            if ( strpos( strtolower( $activityType ), 'group' ) !== false ) {
                $activityType = 1;
            }else{
                $activityType = 2;
            }
            $parameters['dl_activityType'] = $activityType;
        }
        $parameters['pickAddress']      = $item->getPickAddress();
        $parameters['dropAddress']      = $item->getDropAddress();
        $parameters['dl_actionType']    = "edit_item";
        $parameters['custom_services']    = $item->getCustomServices();
        $parameters['renderTargetParameters'] = $parametersForRenderTarget;
        return $this->forward('WicrewSaleBundle:Booking:booking', $parameters);
    }

    /**
     * Delete order item
     *
     * @Route(path = "order/item/delete", name = "order_item_delete")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function orderItemDeleteAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $utils = $this->container->get('wicrew.core.utils');

        $itemId = $request->query->get('id');
        /* @var OrderItem $orderItem */
        $orderItem = $em->getRepository(OrderItem::class)->findOneBy(['id' => $itemId]);

        try {
            $em->beginTransaction();

            $token = $this->get('security.token_storage')->getToken();
            /* @var User $user */
            $user = $token->getUser();

            $orderUtils = $this->get('wicrew.order.utils');
            $orderHistory = $orderUtils->createOrderHistory_CanceledItem($orderItem, $user);

            $orderItem->setStatus(OrderItem::STATUS_CANCELLED);
            $em->persist($orderItem);

            $order = $orderItem->getOrder();
            $order->addHistory($orderHistory);

            $em->persist($order);
            $em->flush();
            $em->commit();

            $this->addFlash('success', $utils->getTranslator()->trans('order.items.delete.success'));
        } catch (Exception $e) {
            $this->logError($e);
            $this->addFlash('error', $e->getMessage());
            $em->rollback();
        }

        return $this->redirectToRoute('easyadmin', [
            'id' => $orderItem->getOrder()->getId(),
            'action' => 'edit',
            'entity' => 'Order'
        ]);
    }

    /**
     * Charge Stripe payment
     *
     * @Route(path = "order/payment/stripe", name = "order_payment_transaction")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function transactionStripeAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        //        $html = '';

        try {
            $em->beginTransaction();

            $utils = $this->container->get('wicrew.core.utils');
            $translator = $utils->getTranslator();
            $orderUtils = $this->container->get('wicrew.order.utils');
            $stripeUtils = $this->container->get('wicrew.core.stripe');

            $paymentType = $request->request->get('paymentType');
            
            $token = $this->get('security.token_storage')->getToken();
            /* @var User $user */
            $user = $token->getUser();
            $order = $em->getRepository(Order::class)->findOneBy(['id' => $request->request->get('orderID')]);

            $makeNewCard = $request->request->get('makeNewCard', null);
            $chargeAsCash = $request->request->get('chargeAsCash', null);
            $orderItemID = $request->request->get('orderItemID', null);
            $orderItem = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('orderItemID')]);
            $note = $request->request->get('note');
            
            if($paymentType === "charge_cash"){
                
                $note           = $note ? $note : 'Cash/wire payment';
                $amount         = new Money($request->request->get('amount'));
                $chargeID       = "cash";
                $resultDesc     = null;
                $status         = "success";
                $msg            = "Payment charged as cash/wire!";
                $orderHistory   = $orderUtils->createOrderHistory_CashCharge($order, $user, $amount, $note);
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ".$request->request->get('orderID')." - $$amount USD Charged as cash!" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG

                $order->addHistory($orderHistory);

                /* @var Money $totalDue */
                $totalDue = $order->getOrderHistoryTotal()['totalDue'];
                if ($totalDue->lessThanOrEqualStr('0') && $order->getStatus() != 2 ) {
                    $order->setStatus(Order::STATUS_PAID);
                    foreach ($order->getItems() as $order_item) {
                        if ( $order_item->getStatus() != 2 ) {
                            $order_item->setStatus( 1 );
                            $em->persist($order_item); 
                        }
                    }   
                }

                $em->persist($order); 
                $em->flush();
                $em->commit();

                $html = $utils->getContainer()->get('templating')->render('WicrewSaleBundle:Admin:Form/order.history.detail.html.twig', [
                    'order' => $order
                ]);
                
            }else{
                $note           = $note ? $note : $translator->trans('sale.checkout.order.card.payment');

                if ($makeNewCard !== null && $paymentType === 'charge') {
                    $stripeToken = $request->request->get('stripeToken');
                    if (!$order->getStripeCustomerId()) {
                        $stripeCustomer = $stripeUtils->createCustomer($stripeToken, $order->getEmail());
                        $order->setStripeCustomerId($stripeCustomer['data']->id);
                    } else {
                        $stripeUtils->createSource($order->getStripeCustomerId(), $stripeToken, true);
                    }
                }
    
                $amount = new Money($request->request->get('amount'));
    
                if ($paymentType === 'charge') {
                    $result = $orderUtils->performCharge($order, $amount);
                } else if ($paymentType === 'refund') {
                    $result = $orderUtils->performRefund($order, $amount);
                } else {
                    throw new Exception("Unknown payment type.");
                }
    
                if (!isset($result['data'])) {
                    throw new Exception($result['message']);
                }
                $status = $result['status'];
                $msg = $result['message'];
                $resultDesc = isset($result['data']->getLastResponse()->json['description']) ? $result['data']->getLastResponse()->json['description'] : null;
    
                if ($result['status'] == 'success') {
                    if ($paymentType == 'charge') {
                        $chargeID = $result['data']->getLastResponse()->json['id'];
                        $orderHistory = $orderUtils->createOrderHistory_StripeCharge($order, $user, $chargeID, $resultDesc, $status, $amount, $note);
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $historyLog      = new HistoryLog();
                        $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications("#RJ".$request->request->get('orderID')." - $$amount USD Charged!" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    } else if ($paymentType == 'refund') {
                        $amountThatCanBeRefunded = Money::fromCents($result['data']->getLastResponse()->json['amount']);
                        if ($amountThatCanBeRefunded->lessThan($amount)) {
                            throw new Exception('Can\'t refund more than $' . $amountThatCanBeRefunded);
                        }
    
                        $refundID = $result['data']->getLastResponse()->json['id'];
                        $orderHistory = $orderUtils->createOrderHistory_StripeRefund($order, $user, $refundID, $resultDesc, $status, $amount, $note, $orderItem);
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $historyLog      = new HistoryLog();
                        $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications("#RJ".$request->request->get('orderID')." - $$amount USD Refunded!" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                        if ($orderItem) {
                            $orderItem->setStatus(OrderItem::STATUS_REFUNDED);
                            $em->persist($orderItem);
                        }
                    }
    
                    $order->addHistory($orderHistory);
    
                    /* @var Money $totalDue */
                    $totalDue = $order->getOrderHistoryTotal()['totalDue'];
                    if ($totalDue->lessThanOrEqualStr('0') && $order->getStatus() != 2 ) {
                        $order->setStatus(Order::STATUS_PAID);
                        foreach ($order->getItems() as $order_item) {
                            if ( $order_item->getStatus() != 2 ) {
                                $order_item->setStatus( 1 );
                                $em->persist($order_item); 
                            }
                        }   
                    }
    
                    $em->persist($order); 
                    $em->flush();
                    $em->commit();
    
                    $html = $utils->getContainer()->get('templating')->render('WicrewSaleBundle:Admin:Form/order.history.detail.html.twig', [
                        'order' => $order
                    ]);
                } else {
                    throw new Exception($msg);
                }
            }
        } catch (Throwable $e) {
            $this->logError($e);
            $em->rollback();
            return new JsonResponse(['status' => 'failed', 'msg' => $e->getMessage()]);
        }

        return new JsonResponse(['status' => $status, 'msg' => $msg, 'html' => $html]);
    }

    /**
     * Charge Stripe payment
     *
     * @Route(path = "order/payment/mail", name = "order_payment_mail")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function orderPaymentMailAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
 
        try { 
            $em->beginTransaction();
            
            $orderId = $request->request->get('orderID');
            $site_url = $request->request->get('siteURL');
            $order = $em->getRepository(Order::class)->find($orderId);

            $amountDueInfo = $order->getOrderHistoryTotal();
            $amountDue = (string) $amountDueInfo['totalDue'];
 
            if ($amountDue > 0) {
                $utils = $this->container->get('wicrew.core.utils');
                $translator = $utils->getTranslator();
                $orderUtils = $this->container->get('wicrew.order.utils'); 
                $mailer = $this->container->get('wicrew.core.mailer');

                $customerName = $order->getFirstname() . ' ' . $order->getLastname();
                $orderNumber = $orderId;
                $orderDetails   = '';
                foreach ($order->getSortedItems() as $item) {
                    if( $item->getStatus() == 0 ){
                        $orderDetails .= $this->renderTwigToString('WicrewSaleBundle:Admin:order.item.info.email.html.twig', ['item' => $item, 'email' => true]);
                    }
                }
                if( $orderDetails == '' ){
                    foreach ($order->getSortedItems() as $item) {
                        $orderDetails .= $this->renderTwigToString('WicrewSaleBundle:Admin:order.item.info.email.html.twig', ['item' => $item, 'email' => true]);
                    }
                }

                $utils = $this->get('wicrew.core.utils');
                $q = json_encode(['oid' => $order->getId()]);
                $data = $utils->encrypt_decrypt($q, 'encrypt');
                
                $link = $site_url . $this->generateUrl('order_mailpayment_transaction') . '?q=' . $data;
                
                $msg = 'Mail of payment sent to customer';
                $from = $utils->getContainer()->getParameter('system_email');
                $to = $order->getEmail();
                $replyTo = $utils->getContainer()->getParameter('system_email');
                $subject = $translator->trans('order.mail.payment.subject');
                $body = $this->renderTwigToString('WicrewSaleBundle:Admin:Email/order.mail.payment.html.twig', [
                    'orderNumber'   => $orderNumber, 
                    'orderDetails'  => $orderDetails,
                    'customerName'  => $customerName,
                    'link'          => $link
                ]);

                $mailer->send([
                    'from' => $from,
                    'to' => $to, 
                    'subject' => $subject,
                    'body' => $body
                ]);
                // LOGGING INTO HISTORYLOG
                global $kernel;
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$orderNumber - Sent mail for payment to customer!" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            } else {
                $msg = 'Amount due not greater than 0';
            }
        } catch (Throwable $e) {
            $this->logError($e);dump($e);die;
            $em->rollback();
            return new JsonResponse(['status' => 'failed', 'msg' => $e->getMessage()]);
        }
        
        return new JsonResponse(['status' => 'success', 'msg' => $msg]);
    }



    /**
     * Charge Stripe payment
     *
     * @Route(path = "create/mail_payment/link", name = "create_payment_link")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createMailPaymentLinkAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
 
        try { 
            $em->beginTransaction();
            
            $orderId = $request->request->get('orderID');
            $site_url = $request->request->get('siteURL');
            
            $utils = $this->get('wicrew.core.utils');
            $q = json_encode([ 'oid' => $orderId ]);
            $data = $utils->encrypt_decrypt($q, 'encrypt');
            
            $link = $site_url . $this->generateUrl('order_mailpayment_transaction') . '?q=' . $data;
            
            return new JsonResponse(['status' => 'success', 'link' => $link]);
        
        } catch (Throwable $e) {
            $em->rollback();
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }
        
    }

    /**
     * Send email
     *
     * @return JsonResponse
     */
    public function sendMailAction() {
        $response = [
            'status' => 'failed',
            'message' => ''
        ];

        $translator = $this->container->get('translator');
        $utils = $this->container->get('wicrew.core.utils');
        $orderId = $this->request->request->get('order', null);
        $template = $this->request->request->get('template', null);
        if ($orderId && $template && in_array($template, ['edit', 'charge', 'refund', 'canceled', 'quote'])) {
           // LOGGING INTO HISTORYLOG
           global $kernel;
           $historyLog      = new HistoryLog();
           $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
           $historyLog->setCreatedAt( $currentDateTime );
           $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
           $historyLog->setModifications("#RJ$orderId - Order $template e-mail sent to the customer!" );
           $this->em->persist($historyLog);
           $this->em->flush();
           // LOGGING INTO HISTORYLOG
            try {
                $customBody = $this->request->request->get('customBody', null);

                $order = $this->em->getRepository(Order::class)->find($orderId);
                if ($order) {
                    $customerName = $order->getFirstname() . ' ' . $order->getLastname();
                    $orderNumber = $orderId;
                    $orderDetails = '';
                    foreach ($order->getSortedItems() as $item) {
                        $orderDetails .= $this->renderTwigToString('WicrewSaleBundle:Admin:order.item.info.email.html.twig', ['item' => $item, 'email' => true]);
                    }

                    $from = $utils->getContainer()->getParameter('system_email');
                    $to = $order->getEmail();
                    $replyTo = $utils->getContainer()->getParameter('system_email');

                    if ($template == 'edit') {
                        $subject = $translator->trans('order.mail.edit.subject');
                        $body = $this->renderTwigToString('WicrewSaleBundle:Admin:Email/order.mail.edit.html.twig', [
                            'order'         => $order,
                            'customerName'  => $customerName,
                            'orderNumber'   => $orderNumber,
                            'orderDetails'  => $orderDetails,
                            'customBody'    => $customBody
                        ]);
                    } else if ($template == 'charge') {
                        $subject = $translator->trans('order.mail.charge.subject');
                        $body = $this->renderTwigToString('WicrewSaleBundle:Admin:Email/order.mail.charge.html.twig', [
                            'order'         => $order,
                            'customerName'  => $customerName,
                            'orderNumber'   => $orderNumber,
                            'orderDetails'  => $orderDetails,
                            'customBody'    => $customBody
                        ]);
                    } else if ($template == 'refund') {
                        $subject = $translator->trans('order.mail.refund.subject');
                        $body = $this->renderTwigToString('WicrewSaleBundle:Admin:Email/order.mail.refund.html.twig', [
                            'order'         => $order,
                            'customerName'  => $customerName,
                            'orderNumber'   => $orderNumber,
                            'orderDetails'  => $orderDetails,
                            'customBody'    => $customBody
                        ]);
                    } else if ($template == 'canceled') {
                        $subject = $translator->trans('order.mail.canceled.subject');
                        $body = $this->renderTwigToString('WicrewSaleBundle:Admin:Email/order.mail.canceled.html.twig', [
                            'order'         => $order,
                            'customerName'  => $customerName,
                            'orderNumber'   => $orderNumber,
                            'orderDetails'  => $orderDetails,
                            'customBody'    => $customBody
                        ]);
                    } else if ($template == 'quote') {
                        $subject = 'ILT Costa Rica - Quote confirmation';

                        $site_url       = $this->container->get('router')->getContext()->getBaseUrl();
                        $utils          = $this->get('wicrew.core.utils');
                        $q              = json_encode(['oid' => $order->getId()]);
                        $data           = $utils->encrypt_decrypt($q, 'encrypt');
                        $payment_link   = $site_url . $this->generateUrl('order_mailpayment_transaction') . '?q=' . $data;
                        
                        $body = $this->renderTwigToString('WicrewSaleBundle:Admin:Email/order.mail.quote.html.twig', [
                            'order'         => $order,
                            'customerName'  => $customerName,
                            'orderNumber'   => $orderNumber,
                            'orderDetails'  => $orderDetails,
                            'customBody'    => $customBody,
                            'payment_link'  => $payment_link,
                        ]);
                    }

                    if (isset($subject) && isset($body)) {
                        $mailer = $this->container->get('wicrew.core.mailer');
                        $result = $mailer->send([
                            'from'      => $from,
                            'to'        => trim($to),
                            'replyTo'   => $replyTo,
                            'subject'   => $subject,
                            'body'      => $body
                        ]);

                        if ($result) {
                            $response['status'] = 'success';
                        } else {
                            throw new Exception('Unable to send the email');
                        }
                    } else {
                        throw new Exception('Unable to send the email');
                    }
                } else {
                    $response['status'] = 'failed';
                    $response['message'] = $translator->trans('Order not found');
                }
            } catch (Exception $e) {
                $response['status'] = 'failed';
                $response['message'] = $e->getMessage();
            }
        } else {
            $response['status'] = 'failed';
            $response['message'] = $translator->trans('Invalid request');
        }

        return new JsonResponse($response);
    } 


    /**
     * Creates Query Builder instance for search query.
     *
     * @param string      $entityClass
     * @param string      $searchQuery
     * @param array       $searchableFields
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param string|null $dqlFilter
     *
     * @return QueryBuilder The Query Builder instance
     */
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = NULL, $sortDirection = NULL, $dqlFilter = NULL)
    {   
        if (strpos(strtolower($searchQuery), '#rj') !== false) {
            $searchQuery = str_ireplace("#RJ", "", $searchQuery);
            // unset($this->entity['search']['fields']["id"]);
            unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            unset($this->entity['search']['fields']["dlOrder.quote"]);
        }

        if (strpos(strtolower($searchQuery), 'rj') !== false) {
            $searchQuery = str_ireplace("RJ", "", $searchQuery);
            // unset($this->entity['search']['fields']["id"]);
            unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            unset($this->entity['search']['fields']["dlOrder.quote"]);
        }

        if (strpos(strtolower($searchQuery), 'pending') !== false) {
            $searchQuery = str_ireplace("pending", "0", $searchQuery);
            unset($this->entity['search']['fields']["id"]);
            // unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            unset($this->entity['search']['fields']["dlOrder.quote"]);
        }

        if (strpos(strtolower($searchQuery), 'paid cash') !== false) {
            $searchQuery = str_ireplace("paid cash", "1", $searchQuery);
            unset($this->entity['search']['fields']["id"]);
            // unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            unset($this->entity['search']['fields']["dlOrder.quote"]);
        }

        if (strpos(strtolower($searchQuery), 'paid') !== false) {
            $searchQuery = str_ireplace("paid", "1", $searchQuery);
            unset($this->entity['search']['fields']["id"]);
            // unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            unset($this->entity['search']['fields']["dlOrder.quote"]);
        }
      
        if (strpos(strtolower($searchQuery), 'cancelled') !== false) {
            $searchQuery = str_ireplace("cancelled", "2", $searchQuery);
            unset($this->entity['search']['fields']["id"]);
            // unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            unset($this->entity['search']['fields']["dlOrder.quote"]);
        }

        if (strtotime($searchQuery) ){
            $searchQuery = date( 'Y-m-d', strtotime($searchQuery) );
            unset($this->entity['search']['fields']["id"]);
            unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            // unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            unset($this->entity['search']['fields']["dlOrder.quote"]);
        }


        if (strpos(strtolower($searchQuery), 'no') !== false) {
            $searchQuery = str_ireplace("no", "0", $searchQuery);
            unset($this->entity['search']['fields']["id"]);
            unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            // unset($this->entity['search']['fields']["dlOrder.quote"]);
        }

        if (strpos(strtolower($searchQuery), 'yes') !== false) {
            $searchQuery = str_ireplace("yes", "1", $searchQuery);
            unset($this->entity['search']['fields']["id"]);
            unset($this->entity['search']['fields']["status"]);
            unset($this->entity['search']['fields']["supplier.bizName"]);
            unset($this->entity['search']['fields']["dlOrder.firstName"]);
            unset($this->entity['search']['fields']["dlOrder.lastName"]);
            unset($this->entity['search']['fields']["dlOrder.createdAt"]);
            unset($this->entity['search']['fields']["dlOrder.items.grandTotal"]);
            // unset($this->entity['search']['fields']["dlOrder.quote"]);
        }

        return $this->get('easyadmin.query_builder')->createSearchQueryBuilder($this->entity, $searchQuery, $sortField, $sortDirection, $dqlFilter, $searchableFields);
    }

    public function editAction() {
        
        $em = $this->getDoctrine()->getManager();
        
        $id = $this->request->query->get('id');
        $dl_amountDue = $this->request->get('dl_amountDue');
        $oldOrder = $em->getRepository(Order::class)->findOneBy( [ 'id' => $id ] );
        global $kernel;        
        
        $this->dispatch(EasyAdminEvents::PRE_EDIT);
        $easyadmin = $this->request->attributes->get('easyadmin');
        $entity = $easyadmin['item'];
        
        $oldOrderStatus         = $oldOrder->getStatus();
        // CLIENT INFORMATION TAB 
        $oldOrderQuote          = $oldOrder->getQuote();
        $oldOrderSupplier       = $oldOrder->getSupplier();
        $oldOrderPaymentType    = $oldOrder->getPaymentType();
        $oldOrderFirstname      = $oldOrder->getFirstname();
        $oldOrderLastname       = $oldOrder->getLastname();
        $oldOrderEmail          = $oldOrder->getEmail();
        $oldOrderCountry        = $oldOrder->getCountry();
        $oldOrderTel            = $oldOrder->getTel();
        $oldOrderWhatsapp       = $oldOrder->getWhatsapp();
        // CLIENT INFORMATION TAB 

        if ($this->request->isXmlHttpRequest() && $property = $this->request->query->get('property')) {
            $newValue = 'true' === mb_strtolower($this->request->query->get('newValue'));
            $fieldsMetadata = $this->entity['list']['fields'];

            if (!isset($fieldsMetadata[$property]) || 'toggle' !== $fieldsMetadata[$property]['dataType']) {
                throw new RuntimeException(sprintf('The type of the "%s" property is not "toggle".', $property));
            }
            
            $this->updateEntityProperty($entity, $property, $newValue);
            
            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int)$newValue);
        }

        $fields = $this->entity['edit']['fields'];
        
        /* @var Form $editForm */
        $editForm = $this->executeDynamicMethod('create<EntityName>EditForm', [$entity, $fields]);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $editForm->handleRequest($this->request);
        if ($editForm->isSubmitted() ) {
            $flashMessage = '';
            $warning = false;
            
            $canSubmit = $this->onValidNewOrEditSubmit($entity, $flashMessage, $warning);
            if ($flashMessage !== '') {
                $this->addFlash($warning ? 'warning' : 'error', $flashMessage);
            }

            $newOrderStatus = $entity->getStatus();
            // CLIENT INFORMATION TAB 
            $newOrderQuote          = $entity->getQuote();
            $newOrderSupplier       = $entity->getSupplier();
            $newOrderPaymentType    = $entity->getPaymentType();
            $newOrderFirstname      = $entity->getFirstname();
            $newOrderLastname       = $entity->getLastname();
            $newOrderEmail          = $entity->getEmail();
            $newOrderCountry        = $entity->getCountry();
            $newOrderTel            = $entity->getTel();
            $newOrderWhatsapp       = $entity->getWhatsapp();
            // CLIENT INFORMATION TAB 
            
            if( $oldOrderQuote != $newOrderQuote ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                
                $_tempValue = "";
                if( $newOrderQuote == 0 ){
                    $_tempValue = "Un-checked";
                }else if( $newOrderQuote == 1 ){
                    $_tempValue = "Checked";
                }

                $historyLog->setModifications("#RJ$id - Checkout as Quote $_tempValue" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }

            if( $oldOrderSupplier != $newOrderSupplier ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $oldSupplierName = "";
                $newSupplierName = "";
                if( $oldOrderSupplier != null ){
                    $oldSupplierName = $oldOrderSupplier->getBizName();
                }
                if( $newOrderSupplier != null ){
                    $newSupplierName = $newOrderSupplier->getBizName();
                }
                if($oldSupplierName == ""){
                    $oldSupplierName = "Null";
                }
                
                $historyLog->setModifications("#RJ$id - Order's supplier changed from '$oldSupplierName' to '$newSupplierName'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }


            if( $oldOrderPaymentType != $newOrderPaymentType ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $oldOrderPaymentTypeName = "";
                $newOrderPaymentTypeName = "";
                if( $oldOrderPaymentType == 1 ){
                    $oldOrderPaymentTypeName = "Credit Card";
                }else if( $newOrderSupplier == 2 ){
                    $newOrderPaymentTypeName = "Cash";
                }
                $historyLog->setModifications("#RJ$id - Order's payment type changed from '$oldOrderPaymentTypeName' to '$newOrderPaymentTypeName'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }

            if( $oldOrderFirstname != $newOrderFirstname ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$id - Customer's first name changed from '$oldOrderFirstname' to '$newOrderFirstname'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }

            if( $oldOrderLastname != $newOrderLastname ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$id - Customer's last name changed from '$oldOrderLastname' to '$newOrderLastname'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }


            if( $oldOrderEmail != $newOrderEmail ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$id - Customer's E-mail changed from '$oldOrderEmail' to '$newOrderEmail'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }
           
            if( $oldOrderCountry != $newOrderCountry ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$id - Customer's country changed from '$oldOrderCountry' to '$newOrderCountry'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }

            if( $oldOrderTel != $newOrderTel ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$id - Customer's telephone number changed from '$oldOrderTel' to '$newOrderTel'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }

            if( $oldOrderWhatsapp != $newOrderWhatsapp ){
                // LOGGING INTO HISTORYLOG
                $historyLog      = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$id - Customer's WhatsApp number changed from '$oldOrderWhatsapp' to '$newOrderWhatsapp'" );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }

            $allOrderItems  = $this->em->getRepository(OrderItem::class)->findBy( [ 'orderId' => $id ] );
            if( $newOrderStatus != 2 ){
                // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
                // echo "hello; ". $entity->getOrderHistoryTotal()['totalDue']; exit;
                if( $dl_amountDue <= 0 and !$entity->getQuote() ) {
                    foreach ($allOrderItems as $item) {
                        if( $item->getStatus() != 2 ){ // CHECK IF ITEM STATUS IS CANCELLED DO NOTHING 
                            $item->setStatus(1); // PAID
                            $em->persist($item);
                            $em->flush();
                        }              
                    }
                }
                
            }else{

                foreach ($allOrderItems as $item) {
                    $item->setStatus(2); // CALCENED
                    $em->persist($item);
                    $em->flush();    
                }
            }
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
        
            if( $oldOrderStatus != $newOrderStatus ){

                if( $oldOrderStatus == 0 ){
                    $oldOrderStatus = "PENDING";
                }else if( $oldOrderStatus == 1 ){
                    $oldOrderStatus = "PAID";
                }else if( $oldOrderStatus == 2 ){
                    $oldOrderStatus = "CANCELLED";
                } 

                if( $newOrderStatus == 0 ){
                    $newOrderStatus = "PENDING";
                }else if( $newOrderStatus == 1 ){
                    $newOrderStatus = "PAID";
                }else if( $newOrderStatus == 2 ){
                    $newOrderStatus = "CANCELLED";
                } 
                // LOGGING INTO HISTORYLOG
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("#RJ$id - Order's billing status changed from '$oldOrderStatus' to '$newOrderStatus'." );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
                
            }

            if ($canSubmit) {
                $this->processUploadedFiles($editForm);

                $this->dispatch(EasyAdminEvents::PRE_UPDATE, ['entity' => $entity]);
                $this->executeDynamicMethod('update<EntityName>Entity', [$entity, $editForm]);
                $this->dispatch(EasyAdminEvents::POST_UPDATE, ['entity' => $entity]);

                return $this->redirectToReferrer();
            }
        }

        $this->dispatch(EasyAdminEvents::POST_EDIT);

        $parameters = [
            'form' => $editForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['edit', $this->entity['templates']['edit'], $parameters]);
    }

    public function deleteAction(): RedirectResponse {
        $this->dispatch(EasyAdminEvents::PRE_DELETE);
        $em = $this->getDoctrine()->getManager();
        if ('DELETE' !== $this->request->getMethod()) {
            return $this->redirect($this->generateUrl('easyadmin', ['action' => 'list', 'entity' => $this->entity['name']]));
        }
        
        $id = $this->request->query->get('id');
        $form = $this->createDeleteForm($this->entity['name'], $id);
        $form->handleRequest($this->request);
        if ($form->isSubmitted() && $form->isValid()) {
            $easyadmin = $this->request->attributes->get('easyadmin');
            $entity = $easyadmin['item'];
            
            // LOGGING INTO HISTORYLOG
            global $kernel;
            $historyLog         = new HistoryLog();
            $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
            $historyLog->setModifications("#RJ$id - Order has been archived!" );
            $em->persist($historyLog);
            // LOGGING INTO HISTORYLOG
            
            $flashMessage = '';
            if ($this->onValidDeleteSubmit($entity, $flashMessage)) {
                
                $this->dispatch(EasyAdminEvents::PRE_REMOVE, ['entity' => $entity]);
                
                $this->executeDynamicMethod('remove<EntityName>Entity', [$entity, $form]);
                $this->dispatch(EasyAdminEvents::POST_REMOVE, ['entity' => $entity]);
                $flashMessage = "#RJ$id - Order has been archived!";

            }

            if ($flashMessage !== '') {
                $this->addFlash('error', $flashMessage);
            }
        }

        $this->dispatch(EasyAdminEvents::POST_DELETE);

        return $this->redirectToReferrer();
    }

    public function archiveAction(): RedirectResponse {
        $this->dispatch(EasyAdminEvents::PRE_DELETE);
        $em = $this->getDoctrine()->getManager();
        $id = $this->request->query->get('id');
        // LOGGING INTO HISTORYLOG
        global $kernel;
        $historyLog         = new HistoryLog();
        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
        $historyLog->setCreatedAt( $currentDateTime );
        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
        $historyLog->setModifications("#RJ$id - Order has been archived!" );
        $em->persist($historyLog);
        // LOGGING INTO HISTORYLOG
        
        $flashMessage = '';
            
        // echo $this->request->getMethod(); exit;
        // $this->executeDynamicMethod('remove<EntityName>Entity', [$entity, $form]);
        $order = $em->getRepository(Order::class)->findOneBy([ 'id' => $id ]);
        $order->setArchiveStatus(1); 
        foreach ($order->getItems() as $key => $order_item) {
            $order_item->setArchiveStatus(1);
        }
        $em->persist($order);
        $em->flush();
        $flashMessage = "#RJ$id - Order has been archived!";

        

        if ($flashMessage !== '') {
            $this->addFlash('error', $flashMessage);
        }
    
        return $this->redirectToReferrer();
    }

    public function unarchiveAction(): RedirectResponse {
        $this->dispatch(EasyAdminEvents::PRE_DELETE);
        $em = $this->getDoctrine()->getManager();
    
        
        $id = $this->request->query->get('id');
    
        
        // LOGGING INTO HISTORYLOG
        global $kernel;
        $historyLog         = new HistoryLog();
        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
        $historyLog->setCreatedAt( $currentDateTime );
        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
        $historyLog->setModifications("#RJ$id - Order has been unarchived!" );
        $em->persist($historyLog);
        // LOGGING INTO HISTORYLOG
        
        $flashMessage = '';
            
        // echo $this->request->getMethod(); exit;
        // $this->executeDynamicMethod('remove<EntityName>Entity', [$entity, $form]);
        $order = $em->getRepository(Order::class)->findOneBy([ 'id' => $id ]);
        $order->setArchiveStatus(0); 
        foreach ($order->getItems() as $key => $order_item) {
            $order_item->setArchiveStatus(0);
        }
        $em->persist($order);
        $em->flush();
        $flashMessage = "#RJ$id - Order has been unarchived!";

        

        if ($flashMessage !== '') {
            $this->addFlash('success', $flashMessage);
        }
    
        return $this->redirectToReferrer();
    }

    /**
     * DELETE ORDER PAYMENT HISTORY ROW
     *
     * @Route(path = "order/payment_history/delete", name = "order_payment_history_delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteOrderPaymentHistory(Request $request) {
        try {
            $em                 = $this->getDoctrine()->getManager();
            $orderHistoryRecord = $em->getRepository(OrderHistory::class)->findOneBy(['id' => $request->request->get('id')]);
            $order_id           = $orderHistoryRecord->getOrder()->getId();
            $record_amount      = $orderHistoryRecord->getAmount();
            
            $em->remove($orderHistoryRecord);
            $em->flush();
            // LOGGING INTO HISTORYLOG
            global $kernel;
            $historyLog      = new HistoryLog();
            $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
            $historyLog->setModifications("#RJ$order_id - Record worth amount '$$record_amount' deleted from order payment history." );
            $em->persist($historyLog);
            $em->flush();
            // LOGGING INTO HISTORYLOG

            $status = 'deleted';
        } catch (Exception $e) {
            $status = 'error';
        }
        $data = ['status' => $status];
        return new JsonResponse($data);
    }

}
