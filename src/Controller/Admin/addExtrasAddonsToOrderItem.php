<?php


namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasAddon;
use App\Wicrew\SaleBundle\Entity\OrderItemHasExtra;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use Symfony\Component\HttpFoundation\Request;
use DateTime;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use Throwable;
use App\Wicrew\DateAvailability\Entity\HistoryLog;

    
/**
 * Add Extras/Addons To Order Item
 */
class addExtrasAddonsToOrderItem extends BaseAdminController
{
    /**
     * @Route("admin/add/addon", name="add_addon_to_orderitem")
     * 
     * @param Request $request
     */
    public function addAddon(Request $request): JsonResponse
    {
        try {
            $em     = $this->getEM();
            $em->beginTransaction();
            
            $addons = $em->getRepository(Addon::Class)->findBy( array("id" => $request->request->get('addon_id')), array('sortOrder' => 'ASC') );
            $addon  = $addons[0];
            $adultCount = $request->request->get('adultCount');
            $childCount = $request->request->get('childCount');
            $item = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('item_id')]);
            $oldItem    = clone $item; 

            // ADDING ADDON TO ORDER ITEM
            $itemAddon = new OrderItemHasAddon();
            
            $itemAddon->setAddon($addon);
            $itemAddon->setPriceType($addon->getPriceType());          
            
            $itemAddon->setLabel($addon->getLabel()."<br> <small>(<em>Adults x $adultCount: $".$addon->getAdultRackPrice() * $adultCount." USD</em>, <em>Children x $childCount: $".$addon->getChildRackPrice() * $childCount." USD</em>, <em>Extra Transportation: $".$addon->getExtraTransportation()." USD</em>)</small> ");
            $itemAddon->setAddonTitle($addon->getLabel());
            $itemAddon->setAdultQuantity( $adultCount );
            $itemAddon->setChildQuantity( $childCount );
            $itemAddon->setAdultRackPrice(($addon->getAdultRackPrice()* $adultCount));
            $itemAddon->setAdultNetPrice(($addon->getAdultNetPrice() * $adultCount));
            $itemAddon->setChildRackPrice(($addon->getChildRackPrice() * $childCount));
            $itemAddon->setChildNetPrice(($addon->getChildNetPrice() * $childCount));
            $itemAddon->setExtraTransportation($addon->getExtraTransportation());
            
            
            $addon_total_rack_price   = ( $adultCount * $addon->getAdultRackPrice() ) + ( $childCount * $addon->getChildRackPrice() ) + $addon->getExtraTransportation();
            $addon_total_net_price    = ( $adultCount * $addon->getAdultNetPrice() ) + ( $childCount * $addon->getChildNetPrice() );
            
            $addons_tax = $em->getRepository(TaxConfig::class)->findBy([
                'label' => "addons"
            ]); 
            if ( count($addons_tax) > 0 ){
                $addons_tax = $addons_tax[0];
            }
            $month          = $item->getPickDate()->format('n');
            $now            = new DateTime();
            $month          = $item->getPickDate()->format('n');
            $year           = $item->getPickDate()->format('Y');
            $now            = new DateTime();
            $current_year   = $now->format("Y");

            if ( $year == $current_year ){
                if( $month <= 6 ){
                    $addon_total_tax_price    = ( $addons_tax->getJanMayRate() / 100 ) * $addon_total_rack_price; 
                }else{
                    $addon_total_tax_price    = ( $addons_tax->getJunDecRate() / 100 ) * $addon_total_rack_price; 
                }
            }else if ( $year < $current_year ){
                $addon_total_tax_price    = ( $addons_tax->getJanMayRate() / 100 ) * $addon_total_rack_price; 
            }else if ( $year > $current_year ){
                $addon_total_tax_price    = ( $addons_tax->getJunDecRate() / 100 ) * $addon_total_rack_price; 
            }

            $itemAddon->setTax( $addon_total_tax_price );

            $itemAddon->setRackPrice( $addon_total_rack_price );
            $itemAddon->setNetPrice( $addon_total_net_price );

            $item->addAddon($itemAddon);

            // ADDON ADDED TO ORDER ITEM. NOW CHANGE PRICES FOR ORDER ITEM
            $item->setTitleRackPrice( $item->getTitleRackPrice() + $addon_total_rack_price );
            $item->setTitleNetPrice( $item->getTitleNetPrice() + $addon_total_net_price );
            $item->setTotalTax( $item->getTotalTax() + $addon_total_tax_price );
            $item->setSubtotalRack( $item->getSubtotalRack() + $addon_total_rack_price );
            $item->setSubtotalNet( $item->getSubtotalNet() + $addon_total_net_price );
            
            $item->setGrandTotal( $item->getGrandTotal() + $addon_total_tax_price +  $addon_total_rack_price);
           
            $item->setAdultRackPrice( $addon->getAdultRackPrice() );
            $item->setChildRackPrice( $addon->getChildRackPrice() );
            $item->setAdultNetPrice( $addon->getAdultNetPrice() );
            $item->setChildNetPrice( $addon->getChildNetPrice() );
            

            $item->setStatus(0); // MAKING ORDERITEM STATUS TO PENDING. 

            $em->persist($item);

            // ADDING TO PAYMENT HISTORY
            global $kernel;
            $token          = $kernel->getContainer()->get('security.token_storage')->getToken();
            $orderUtils     = $kernel->getContainer()->get('wicrew.order.utils');
            $newItem        = $item;
            /* @var User $user */
            $user           = $token->getUser();
            $custom_note    = $addon->getLabel()." added to order item!";
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
            $historyLog->setModifications("#RJ$order_id - Addon: ". $addon->getLabel() . "  Added ." );
            $em->persist($historyLog);
            // $em->flush();
            // LOGGING INTO HISTORYLOG
            
            $em->flush();
            
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
            // if( $order->getOrderHistoryTotal()['totalDue']->greaterThanStr('0') ) {
            $order->setStatus(0); // MAKING ORDER STATUS TO PENDING/UNPAID
            // }else{
            //     $order->setStatus(1); // MAKING ORDER STATUS TO PAID
            // }
            $em->persist($order);
            $em->flush();
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 

            $em->commit();


            return new JsonResponse( json_encode([
                'status'   => "success",
                'message'  => "Addon Added!",
            ]));

        } catch (Throwable $e) {
       
            $em->rollback();
            return new JsonResponse( json_encode([
                'status'   => "failed",
                'message'  => $e->getMessage(),
            ]));
        }

    }

    /**
     * @Route("admin/add/extra", name="add_extra_to_orderitem")
     * 
     * @param Request $request
     */
    public function addExtra(Request $request): JsonResponse
    {
        try {
            $em     = $this->getEM();
            $em->beginTransaction();
            
            $extras = $em->getRepository(Extra::Class)->findBy( array("id" => $request->request->get('extra_id')), array('sortOrder' => 'ASC') );
            $extra  = $extras[0];
            $passengerCount = $request->request->get('passengerCount');

            $item       = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('item_id')]);
            $oldItem    = clone $item; 
            
            // ADDING EXTRA TO ORDER ITEM
            $itemExtra = new OrderItemHasExtra();
            $itemExtra->setExtra($extra);
            $itemExtra->setPriceType($extra->getPriceType());          
            $itemExtra->setLabel($extra->getLabel()."<br> <small>(</small><small><em>Price x ".( $passengerCount ).": $".($extra->getRackPrice())*( $passengerCount )." USD</em></small><small>)</small>");
            $itemExtra->setAddonTitle($extra->getLabel());
            $itemExtra->setQuantity( $passengerCount );
            $itemExtra->setRackPrice($extra->getRackPrice() * (  $passengerCount  ));
            $itemExtra->setNetPrice($extra->getNetPrice() * (  $passengerCount  ));            
            
            $extra_total_rack_price   = $extra->getRackPrice() * ( $passengerCount );
            $extra_total_net_price    = $extra->getNetPrice() * ( $passengerCount );            

            $extras_tax = $em->getRepository(TaxConfig::class)->findBy([
                'label' => "extras"
            ]); 
            if ( count($extras_tax) > 0 ){
                $extras_tax = $extras_tax[0];
            }
            $month          = $item->getPickDate()->format('n');
            $year           = $item->getPickDate()->format('Y');
            $now            = new DateTime();
            $current_year   = $now->format("Y");

            if ( $year == $current_year ){
                if( $month <= 6 ){
                    $extra_total_tax_price    = ( $extras_tax->getJanMayRate() / 100 ) * $extra_total_rack_price; 
                }else{
                    $extra_total_tax_price    = ( $extras_tax->getJunDecRate() / 100 ) * $extra_total_rack_price; 
                }
            }else if ( $year < $current_year ){
                $extra_total_tax_price    = ( $extras_tax->getJanMayRate() / 100 ) * $extra_total_rack_price; 
            }else if ( $year > $current_year ){
                $extra_total_tax_price    = ( $extras_tax->getJunDecRate() / 100 ) * $extra_total_rack_price; 
            }
            
            $itemExtra->setTax( $extra_total_tax_price );
            

            $item->addExtra($itemExtra);

            // Extra ADDED TO ORDER ITEM. NOW CHANGE PRICES FOR ORDER ITEM
            $item->setTitleRackPrice( $item->getTitleRackPrice() + $extra_total_rack_price );
            $item->setTitleNetPrice( $item->getTitleNetPrice() + $extra_total_net_price );
            $item->setTotalTax( $item->getTotalTax() + $extra_total_tax_price );
            $item->setSubtotalRack( $item->getSubtotalRack() + $extra_total_rack_price );
            $item->setSubtotalNet( $item->getSubtotalNet() + $extra_total_net_price );
            
            $item->setGrandTotal( $item->getGrandTotal() + $extra_total_tax_price +  $extra_total_rack_price);
           
            $item->setAdultRackPrice( $extra->getAdultRackPrice() );
            $item->setChildRackPrice( $extra->getChildRackPrice() );
            $item->setAdultNetPrice( $extra->getAdultNetPrice() );
            $item->setChildNetPrice( $extra->getChildNetPrice() );
            

            $item->setStatus(0); // MAKING ORDERITEM STATUS TO PENDING. 

            $em->persist($item);

            // ADDING TO PAYMENT HISTORY
            global $kernel;
            $token          = $kernel->getContainer()->get('security.token_storage')->getToken();
            $orderUtils     = $kernel->getContainer()->get('wicrew.order.utils');
            $newItem        = $item;
            /* @var User $user */
            $user           = $token->getUser();
            $custom_note    = $extra->getLabel()." added to order item!";
            $orderHistory   = $orderUtils->createOrderHistory_UpdatedItem($newItem, $oldItem, $user, $custom_note);
            $newItem->addHistory($orderHistory);
            $order          = $newItem->getOrder();
            $order->addHistory($orderHistory);
            $em->persist($order);
            // ADDING TO PAYMENT HISTORY
            
            // LOGGING INTO HISTORYLOG
            $historyLog = new HistoryLog();
            $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
            $order_id = $item->getOrder()->getId();
            $historyLog->setModifications("#RJ$order_id - Extra: ". $extra->getLabel() . "  Added ." );
            $em->persist($historyLog);
            // $em->flush();
            // LOGGING INTO HISTORYLOG


            $em->flush();
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
            // if( $order->getOrderHistoryTotal()['totalDue']->greaterThanStr('0') ) {
            $order->setStatus(0); // MAKING ORDER STATUS TO PENDING/UNPAID
            // }else{
            //     $order->setStatus(1); // MAKING ORDER STATUS TO PAID
            // }
            $em->persist($order);
            $em->flush();
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
            $em->commit();

            return new JsonResponse( json_encode([
                'status'   => "success",
                'message'  => "Extra Added!",
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
