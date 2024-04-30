<?php


namespace App\Controller\Admin;

require_once(dirname(__DIR__, 3) . "/phpSpreadsheet/vendor/autoload.php");
require_once( dirname(__DIR__, 3) . "/frontapp/vendor/autoload.php" );

use DrewM\FrontApp\FrontApp;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Wicrew\ProductBundle\Entity\Area;
use PhpOffice\PhpSpreadsheet\Writer as Writer;
use App\Wicrew\SaleBundle\Entity\Discount;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderHistory;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use App\Wicrew\SaleBundle\Service\OrderService;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Knp\Snappy\Pdf;
use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\SaleBundle\Service\Summary\PriceSummary;
use App\Wicrew\DateAvailability\Entity\HistoryLog;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Dashboard Controller
 */
class DashboardController extends BaseAdminController
{
    const ADDITIONAL_DRIVERS_CLASS_NAME = 'OrderItemHasDriver';
    private $additionalDriversConfig = array();

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            'wicrew.order.utils' => OrderService::class,
            'knp_snappy.pdf' => Pdf::class
        ];
    }

    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        /* @var EntityManager */
        $em = $this->getDoctrine()->getManagerForClass($this->entity['class']);
        /* @var QueryBuilder */
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($this->entity['class'], 'entity')
            ->leftJoin('entity.order', 'orderParent');

        if (!empty($dqlFilter)) {
            $queryBuilder->andWhere($dqlFilter);
        }
        $_filters = $this->request->query->get('filters');
        if ( $_filters && array_key_exists('pickDate', $_filters) ){
            $queryBuilder->orderBy('entity.pickDate', 'ASC');    
        }else{
            
            if (null !== $sortField) {
                $queryBuilder->orderBy('entity.' . $sortField, $sortDirection ?: 'DESC');
                $queryBuilder->addOrderBy('entity.pickDate', 'ASC');
            }
        }   

        $queryBuilder->addSelect('CASE WHEN(entity.pickTimeTransport IS NULL) THEN entity.pickTime ELSE entity.pickTimeTransport END AS pickTime_dl');
        $queryBuilder->addOrderBy('pickTime_dl', 'ASC');

        return $queryBuilder;
    }
    protected function createQueryBuilder($sortField= null){
        $queryBuilder->andWhere('t.sortField = :query OR t.myColumn = :query OR t.myColumn = :query')
        ->setParameter('value1', 'value1')
        ->setParameter('value2', 'value2')
        ->setParameter('value3', 'value3');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(Request $request)
    {
        parent::initialize($request);
        $this->additionalDriversConfig = $this->get('easyadmin.config.manager')->getEntityConfig(self::ADDITIONAL_DRIVERS_CLASS_NAME);
    }

    /**
     * {@inheritDoc}
     */
    protected function listAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_LIST);
        
        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll(
            $this->entity['class'], 
            $this->request->query->get('page', 1), 
            $this->request->query->get('max_results', 25), //Show items per page, default is 15
            $this->request->query->get('sortField'), 
            $this->request->query->get('sortDirection'), 
            $this->entity['list']['dql_filter']
        );


        $this->dispatch(EasyAdminEvents::POST_LIST, ['paginator' => $paginator]);

        // Inject additional drivers config to get its data in the TM's list view.
        $additionalDriverFields = $this->additionalDriversConfig['list']['fields'];

        $parameters = [
            'paginator'                     => $paginator,
            'fields'                        => $fields,
            '_driver_entity_config_name'    => self::ADDITIONAL_DRIVERS_CLASS_NAME,
            'driverFields'                  => $additionalDriverFields,
            'batch_form'                    => $this->createBatchForm($this->entity['name'])->createView(),
            'delete_form_template'          => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['list', $this->entity['templates']['list'], $parameters]);
    }

    /**
     *  Management save
     *
     * @Route("/admin/management/save", name="management_save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $messages = [];

        $driversThatHaveEmailsSentIDs = [];
        $driversToSendEmailsTo = [];
        try {
            $em->beginTransaction();

            /* @var OrderItem $orderItem */
            $needToUpdatePrice  = false;
            $orderItem          = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('id')]);
            $order              = $orderItem->getOrder();
            $oldItem            = clone $orderItem; 

            $old_additional_drivers = [];
            $index = 0;
            foreach( $oldItem->getAdditionalDrivers() as $oldAdditionalDriver ){
                
                $driver_pick_date           = $oldAdditionalDriver->getPickDate();
                if( $driver_pick_date != null )
                    $driver_pick_date       = $oldAdditionalDriver->getPickDate()->format('d/m/Y');
                else
                    $driver_pick_date       = "null";

                $driver_pick_time           = $oldAdditionalDriver->getPickTime();
                if( $driver_pick_time != null )
                    $driver_pick_time       = $oldAdditionalDriver->getPickTime()->format('H:i');
                else
                    $driver_pick_time       = "null";

                $driver_driver_id       = $oldAdditionalDriver->getDriver();
                if( $driver_driver_id != null )
                    $driver_driver_id       = $oldAdditionalDriver->getDriver()->getBizName();
                else
                    $driver_driver_id       = "null";

                $driver_vehicle_id          = $oldAdditionalDriver->getVehicle();
                if( $driver_vehicle_id != null )
                    $driver_vehicle_id      = $oldAdditionalDriver->getVehicle()->getName();
                else
                    $driver_vehicle_id      = "null";
                    
                $driver_confirmation_status = $oldAdditionalDriver->getConfirmationStatus();
                if( $driver_confirmation_status == 0 ){
                    $driver_confirmation_status      = "Unassigned";
                } else if( $driver_confirmation_status == 1 ){
                    $driver_confirmation_status      = "Assigned";
                } else if( $driver_confirmation_status == 2 ){
                    $driver_confirmation_status      = "Approved";
                } else if( $driver_confirmation_status == 3 ){
                    $driver_confirmation_status      = "Confirmed";
                }
                $driver_pick_location       = $oldAdditionalDriver->getFromDescription();
                $driver_drop_location       = $oldAdditionalDriver->getToDescription();
                $driver_price               = $oldAdditionalDriver->getNet();
                
                $_temp_old_additional_drivers = [
                    "pick_date"             => $driver_pick_date,
                    "pick_time"             => $driver_pick_time,
                    "confirmation_status"   => $driver_confirmation_status,
                    "driver_id"             => $driver_driver_id,
                    "vehicle_id"            => $driver_vehicle_id,
                    "pick_location"         => $driver_pick_location,
                    "drop_location"         => $driver_drop_location,
                    "price"                 => $driver_price,
                ];

                $old_additional_drivers[$index] = $_temp_old_additional_drivers; 
                $index++;
            }

            $newStatus = (int) $request->request->get('status');
            if (is_int($newStatus)) {
                // LOGGING INTO HISTORYLOG
                $old_value  = $orderItem->getStatus();
                $new_value  = $request->request->get('status');
                if( $old_value != $new_value ){
                    $totalDiscountAmount = 0;
                    foreach( $order->getDiscountValues() as $discount_value ){
                       $totalDiscountAmount = $totalDiscountAmount + (int)$discount_value['discountRack']->__toString();
                    }
                    $orderitemAmountToRefund = $totalDiscountAmount / count( $order->getItems() );
                    if( $new_value == 2 ){
                        $history_new = new OrderHistory();
                        $user        = $this->get('security.token_storage')->getToken()->getUser();
                        $history_new->setType(OrderHistory::TYPE_UPDATED_ITEM);
                        $history_new->setOrder($orderItem->getOrder());
                        $history_new->setUser($user);
                        $history_new->setAmount( "-".($orderItem->getGrandTotal() - $orderitemAmountToRefund));
                        $history_new->setNotes( "Item canceled!" );
                        $orderItem->addHistory($history_new);
                        $order->addHistory($history_new);
                    }
                    if( $old_value == 2 ){
                        $history_new = new OrderHistory();
                        $user        = $this->get('security.token_storage')->getToken()->getUser();
                        $history_new->setType(OrderHistory::TYPE_UPDATED_ITEM);
                        $history_new->setOrder($orderItem->getOrder());
                        $history_new->setUser($user);
                        $history_new->setAmount( $orderItem->getGrandTotal() - $orderitemAmountToRefund );
                        $history_new->setNotes( "Item status changed from canceled!" );
                        $orderItem->addHistory($history_new);
                        $order->addHistory($history_new);
                    }
                }
                if( $old_value != $new_value ){
                    if( $old_value == 0 ){$old_value = "UNPAID";}
                    if( $old_value == 1 ){$old_value = "PAID";}
                    if( $old_value == 2 ){$old_value = "CANCELLED";}

                    if( $new_value == 0 ){$new_value = "UNPAID";}
                    if( $new_value == 1 ){$new_value = "PAID";}
                    if( $new_value == 2 ){$new_value = "CANCELLED";}
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user       = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    $historyLog->setModifications("Billing status changed from $old_value to $new_value for booking #RJ".$order->getId());
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setStatus($newStatus);
                if( $newStatus == 0 ) {
                    $order->setStatus($newStatus);
                }else if( $newStatus == 1 ) {
                    $itemsBillingStatuses = [];
                    foreach ($order->getItems() as $key => $item) {
                        $item_billingStatus = $item->getStatus();
                        if( $item->getId() == $request->request->get('id') ){
                            $item_billingStatus = $newStatus;
                        }
                        array_push($itemsBillingStatuses, $item_billingStatus);                        
                    }   
                    array_unique($itemsBillingStatuses);
                    if( !in_array("0", $itemsBillingStatuses) ){
                        $order->setStatus($newStatus);
                    }
                }else if( $newStatus == 2 ) {
                    $itemsBillingStatuses = [];
                    foreach ($order->getItems() as $key => $item) {
                        $item_billingStatus = $item->getStatus();
                        if( $item->getId() == $request->request->get('id') ){
                            $item_billingStatus = $newStatus;
                        }
                        array_push($itemsBillingStatuses, $item_billingStatus);                        
                    }   
                    array_unique($itemsBillingStatuses);
                    if( !in_array("0", $itemsBillingStatuses) && !in_array("1", $itemsBillingStatuses) ){
                        $order->setStatus($newStatus);
                    }
                }
            }
            
            $confirmationStatus = (int) $request->request->get('confirmation_status');
            if (is_int($confirmationStatus)) {
                // LOGGING INTO HISTORYLOG
                $old_value  = $orderItem->getConfirmationStatus();
                $new_value  = $request->request->get('confirmation_status');
                
                if( $old_value != $new_value ){

                    if( $old_value == 0 ){ $old_value = "UNASSIGNED"; }
                    if( $old_value == 1 ){ $old_value = "ASSIGNED"; }
                    if( $old_value == 2 ){ $old_value = "APPROVED"; }
                    if( $old_value == 3 ){ $old_value = "CONFIRMED"; }

                    if( $new_value == 0 ){ $new_value = "UNASSIGNED"; }
                    if( $new_value == 1 ){ $new_value = "ASSIGNED"; }
                    if( $new_value == 2 ){ $new_value = "APPROVED"; }
                    if( $new_value == 3 ){ $new_value = "CONFIRMED"; }

                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user       = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    $historyLog->setModifications("Confirmation status changed from $old_value to $new_value for booking #RJ".$order->getId());
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setConfirmationStatus($confirmationStatus);
            }


            
            if( $orderItem->getPickArea()->getType() == 1 OR $orderItem->getDropArea()->getType() == 1 ){
                $oldPickTime = $orderItem->getPickTimeTransport();
                $newPickTime = $request->request->get('pickTimeTransport') ? new Datetime($request->request->get('pickTimeTransport')) : '';
    
                // TODO: refactor this into a function
                // email confirm
                $pickTimeChanged = $oldPickTime != $newPickTime;
                if ($request->request->get('pickTimeTransport')) {
                    $orderItem->setPickTimeTransport(new DateTime($request->request->get('pickTimeTransport')));
                }

                // LOGGING INTO HISTORYLOG
                if( !empty($oldPickTime) and !empty($newPickTime) ){

                    if( $oldPickTime->format('H:i') != $newPickTime->format('H:i') ){
                    
                        $historyLog = new HistoryLog();
                        $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                        $historyLog->setCreatedAt( $currentDateTime );
                        $user = $this->get('security.token_storage')->getToken()->getUser();
                        $historyLog->setUser($user);
                        $historyLog->setModifications("Pick time changed from ".$oldPickTime->format('H:i')." to ".$newPickTime->format('H:i')." for booking #RJ".$order->getId());
                        $em->persist($historyLog);
                        $em->flush();                
                    }
                }else if( !empty( $newPickTime ) ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    $historyLog->setModifications("Pick time changed from NULL to ".$newPickTime->format('H:i')." for booking #RJ".$order->getId());
                    $em->persist($historyLog);
                    $em->flush();                
                }
                // LOGGING INTO HISTORYLOG

                
            }else{
                $oldPickTime = $orderItem->getPickTime();
                $newPickTime = $request->request->get('pickTimeTransport') ? new Datetime($request->request->get('pickTimeTransport')) : '';
    
                // TODO: refactor this into a function
                // email confirm
                $pickTimeChanged = $oldPickTime != $newPickTime;
                if ($request->request->get('pickTimeTransport')) {
                    $orderItem->setPickTime(new DateTime($request->request->get('pickTimeTransport')));
                }


                // LOGGING INTO HISTORYLOG
                if( !empty($oldPickTime) and !empty($newPickTime) ){

                    if( $oldPickTime->format('H:i') != $newPickTime->format('H:i') ){
                    
                        $historyLog = new HistoryLog();
                        $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                        $historyLog->setCreatedAt( $currentDateTime );
                        $user = $this->get('security.token_storage')->getToken()->getUser();
                        $historyLog->setUser($user);
                        $historyLog->setModifications("Pick time changed from ".$oldPickTime->format('H:i')." to ".$newPickTime->format('H:i')." for booking #RJ".$order->getId());
                        $em->persist($historyLog);
                        $em->flush();                
                    }
                }else if( !empty( $newPickTime ) ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6'));
                     
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    $historyLog->setModifications("Pick time changed from NULL to ".$newPickTime->format('H:i')." for booking #RJ".$order->getId());
                    $em->persist($historyLog);
                    $em->flush();                
                }
                // LOGGING INTO HISTORYLOG
                
            }

            $departureTime  = $request->request->get('departureTime') ? new Datetime($request->request->get('departureTime')) : false;
            $arrivalTime    = $request->request->get('arrivalTime') ? new Datetime($request->request->get('arrivalTime')) : false;
            
            if( $arrivalTime ){
                // LOGGING INTO HISTORYLOG
                if( $orderItem->getPickTime()->format('H:i') != $arrivalTime->format('H:i') ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    if( empty( $orderItem->getPickTime() ) ){
                        $historyLog->setModifications("Arrival time changed from NULL to ".$arrivalTime->format('H:i')." for booking #RJ".$order->getId());
                    }else{
                        $historyLog->setModifications("Arrival time changed from ". $orderItem->getPickTime()->format('H:i')." to ".$arrivalTime->format('H:i')." for booking #RJ".$order->getId());
                    }
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setPickTime( $arrivalTime );
            }

            if( $departureTime ){
                // LOGGING INTO HISTORYLOG
                
                if( $orderItem->getPickTime()->format('H:i') != $departureTime->format('H:i') ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    if( empty( $orderItem->getPickTime() ) ){
                        $historyLog->setModifications("Departure time changed from NULL to ".$departureTime->format('H:i')." for booking #RJ".$order->getId());
                    }else{
                        $historyLog->setModifications("Departure time changed from ". $orderItem->getPickTime()->format('H:i')." to ".$departureTime->format('H:i')." for booking #RJ".$order->getId());
                    }
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setPickTime( $departureTime );
            
            }

            $supplier       = $em->getRepository(Partner::class)->findOneBy(['id' => $request->request->get('supplier_id')]);
            $currentDriver  = $orderItem->getSupplier();
            $orderItem->setSupplier($supplier);
            
            // LOGGING INTO HISTORYLOG
            if( $currentDriver != $supplier ){
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                $historyLog->setCreatedAt( $currentDateTime );
                $user = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                if( empty( $currentDriver ) ){
                    $historyLog->setModifications("Supplier changed from NULL to ".$supplier->getBizName()." for booking #RJ".$order->getId());
                }elseif( empty( $supplier ) ){
                    $historyLog->setModifications("Supplier changed from ". $currentDriver->getBizName()." to Null for booking #RJ".$order->getId());
                }else{
                    $historyLog->setModifications("Supplier changed from ". $currentDriver->getBizName()." to ".$supplier->getBizName()." for booking #RJ".$order->getId());
                }
                $em->persist($historyLog);
                $em->flush();
            }
            // LOGGING INTO HISTORYLOG
            
            $pickup_area            = $em->getRepository(Area::class)->findOneBy(['name' => $request->request->get('pickArea_name')]);
            if( empty( $pickup_area ) ){
                $em->rollback();
                $error = [
                    'status'    => 'error',
                    'message'   => "Pick-up Area not found!"
                ];
                return new JsonResponse($error);
            }
            $old_pickup_area    = $orderItem->getPickArea();            
            if( $pickup_area != $old_pickup_area ){
                
                $orderItem->setPickArea($pickup_area);
                $needToUpdatePrice = true;
                
                // LOGGING INTO HISTORYLOG
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                $historyLog->setCreatedAt( $currentDateTime );
                $user = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                
                $historyLog->setModifications("Pick-up area changed from ". $old_pickup_area->getName()." to ".$pickup_area->getName()." for booking #RJ".$order->getId());
                
                $em->persist($historyLog);
                $em->flush();
            }
            // LOGGING INTO HISTORYLOG


            $drop_area            = $em->getRepository(Area::class)->findOneBy(['name' => $request->request->get('dropArea_name')]);
            $old_drop_area        = $orderItem->getDropArea();
            if( $drop_area != $old_drop_area ){
                
                $orderItem->setDropArea($drop_area);
                $needToUpdatePrice = true;
                
                // LOGGING INTO HISTORYLOG
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                $historyLog->setCreatedAt( $currentDateTime );
                $user = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                
                $historyLog->setModifications("Drop-off area changed from ". $old_drop_area->getName()." to ".$drop_area->getName()." for booking #RJ".$order->getId());
                
                $em->persist($historyLog);
                $em->flush();
            }
            // LOGGING INTO HISTORYLOG
            
            // PASSENGER NAME
            // LOGGING INTO HISTORYLOG
            $old_value  = $orderItem->getPassengerName();
            $new_value  = $request->request->get('passengerName');
            if( !empty( $old_value ) ){
                if( $old_value != $new_value ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    if( empty( $old_value ) ){
                        $historyLog->setModifications("Passenger Name changed from NULL to $new_value for booking #RJ".$order->getId());
                    }else{
                        $historyLog->setModifications("Passenger Name changed from $old_value to $new_value for booking #RJ".$order->getId());
                    }
                    $em->persist($historyLog);
                    $em->flush();
                }
            }
            // LOGGING INTO HISTORYLOG
            $orderItem->setPassengerName( $request->request->get('passengerName') );


            // Pick Address
            if( !empty( $request->request->get('pickAddress') ) ){
                // LOGGING INTO HISTORYLOG
                $old_value  = $orderItem->getPickAddress();
                $new_value  = $request->request->get('pickAddress');
                
                if( $old_value != $new_value ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user       = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    if( empty( $old_value ) ){
                        $historyLog->setModifications("Pick-up Address changed from NULL to $new_value for booking #RJ".$order->getId());
                    }else{
                        $historyLog->setModifications("Pick-up Address changed from $old_value to $new_value for booking #RJ".$order->getId());
                    }
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setPickAddress( $request->request->get('pickAddress') );

            }
            
            // Drop Address
            if( !empty( $request->request->get('dropAddress') ) ){
                // LOGGING INTO HISTORYLOG
                $old_value  = $orderItem->getDropAddress();
                $new_value  = $request->request->get('dropAddress');
                
                if( $old_value != $new_value ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user       = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    if( empty( $old_value ) ){
                        $historyLog->setModifications("Drop-off Address changed from NULL to $new_value for booking #RJ".$order->getId());
                    }else{
                        $historyLog->setModifications("Drop-off Address changed from $old_value to $new_value for booking #RJ".$order->getId());
                    }
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setDropAddress( $request->request->get('dropAddress') );

            }

            // Pick Airline company
            // LOGGING INTO HISTORYLOG
            $old_value  = $orderItem->getPickAirlineCompany();
            $new_value  = $request->request->get('pickAirlineCompany', null);
            
            if(  !empty( $old_value ) && $old_value != $new_value ){
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
            
                $historyLog->setCreatedAt( $currentDateTime );
                $user       = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                
                if( is_null( $old_value ) ){
                    $old_value = "Null";
                }

                if( is_null( $new_value ) ){
                    $new_value = "Null";
                }
               
                $historyLog->setModifications("Pick-up Airline company changed from '$old_value' to '$new_value' for booking #RJ".$order->getId());
                $em->persist($historyLog);
                $em->flush();
            }
            // LOGGING INTO HISTORYLOG
            $orderItem->setPickAirlineCompany( $request->request->get('pickAirlineCompany', null) );
            
            // Pick Flight Number
            // LOGGING INTO HISTORYLOG
            $old_value  = $orderItem->getPickFlightNumber();
            $new_value  = $request->request->get('pickFlightNumber', null);
            
            if( !empty( $old_value ) && $old_value != $new_value ){
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
            
                $historyLog->setCreatedAt( $currentDateTime );
                $user       = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                if( is_null( $old_value ) || empty( $old_value ) ){
                    $old_value = "Null";
                }

                if( is_null( $new_value ) || empty( $new_value ) ){
                    $new_value = "Null";
                }
                
                $historyLog->setModifications("Pick-up Flight changed from '$old_value' to '$new_value' for booking #RJ".$order->getId());
                $em->persist($historyLog);
                $em->flush();
            }
            // LOGGING INTO HISTORYLOG
            $orderItem->setPickFlightNumber( $request->request->get('pickFlightNumber', null) );

            if($request->request->get('adultCount') <= 0 and $request->request->get('childCount') <= 0){
                $em->rollback();
                $error = [
                    'status'    => 'error',
                    'message'   => "Passenger quantity should be greater than 0."
                ];
                return new JsonResponse($error);
            }

            $selected_vehicle_type  = $request->request->get('vehicleType');
            if($orderItem->getProduct()){
                $old_vehicle_type       = $orderItem->getProduct()->getVehicleType()->getId();
            }else{
                $old_vehicle_type       = 0;
            }
            $old_vehicle            = $orderItem->getVehicle();            
            $vehicleTypeChanged     = false;
            if($old_vehicle){
                $old_vehicle_type = $old_vehicle->getVehicleType()->getId();
            }

            if( !empty($selected_vehicle_type)  and $old_vehicle_type != $selected_vehicle_type){
                $vehicleTypeChanged = true;
            }

            if ($vehicleTypeChanged){
                $selected_vehicle  = $request->request->get('vehicle_id');
                if(empty($selected_vehicle)){
                    $em->rollback();
                    $error = [
                        'status'    => 'error',
                        'message'   => "Please also select vehicle to save!"
                    ];
                    return new JsonResponse($error);
                }else{
                    $_vehicle = $em->getRepository(Vehicle::class)->findOneBy(['id' => $request->request->get('vehicle_id')]);
                    if($_vehicle){
                        $_vehicleType_selected      = $em->getRepository(VehicleType::class)->findOneBy(['id' => $request->request->get('vehicleType')]);
                        $_vehicleType_vehicle_id    = $_vehicle->getVehicleType()->getId();
                        $_vehicleType_selected_id   = $_vehicleType_selected->getId();

                        if($_vehicleType_vehicle_id != $_vehicleType_selected_id){
                            $em->rollback();
                            $error = [
                                'status'    => 'error',
                                'message'   => "Selected vehicle: ".$_vehicle->getName()." does not belongs to vehicle type: ".$_vehicleType_selected->getName()
                            ];
                            return new JsonResponse($error);
                        }
                    }
                }

            }

            // vehicle
            $vehicle = $em->getRepository(Vehicle::class)->findOneBy(['id' => $request->request->get('vehicle_id')]);
            if($vehicle){
                $vehicleType = $vehicle->getVehicleType()->getId();
                if ($orderItem->getProduct()) {
                    // NEED TO UPDATE PRICES
                    $needToUpdatePrice          = true;
                    $max_passenger_count        = (int)$vehicle->getVehicleType()->getMaxPassengerNumber();
                    $total_selected_passengers  = (int)$request->request->get('adultCount') + (int)$request->request->get('childCount');
                    if($max_passenger_count != 0 and $total_selected_passengers > $max_passenger_count){
                        $em->rollback();
                        $error = [
                            'status'    => 'error',
                            'message'   => "You can't choose more than $max_passenger_count passengers for ". $vehicle->getVehicleType()->getName()." - ".$vehicle->getName().". Change Vehicle first or choose correct quantity.",
                            'adultCount' => $orderItem->getAdultCount(),
                            'childCount' => $orderItem->getChildCount(),
                        ];
                        return new JsonResponse($error);
                    }

                }
            }else{
                $vehicleType = null;
            }
            // LOGGING INTO HISTORYLOG
            $old_value  = $orderItem->getVehicle();
            $new_value  = $vehicle;
            
            if( !empty( $old_value ) && $old_value != $new_value ){
                $historyLog = new HistoryLog();
                $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                $historyLog->setCreatedAt( $currentDateTime );
                $user       = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                if( empty( $old_value ) ){
                    $historyLog->setModifications("Vehicle changed from NULL to ".$new_value->getName()." for booking #RJ".$order->getId());
                }else if( empty( $new_value ) ){
                    $historyLog->setModifications("Vehicle changed from ".$old_value->getName()." to Null for booking #RJ".$order->getId());

                }else{
                    $historyLog->setModifications("Vehicle changed from ".$old_value->getName()." to ".$new_value->getName()." for booking #RJ".$order->getId());
                }
                $em->persist($historyLog);
                $em->flush();
            }
            // LOGGING INTO HISTORYLOG
            $orderItem->setVehicle($vehicle);
            $_dlTransportationType = false;

            // Adult Count
            if( !empty( $request->request->get('adultCount') ) || $request->request->get('adultCount') == 0 ){

                // LOGGING INTO HISTORYLOG
                $old_value  = $orderItem->getAdultCount();
                $new_value  = $request->request->get('adultCount');
                
                if( $old_value != $new_value ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user       = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    if( empty( $old_value ) ){
                        $historyLog->setModifications("Adults count changed from NULL to $new_value for booking #RJ".$order->getId());
                    }else{
                        $historyLog->setModifications("Adults count changed from $old_value to $new_value for booking #RJ".$order->getId());
                    }
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setAdultCount( $request->request->get('adultCount') );
                if ( $orderItem->getActivity() ) {
                    // NEED TO UPDATE PRICES
                    $needToUpdatePrice = true;
                }elseif ( strtolower($orderItem->getProduct()->getTransportationType()->getName()) == "jeep-boat-jeep private" ) {
                    $needToUpdatePrice = true;
                    $_dlTransportationType = $orderItem->getProduct()->getTransportationType();

                }    
                
            }
          
            // Child Count
            if( !empty( $request->request->get('childCount') ) || $request->request->get('childCount') == 0 ){
                // LOGGING INTO HISTORYLOG
                $old_value  = $orderItem->getChildCount();
                $new_value  = $request->request->get('childCount');
                
                if( $old_value != $new_value ){
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
                    $historyLog->setCreatedAt( $currentDateTime );
                    $user       = $this->get('security.token_storage')->getToken()->getUser();
                    $historyLog->setUser($user);
                    if( empty( $old_value ) ){
                        $historyLog->setModifications("Children count changed from NULL to $new_value for booking #RJ".$order->getId());
                    }else{
                        $historyLog->setModifications("Children count changed from $old_value to $new_value for booking #RJ".$order->getId());
                    }
                    $em->persist($historyLog);
                    $em->flush();
                }
                // LOGGING INTO HISTORYLOG
                $orderItem->setChildCount( $request->request->get('childCount') );
                if ($orderItem->getActivity()) {
                    // NEED TO UPDATE PRICES
                    $needToUpdatePrice = true;
                }elseif ( strtolower($orderItem->getProduct()->getTransportationType()->getName()) == "jeep-boat-jeep private" ) {
                    $needToUpdatePrice = true;
                    $_dlTransportationType = $orderItem->getProduct()->getTransportationType();
                }   
            }

            // driver  
            $itemHasDriver = $currentDriver;
            if ($itemHasDriver === null) {
                $oldDriver = null;
            } else {
                $oldDriver = $currentDriver;
            }
            $newDriver = $supplier;

            $hasNewDriver = $newDriver !== null && ($oldDriver === null || !$newDriver->equalsID($oldDriver));

            if ($hasNewDriver) {
                $orderItem->setSendEmail(new DateTime());
            } else if ($newDriver === null) {
                $orderItem->setSendEmail(null);
            }

            $sendEmail = $request->request->get('send_email');
            if ($sendEmail) {
                if ($newDriver->getEmail() === null) {
                    $messages[] = $this->translator()->trans('transport_management.save.no_email', ['driverName' => $newDriver->getBizName()]);
                } else {
                    if (!in_array($newDriver->getId(), $driversThatHaveEmailsSentIDs)) {
                        $driversToSendEmailsTo[]        = $newDriver;
                        $driversThatHaveEmailsSentIDs[] = $newDriver->getId();
                    }
                }
            }
            if( $orderItem->getPickDate() != new DateTime( $request->request->get('pickDate') ) ){
                
                // LOGGING INTO HISTORYLOG
                $_pickupDate        = new DateTime($request->request->get('pickDate'));
                $_pickupDate        = $_pickupDate->format("d/m/Y");
                $old_pickDate       = $orderItem->getPickDate();
                $old_pickDate       = $old_pickDate->format("d/m/Y");
                $historyLog         = new HistoryLog();
                $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6'));
                $historyLog->setCreatedAt( $currentDateTime );
                $user       = $this->get('security.token_storage')->getToken()->getUser();
                $historyLog->setUser($user);
                $historyLog->setModifications("Pickup date changed from $old_pickDate to $_pickupDate for #RJ".$order->getId());
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG
            }
            $orderItem->setPickDate(new DateTime($request->request->get('pickDate')));

            $currentDrivers = $oldItem->getAdditionalDrivers();
            $currentIndices = $currentDrivers->getKeys();
            $additionalDrivers = $this->getRequestDataNoThrow($request, 'additionalDrivers', array());

            $editedOrAddedIndices = [];
            /* @var Partner[] $driversToSendEmailsTo */
            $additional_drivers = [];
            $newlyAddedDriver = [];
            foreach ($additionalDrivers as $index => $addDriver) {
                $editedOrAddedIndices[] = $index;
                if( !array_key_exists('driver.id', $addDriver) || empty( $addDriver['driver.id'] ) ){
                    $additional_drivers[$index] = "UNASSIGNED";
                }else{
                    $additional_drivers[$index] = "ASSIGNED";
                }
                if( !array_key_exists('driver.id', $addDriver) )
                    continue;
                $newDriver          = $em->getRepository(Partner::class)->findOneBy(['id' => $addDriver['driver.id']]);
                $vehicle            = $em->getRepository(Vehicle::class)->findOneBy(['id' => $addDriver['vehicle.id']]);
                $fromDesc           = $addDriver['fromDescription'];
                $toDesc             = $addDriver['toDescription'];
                $driver_pickupDate  = $addDriver['pickup_date'];
                if ( $driver_pickupDate != null ) { $driver_pickupDate = new Datetime($addDriver['pickup_date']); }
                $driver_pickupTime  = $addDriver['pickup_time'];
                if ( $driver_pickupTime != null ) { $driver_pickupTime = new Datetime($addDriver['pickup_time']); }
                $driverAssigned     = $addDriver['driverAssigned'];
                if( array_key_exists( 'rack', $addDriver ) ){
                    $rack       = $addDriver['rack'];
                }else{
                    $rack       = $addDriver['net'];
                }
                $net        = $addDriver['net'];
                /* @var OrderItemHasDriver $itemHasDriver */
                $itemHasDriver = $currentDrivers->get($index);
                if ($itemHasDriver === null) {
                    $oldDriver = null;
                    $itemHasDriver = new OrderItemHasDriver();
                    $orderItem->addAdditionalDrivers($itemHasDriver);
                    $em->persist($itemHasDriver);
                } else {
                    $oldDriver = $itemHasDriver->getDriver();
                }
            
                
                $itemHasDriver->setDriver($newDriver);
                $itemHasDriver->setVehicle($vehicle);
                $itemHasDriver->setFromDescription($fromDesc);
                $itemHasDriver->setToDescription($toDesc);
                $itemHasDriver->setConfirmationStatus($driverAssigned);
                if ($driver_pickupDate != null){
                    $itemHasDriver->setPickDate($driver_pickupDate);
                } 
                if( $driver_pickupTime != null ){
                    $itemHasDriver->setPickTime($driver_pickupTime);
                }
                $itemHasDriver->setRack($rack);
                $itemHasDriver->setNet($net);
              
            }


            $em->persist($orderItem);
            $em->flush();


            $productInfo    = [];
            $rack_a         = null;
            $rack_c         = null;
            $tax            = null;
            $rack_tax       = null;
            $net            = null;
            $net_tax        = null;
            $orderUtils = $this->container->get('wicrew.order.utils');
            
            $new_additional_drivers = [];
            $index = 0;
            foreach( $orderItem->getAdditionalDrivers() as $newAdditionalDriver ){
                
                $driver_pick_date           = $newAdditionalDriver->getPickDate();
                if( $driver_pick_date != null )
                    $driver_pick_date       = $newAdditionalDriver->getPickDate()->format('d/m/Y');
                else
                    $driver_pick_date       = "null";

                $driver_pick_time           = $newAdditionalDriver->getPickTime();
                if( $driver_pick_time != null )
                    $driver_pick_time       = $newAdditionalDriver->getPickTime()->format('H:i');
                else
                    $driver_pick_time       = "null";

                $driver_driver_id       = $newAdditionalDriver->getDriver();
                if( $driver_driver_id != null )
                    $driver_driver_id       = $newAdditionalDriver->getDriver()->getBizName();
                else
                    $driver_driver_id       = "null";

                $driver_vehicle_id          = $newAdditionalDriver->getVehicle();
                if( $driver_vehicle_id != null )
                    $driver_vehicle_id      = $newAdditionalDriver->getVehicle()->getName();
                else
                    $driver_vehicle_id      = "null";
                    
                $driver_confirmation_status = $newAdditionalDriver->getConfirmationStatus();
                if( $driver_confirmation_status == 0 ){
                    $driver_confirmation_status      = "Unassigned";
                } else if( $driver_confirmation_status == 1 ){
                    $driver_confirmation_status      = "Assigned";
                } else if( $driver_confirmation_status == 2 ){
                    $driver_confirmation_status      = "Approved";
                } else if( $driver_confirmation_status == 3 ){
                    $driver_confirmation_status      = "Confirmed";
                }
                $driver_pick_location       = $newAdditionalDriver->getFromDescription();
                $driver_drop_location       = $newAdditionalDriver->getToDescription();
                $driver_price               = $newAdditionalDriver->getNet();
                
                $_temp_new_additional_drivers = [
                    "pick_date"             => $driver_pick_date,
                    "pick_time"             => $driver_pick_time,
                    "confirmation_status"   => $driver_confirmation_status,
                    "driver_id"             => $driver_driver_id,
                    "vehicle_id"            => $driver_vehicle_id,
                    "pick_location"         => $driver_pick_location,
                    "drop_location"         => $driver_drop_location,
                    "price"                 => $driver_price,
                ];

                $new_additional_drivers[$index] = $_temp_new_additional_drivers; 
                $index++;
            }

            $order_id = $orderItem->getOrder()->getId();
            if( count( $new_additional_drivers ) > count( $old_additional_drivers ) ){
                foreach( $newlyAddedDriver as $newDriver ){
                    // LOGGING INTO HISTORYLOG
                    global $kernel;
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                    $historyLog->setCreatedAt( $currentDateTime );
                    $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                    $order_id = $orderItem->getOrder()->getId();
                    $historyLog->setModifications( "#RJ$order_id - Additional supplier added! " );
                    $em->persist($historyLog);
                    $em->flush();
                    // LOGGING INTO HISTORYLOG
                }
            }else if( count( $new_additional_drivers ) < count( $old_additional_drivers ) ){
                    
                    // LOGGING INTO HISTORYLOG
                    global $kernel;
                    $historyLog = new HistoryLog();
                    $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
                    $historyLog->setCreatedAt( $currentDateTime );
                    $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                    $order_id = $orderItem->getOrder()->getId();
                    $deleted_suppliers = count( $old_additional_drivers ) - count( $new_additional_drivers );
                    $historyLog->setModifications( "#RJ$order_id - $deleted_suppliers Additional supplier removed! " );
                    $em->persist($historyLog);
                    $em->flush();
                    // LOGGING INTO HISTORYLOG
                
            }else if( count( $new_additional_drivers ) == count( $old_additional_drivers ) ){
                // Same number of drivers
                foreach ( $new_additional_drivers as $index => $newAdditionalDriver ) {
    
                    if( $newAdditionalDriver["pick_date"] != $old_additional_drivers[$index]["pick_date"] && ( $newAdditionalDriver["pick_date"] != "" || $newAdditionalDriver["pick_date"] != "null" ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName pick-up date changed from '".$old_additional_drivers[$index]["pick_date"]."' to '".$newAdditionalDriver["pick_date"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }
    
                    if( $newAdditionalDriver["pick_time"] != $old_additional_drivers[$index]["pick_time"] && ( $newAdditionalDriver["pick_time"] != "" || $newAdditionalDriver["pick_time"] != "null" ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName pick-up time changed from '".$old_additional_drivers[$index]["pick_time"]."' to '".$newAdditionalDriver["pick_time"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }
    
                    if( $newAdditionalDriver["confirmation_status"] != $old_additional_drivers[$index]["confirmation_status"]  && ( !empty($newAdditionalDriver["confirmation_status"]) || $newAdditionalDriver["confirmation_status"] != "null"  ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName confirmation status changed from '".$old_additional_drivers[$index]["confirmation_status"]."' to '".$newAdditionalDriver["confirmation_status"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }
    
                    if( $newAdditionalDriver["driver_id"] != $old_additional_drivers[$index]["driver_id"] && ( !empty($newAdditionalDriver["driver_id"]) || $newAdditionalDriver["driver_id"] != "null"  ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName supplier changed from '".$old_additional_drivers[$index]["driver_id"]."' to '".$newAdditionalDriver["driver_id"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }
    
                    if( $newAdditionalDriver["vehicle_id"] != $old_additional_drivers[$index]["vehicle_id"] && ( !empty($newAdditionalDriver["vehicle_id"]) || $newAdditionalDriver["vehicle_id"] != "null"  ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName vehicle changed from '".$old_additional_drivers[$index]["vehicle_id"]."' to '".$newAdditionalDriver["vehicle_id"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }
    
                    if( $newAdditionalDriver["pick_location"] != $old_additional_drivers[$index]["pick_location"] && ( !empty($newAdditionalDriver["pick_location"]) || $newAdditionalDriver["pick_location"] != "null"  ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName pick-up location changed from '".$old_additional_drivers[$index]["pick_location"]."' to '".$newAdditionalDriver["pick_location"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }
    
                    if( $newAdditionalDriver["drop_location"] != $old_additional_drivers[$index]["drop_location"] && ( !empty($newAdditionalDriver["drop_location"]) || $newAdditionalDriver["drop_location"] != "null"  ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName drop-off location changed from '".$old_additional_drivers[$index]["drop_location"]."' to '".$newAdditionalDriver["drop_location"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }
    
                    if( $newAdditionalDriver["price"] != $old_additional_drivers[$index]["price"] && ( !empty($newAdditionalDriver["price"]) || $newAdditionalDriver["price"] != "null"  ) ){
                        // LOGGING INTO HISTORYLOG
                        global $kernel;
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $newDriverBizName   = $newAdditionalDriver["driver_id"];
                        $historyLog         = new HistoryLog();
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( $kernel->getContainer()->get('security.token_storage')->getToken()->getUser() );
                        $historyLog->setModifications( "#RJ$order_id - Additional supplier $newDriverBizName net price changed from '".$old_additional_drivers[$index]["price"]."' to '".$newAdditionalDriver["price"]."'" );
                        $em->persist($historyLog);
                        $em->flush();
                        // LOGGING INTO HISTORYLOG
                    }    
                }
            }



            if( $needToUpdatePrice ){
                if ($orderItem->getProduct()) {
                    $productInfo['id']      = $orderItem->getProduct()->getId();
                    $productInfo['type']    = "product";
                } else if ($orderItem->getActivity()) {
                    $productInfo['id']      = $orderItem->getActivity()->getId();
                    $productInfo['type']    = "activity";
                }
                if( $productInfo['type'] == "activity" ){
                
                    $activityType               = $orderItem->getActivityType();
                    if( strtolower($activityType) == "group" ){
                        $activityAdultRackPrice     = $orderItem->getAdultCount() * $orderItem->getActivity()->getGroupAdultRackPrice();
                        $activityAdultNetPrice      = $orderItem->getAdultCount() * $orderItem->getActivity()->getGroupAdultNetPrice();
                        $activityChildRackPrice     = $orderItem->getChildCount() * $orderItem->getActivity()->getGroupKidRackPrice();
                        $activityChildNetPrice      = $orderItem->getChildCount() * $orderItem->getActivity()->getGroupKidNetPrice();
                    }else{
                        $activityAdultRackPrice     = $orderItem->getAdultCount() * $orderItem->getActivity()->getAdultRackPrice();
                        $activityChildRackPrice     = $orderItem->getChildCount() * $orderItem->getActivity()->getChildRackPrice();
                        
                        $activityAdultNetPrice      = $orderItem->getAdultCount() * $orderItem->getActivity()->getAdultNetPrice();
                        $activityChildNetPrice      = $orderItem->getChildCount() * $orderItem->getActivity()->getChildNetPrice();                    
                    }
                   
                    $tax_rate                   = $orderItem->getTaxValue();
                    $additionalFeePick          = $orderItem->getPickAddFeeRack();
                    $additionalFeeDrop          = $orderItem->getDropAddFeeRack();
                    
                    $titleRackPrice             = ( $activityAdultRackPrice + $activityChildRackPrice );
                    $titleNetPrice              = ( $activityAdultNetPrice  + $activityChildNetPrice  );
                    $subtotal_rack              = $titleRackPrice + $additionalFeePick + $additionalFeeDrop;
                    $subtotal_net               = $titleNetPrice;
                    $total_tax                  = (($tax_rate / 100) * $subtotal_rack);
                    $grand_total                = $subtotal_rack + $total_tax;
                    
                    $rack_a     =  $activityAdultRackPrice + $additionalFeePick + $additionalFeeDrop ;
                    $rack_c     =  $activityChildRackPrice ;
                    $tax        =  ( $tax_rate / 100) * $subtotal_rack ;
                    $rack_tax   =  $subtotal_rack + $total_tax;
                    $net        =  $subtotal_net;
                    $net_tax    =  $subtotal_net + (( $tax_rate / 100) * $subtotal_net ) ;
                    
                    $rack_a     =  number_format((float)$rack_a, 2, '.', ',');
                    $rack_c     =  number_format((float)$rack_c, 2, '.', ',');
                    $tax        =  number_format((float)$tax, 2, '.', ',');
                    $rack_tax   =  number_format((float)$rack_tax, 2, '.', ',');
                    $net        =  number_format((float)$net, 2, '.', ',');
                    $net_tax    =  number_format((float)$net_tax, 2, '.', ',');

                    $rack_a     = "$".$rack_a;
                    $rack_c     = "$".$rack_c;
                    $tax        = "$".$tax;
                    $rack_tax   = "$".$rack_tax;
                    $net        = "$".$net;
                    $net_tax    = "$".$net_tax;

                    $orderItem->setTitleRackPrice(new Money($titleRackPrice));
                    $orderItem->setTitleNetPrice(new Money($titleNetPrice));
                    $orderItem->setSubtotalRack(new Money($subtotal_rack));
                    $orderItem->setSubtotalNet(new Money($subtotal_net));
                    $orderItem->setTotalTax(new Money($total_tax));
                    $orderItem->setGrandTotal(new Money($grand_total));


                    $customServices = $orderItem->getCustomServices();
                    foreach($customServices as $customService){
                        $customServiceLabel     = $customService->getLabel(); 
                        $customServiceRackPrice = $customService->getRackPrice(); 
                        $customServiceTax       = ( $customServiceRackPrice * 0.13);

                        $orderItem->setTitleRackPrice( $orderItem->getTitleRackPrice() + $customServiceRackPrice );
                        $orderItem->setSubtotalRack( $orderItem->getSubtotalRack() + $customServiceRackPrice );
                        $orderItem->setTotalTax( $orderItem->getTotalTax() + $customServiceTax );
                        $orderItem->setGrandTotal( $orderItem->getGrandTotal() + $customServiceRackPrice + $customServiceTax );
                                
                    }

                    $em->persist($orderItem);
                    $em->flush();                    
                    
                }else{
                    // find product against selected vehicle and areas
                    $orderItemVehicle       = $orderItem->getVehicle();
                    if( $orderItemVehicle == null ){
                        
                        $em->rollback();
                        
                        $error = [
                            'status'            => 'vehicle_error',
                            'message'           => "Please select vehicle.",
                           
                        ];
                        return new JsonResponse($error);
                    }
                  
                    $orderItemVehicleType   = $orderItemVehicle->getVehicleType();
                    $orderItemPickArea      = $orderItem->getPickArea();
                    $orderItemDropArea      = $orderItem->getDropArea();
                    $areaFrom               = $em->getRepository(Area::class)->findOneById($orderItemPickArea->getId());
                    $areaTo                 = $em->getRepository(Area::class)->findOneById($orderItemDropArea->getId());

                    $new_products = $em->getRepository(Product::class)->findBy(
                        [
                            'areaFrom'              => $areaFrom,
                            'areaTo'                => $areaTo,
                            'vehicleType'           => $orderItemVehicleType->getId(),
                            'enabled'               => true,
                            'archived'              => false,                                                        
                        ]
                    );

                    if( $_dlTransportationType != false ){
                        $new_products = $em->getRepository(Product::class)->findBy(
                            [
                                'areaFrom'              => $areaFrom,
                                'areaTo'                => $areaTo,
                                'vehicleType'           => $orderItemVehicleType->getId(),
                                'enabled'               => true,
                                'archived'              => false,                                                        
                                'transportationType'    => $_dlTransportationType,                                                        

                            ]
                        );
                    }

                    if( count($new_products) > 0 ){
                        $new_product =     $new_products[0];
    
                        // calculate new product price agaist quantities


                       
                        $productType    = $new_product->getTransportationType()->getName();

                        $shuttles = $em->getRepository(TaxConfig::class)->findBy([
                            'label' => "shuttles"
                        ]);
                        if ( count($shuttles) > 0 ){
                            $shuttles = $shuttles[0];
                        }
                
                        $water_taxi = $em->getRepository(TaxConfig::class)->findBy([
                            'label' => "water-taxi"
                        ]);    
                        if ( count($water_taxi) > 0 ){
                            $water_taxi = $water_taxi[0];
                        } 
                
                        $jbj = $em->getRepository(TaxConfig::class)->findBy([
                            'label' => "jbj"
                        ]);
                        if ( count($jbj) > 0 ){
                            $jbj = $jbj[0];
                        }
                
                        $flights = $em->getRepository(TaxConfig::class)->findBy([
                            'label' => "flights"
                        ]); 
                        if ( count($flights) > 0 ){
                            $flights = $flights[0];
                        }


                        $month          = $orderItem->getPickDate()->format('n');
                        $year           = $orderItem->getPickDate()->format('Y');
                        $now            = new DateTime();
                        $current_year   = $now->format("Y");
                        
                        if (strpos(strtolower($productType), "shuttle") !== false) {

                           
                            if ( $year == $current_year ){
                                if( $year == 2023 && $month <= 6 ){
                                    $tax_rate    = $shuttles->getJanMayRate(); 
                                }else{
                                    $tax_rate    = $shuttles->getJunDecRate(); 
                                }
                            }else if ( $year < $current_year ){

                                $tax_rate    = $shuttles->getJanMayRate(); 

                            }else if ( $year > $current_year ){

                                $tax_rate    = $shuttles->getJunDecRate(); 
                            }
                            
            
                        }
                        if (strpos(strtolower($productType), "water") !== false) {


                        
                            if ( $year == $current_year ){
                                if( $year == 2023 && $month <= 6 ){
                                    $tax_rate    = $water_taxi->getJanMayRate(); 
                                }else{
                                    $tax_rate    = $water_taxi->getJunDecRate(); 
                                }
                            }else if ( $year < $current_year ){

                                $tax_rate    = $water_taxi->getJanMayRate(); 
                                
                            }else if ( $year > $current_year ){

                                $tax_rate    = $water_taxi->getJunDecRate(); 
                            }
               
                        }
                        if (strpos(strtolower($productType), "jeep") !== false) {
                           
                            if ( $year == $current_year ){
                                if( $year == 2023 && $month <= 6 ){
                                    $tax_rate    = $jbj->getJanMayRate(); 
                                }else{
                                    $tax_rate    = $jbj->getJunDecRate(); 
                                }
                            }else if ( $year < $current_year ){

                                $tax_rate    = $jbj->getJanMayRate(); 
                                
                            }else if ( $year > $current_year ){

                                $tax_rate    = $jbj->getJunDecRate(); 
                            }
               
             
                        }
                        if (strpos(strtolower($productType), "jbj") !== false) {

                           
                            if ( $year == $current_year ){
                                if( $year == 2023 && $month <= 6 ){
                                    $tax_rate    = $jbj->getJanMayRate(); 
                                }else{
                                    $tax_rate    = $jbj->getJunDecRate(); 
                                }
                            }else if ( $year < $current_year ){

                                $tax_rate    = $jbj->getJanMayRate(); 
                                
                            }else if ( $year > $current_year ){

                                $tax_rate    = $jbj->getJunDecRate(); 
                            }
              
                        }
                        if (strpos(strtolower($productType), "flight") !== false) {

                            if ( $year == $current_year ){
                                if( $year == 2023 && $month <= 6 ){
                                    $tax_rate    = $flights->getJanMayRate(); 
                                }else{
                                    $tax_rate    = $flights->getJunDecRate(); 
                                }
                            }else if ( $year < $current_year ){

                                $tax_rate    = $flights->getJanMayRate(); 
                                
                            }else if ( $year > $current_year ){

                                $tax_rate    = $flights->getJunDecRate(); 
                            }
                        }

                        if ( ( strpos( strtolower( $productType ), "water" ) !== false ) || ( strpos( strtolower( $productType ), "jeep shared" ) !== false ) ) {
                            
                            $adultRackPrice     = $orderItem->getAdultCount() * $new_product->getAdultRackPrice();
                            $childRackPrice     = $orderItem->getChildCount() * $new_product->getChildRackPrice();
                            $adultNetPrice      = $orderItem->getAdultCount() * $new_product->getAdultNetPrice();
                            $childNetPrice      = $orderItem->getChildCount() * $new_product->getChildNetPrice();
                            $fixedRackPrice     = $adultRackPrice + $childRackPrice;
                            $fixedNetPrice      = $adultNetPrice + $childNetPrice;

                            $rack_a         = $adultRackPrice;
                            $rack_c         = $childRackPrice;
                            $subtotal_rack  = $adultRackPrice + $childRackPrice;
                            $tax            = ( $tax_rate / 100) * $subtotal_rack ;
                            $rack_tax       = $subtotal_rack + $tax;
                            $subtotal_net   = $adultNetPrice + $childNetPrice;

                            $net            = $adultNetPrice + $childNetPrice;
                            $net_tax        = $subtotal_net + (( $tax_rate / 100) * $subtotal_net ) ;                        
                        }else{
                        
                            $fixedRackPrice = $new_product->getFixedRackPrice();
                            $fixedNetPrice  = $new_product->getFixedNetPrice();
                            if ( strtolower($orderItem->getProduct()->getTransportationType()->getName()) == "jeep-boat-jeep private" ) {
                                $total_passengers       = $orderItem->getAdultCount() + $orderItem->getChildCount();
                                if( $total_passengers > 5 ){
                                    $additionalPassengers   = $total_passengers - 5;
                                } else{
                                    $additionalPassengers   = 0;
                                }
                                $fixedRackPrice     = $fixedRackPrice + ( $additionalPassengers * 45 );
                                $fixedNetPrice      = $fixedNetPrice + ( $additionalPassengers * 25 );

                            }    
                            
                            $rack_a         = $fixedRackPrice;
                            $rack_c         = "--";
                            $subtotal_rack  = $fixedRackPrice;
                            $tax            = ( $tax_rate / 100) * $fixedRackPrice ;
                            $rack_tax       = $subtotal_rack + $tax;
                            $subtotal_net   = $fixedNetPrice;
                            $net            = $subtotal_net;
                            $net_tax        = $subtotal_net + (( $tax_rate / 100) * $subtotal_net ) ;
                        }

                        $total_tax      = ($tax_rate / 100) * $subtotal_rack;
                        $grand_total    = $subtotal_rack + $total_tax;

                        
                        
                        $rack_a     =  number_format((float)$rack_a, 2, '.', ',');
                        $rack_c     =  number_format((float)$rack_c, 2, '.', ',');
                        $tax        =  number_format((float)$tax, 2, '.', ',');
                        $rack_tax   =  number_format((float)$rack_tax, 2, '.', ',');
                        $net        =  number_format((float)$net, 2, '.', ',');
                        $net_tax    =  number_format((float)$net_tax, 2, '.', ',');

                        $rack_a     = "$".$rack_a;
                        $rack_c     = " ";
                        $tax        = "$".$tax;
                        $rack_tax   = "$".$rack_tax;
                        $net        = "$".$net;
                        $net_tax    = "$".$net_tax;

                        // update product, order item prices
                        $orderItem->setTitleRackPrice( new Money($subtotal_rack) );
                        $orderItem->setTitleNetPrice( new Money($subtotal_net) );
                        $orderItem->setSubtotalRack( new Money($subtotal_rack) );
                        $orderItem->setSubtotalNet( new Money($subtotal_net) );
                        $orderItem->setTotalTax( new Money($total_tax) );
                        $orderItem->setGrandTotal( new Money($grand_total) );
                        $orderItem->setProduct( $new_product );
                        
                        $customServices = $orderItem->getCustomServices();
                        foreach($customServices as $customService){
                            $customServiceLabel     = $customService->getLabel(); 
                            $customServiceRackPrice = $customService->getRackPrice(); 
                            $customServiceTax       = ($customServiceRackPrice * 0.13);
    
                            $orderItem->setTitleRackPrice( $orderItem->getTitleRackPrice() + $customServiceRackPrice );
                            $orderItem->setSubtotalRack( $orderItem->getSubtotalRack() + $customServiceRackPrice );
                            $orderItem->setTotalTax( $orderItem->getTotalTax() + $customServiceTax );
                            $orderItem->setGrandTotal( $orderItem->getGrandTotal() + $customServiceRackPrice + $customServiceTax );
                                    
                        }
                        
                        $em->persist($orderItem);
                        $em->flush();

                        
                    }else{
                        $em->rollback();
                        
                        $error = [
                            'status'            => 'vehicle_error',
                            'message'           => "Price not found for ".$areaFrom->getName()." and ".$areaTo->getName()." for ".$orderItemVehicleType->getName()." - ".$orderItem->getVehicle()->getName().". Please select other value.",
                           
                        ];
                        return new JsonResponse($error);
                    }

                }
                // NOW UPDATE WHOLE ORDER 
                
                $newItem = $orderItem;
            
                $token = $this->get('security.token_storage')->getToken();
                /* @var User $user */
                $user = $token->getUser();
                $orderHistory = $orderUtils->createOrderHistory_UpdatedItem($newItem, $oldItem, $user);
                $newItem->addHistory($orderHistory);
                
                $order = $newItem->getOrder();
                $order->addHistory($orderHistory);                

            }
            
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 
            if( $orderItem->getStatus() != 2 ){
                if( $order->getOrderHistoryTotal()['totalDue']->greaterThanStr('0') ) {
                    $order->setStatus(0); // MAKING ORDER STATUS TO PENDING/UNPAID
                    // if($orderItem != null )
                        // $orderItem->setStatus(0);
                }else{
                    $order->setStatus(1); // MAKING ORDER STATUS TO PAID
                    if($orderItem != null )
                        $orderItem->setStatus(1);
                }
            }
            // CHECK IF ORDER HAVE REMAINING AMOUNT TO CHARGE 


           foreach ($driversToSendEmailsTo as $driver) {
                $orderUtils->sendDriverEmails($driver, $this);
            }
            $em->flush();
            $em->commit();
            $itemNewStatus = $orderItem->getStatus();
        } catch (Throwable $e) {
            dump($e);
            die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['itemNewStatus' => $itemNewStatus, 'rack_a' => $rack_a, 'rack_c' => $rack_c, 'tax' => $tax, 'rack_tax' => $rack_tax, 'net' => $net, 'net_tax' => $net_tax, 'vehicleType' => $vehicleType,'additional_drivers' => $additional_drivers, 'messages' => $messages, 'sentIDs' => $driversThatHaveEmailsSentIDs]);
    } 

    /**
     *  management resent-confirmation
     *
     * @Route("/admin/management/resent-confirmation", name="management_resent_confirmation")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function resentConfirmationAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $messages = [];

        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));

            if (!$orderItem) {
                return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
            }
            // LOGGING INTO HISTORYLOG
            $historyLog = new HistoryLog();
            $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $this->get('security.token_storage')->getToken()->getUser() );
            $order_id = $orderItem->getOrder()->getId();
            $historyLog->setModifications("#RJ$order_id - Confirmation e-mail resent." );
            $em->persist($historyLog);
            $em->flush();
            // LOGGING INTO HISTORYLOG
            $order = $orderItem->getOrder();

            $cardBrand = $order->getCardBrand();
            $last4Digits = $order->getLast4Digits();

            $message = new \Swift_Message();
            $media_path = $this->get('kernel')->getProjectDir() . '/public/';
            $logoSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/logo.png'));
            $tripadvisorSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/tripadvisor.png'));
            $facebookSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/facebook.png'));
            $wopitaSrc = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/wopita.png'));
            $imageItemSrcs = [];
            $orderUtils = $this->get('wicrew.order.utils');
            foreach ($order->getItems() as $key => $item) {
                $imageItemSrcs[$order->getId()] = $orderUtils->getOrderItemImage($item);
            }


            $site_url = $request->request->get('siteURL');
            $utils = $this->get('wicrew.core.utils');
            $q = json_encode(['oid' => $order->getId()]);
            $data = $utils->encrypt_decrypt($q, 'encrypt');
            
            $payment_link = $site_url . $this->generateUrl('order_mailpayment_transaction') . '?q=' . $data;
            
            $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                'order' => $order,
                'cardBrand' => $cardBrand,
                'last4Digits' => $last4Digits,
                'logoSrc' => $logoSrc,
                'tripadvisorSrc' => $tripadvisorSrc,
                'facebookSrc' => $facebookSrc,
                'wopitaSrc' => $wopitaSrc,
                'imageItemSrcs' => $imageItemSrcs,
                'payment_link' => $payment_link,
            ]);

            $body_pdf = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                'order' => $order,
                'cardBrand' => $cardBrand,
                'last4Digits' => $last4Digits,
                'payment_link' => $payment_link,
                'isUsedInPdf' => true
            ]);

            $translator = $this->get('translator');
            $subject_trans_key = $order->getQuote() ? 'email.confirm.order.quote' : 'email.confirm.order';
            if( $order->getOrderHistoryTotal()['totalDue']->greaterThanStr('0') || $order->getStatus() == 0 ){ // Order is un-paid
                $subject_trans_key = 'email.confirm.order.quote';
            }else{
                $subject_trans_key = 'email.confirm.order';
            }
            $subject = $translator->trans($subject_trans_key);
            $siteEmail = 'info@iltcostarica.com';
            $customerEmail = $order->getEmail();

            $pdfOutputPath = $this->get('kernel')->getProjectDir() . '/var/log/confirm.pdf';
            $this->get('knp_snappy.pdf')->generateFromHtml($body_pdf, $pdfOutputPath, [
                'margin-right' => '0mm',
                'margin-left' => '0mm'
            ], true);
            $pdfAttachment = [
                'path' => $pdfOutputPath,
                'filename' => 'confirmation.pdf'
            ];
            $mailerService = $this->get('wicrew.core.mailer');

            $mailerService->send([
                'from' => $siteEmail,
                'to' => $customerEmail,
                'subject' => $subject,
                'body' => $body,
                'attachments' => [$pdfAttachment]
            ]);
        } catch (Throwable $e) {
            dump($e);
            die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['status' => 'success']);
    }

    /**
     *  management send-email-to-driver
     *
     * @Route("/admin/management/send-email-to-driver", name="management_send_email_to_driver")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendEmailToDriverAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $messages = [];

        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));

            if (!$orderItem) {
                return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
            }

            // LOGGING INTO HISTORYLOG
            $historyLog = new HistoryLog();
            $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $this->get('security.token_storage')->getToken()->getUser() );
            $order_id = $orderItem->getOrder()->getId();
            $historyLog->setModifications("#RJ$order_id - Sent e-mail to driver." );
            $em->persist($historyLog);
            $em->flush();
            // LOGGING INTO HISTORYLOG

            $additionalDrivers = $orderItem->getAdditionalDrivers();

            $orders = [];

            $orderUtils = $this->get('wicrew.order.utils');
            $orderItemType = $orderItem->getType();
            if($orderItemType == "activity_regular" || $orderItemType == "activity_regular"){
                $orderItemType = "Activity";

            }else if( $orderItemType == "private_shuttle" ){
                $orderItemType = "Private shuttle";
                
            }else if( $orderItemType == "shared_shuttle" ){
                $orderItemType = "Shared shuttle";

            }else if( $orderItemType == "private_flight" ){
                $orderItemType = "Private flight";

            }else if( $orderItemType == "private_jbj" ){
                $orderItemType = "Private JBJ";

            }else if( $orderItemType == "shared_jbj" ){
                $orderItemType = "Shared JBJ";

            }else if( $orderItemType == "riding_jbj" ){
                $orderItemType = "Riding JBJ";

            }else if( $orderItemType == "water_taxi" ){
                $orderItemType = "Water taxi";

            }

            $orders[] = $orderItem;
            $assignments_to_send = [];
            if ($orderItem->getSupplier()) {
                $orderUtils->sendDriverEmails($orderItem->getSupplier(), $this, $assignments_to_send, $orders, $orderItemType);
            }

            if ($additionalDrivers) {
                foreach ($additionalDrivers as $key => $additionalDriver) {
                    $assignments_to_send = [];
                    $assignments_to_send[] = $additionalDriver;
                    $orders = [];
                    if ($additionalDriver->getDriver()) {
                        $orderUtils->sendDriverEmails($additionalDriver->getDriver(), $this, $assignments_to_send, $orders, $orderItemType);
                    }
                }
            }
        } catch (Throwable $e) {
            dump($e);
            die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['status' => 'success']);
    }


    /**
     * send-email-to-driver-from-front
     *
     * @Route("/admin/dashboard/send-email-from-front", name="send_email_from_front")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendEmailFromFrontAction(Request $request)
    {
        $em                     = $this->getDoctrine()->getManager();
        $utils                  = $this->get('wicrew.core.utils');
        $frontApiSettings       = $utils->getSystemConfigValues('front/api', true);
        $frontApiSettings       = $frontApiSettings['front']['api'];
        
        $frontApiToken          = $frontApiSettings['token'];
        $frontApiChannelName    = $frontApiSettings['channel']['name'];
        $frontApiAuthorEmail    = $frontApiSettings['author'];

        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));

            if (!$orderItem) {
                return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
            }
            // LOGGING INTO HISTORYLOG
            $historyLog = new HistoryLog();
            $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $this->get('security.token_storage')->getToken()->getUser() );
            $order_id = $orderItem->getOrder()->getId();
            $historyLog->setModifications("#RJ$order_id - Draft created in frontApp." );
            $em->persist($historyLog);
            $em->flush();
            // LOGGING INTO HISTORYLOG
            $orders             = [];
            $orderItemType      = $orderItem->getType();

            if($orderItemType == "activity_regular" || $orderItemType == "activity_regular"){
                $orderItemType = "Activity";

            }else if( $orderItemType == "private_shuttle" ){
                $orderItemType = "Private shuttle";
                
            }else if( $orderItemType == "shared_shuttle" ){
                $orderItemType = "Shared shuttle";

            }else if( $orderItemType == "private_flight" ){
                $orderItemType = "Private flight";

            }else if( $orderItemType == "private_jbj" ){
                $orderItemType = "Private JBJ";

            }else if( $orderItemType == "shared_jbj" ){
                $orderItemType = "Shared JBJ";

            }else if( $orderItemType == "riding_jbj" ){
                $orderItemType = "Riding JBJ";

            }else if( $orderItemType == "water_taxi" ){
                $orderItemType = "Water taxi";

            }

            $orders[] = $orderItem;
            $assignments_to_send = [];
        
    
            if ($orderItemType == 'Activity') {
                $subject    = "Supplier Email (Tours)";
                $template   = 'WicrewSaleBundle:Admin:Email/order.mail.front.assigndriver.html.twig';
            } else {
                $subject    = "Assign Driver Email ($orderItemType)";
                $template   = 'WicrewSaleBundle:Admin:Email/order.mail.front.assignsupplier.html.twig';
            }

            if($orderItem->getSupplier()){

                $_driver = $orderItem->getSupplier()->getBizName();
            }else{
                $_driver = " ";
            }
            
            $bodyDriver = $this->renderTwigToString($template, [
                'assignments'   => $assignments_to_send,
                'orders'        => $orders,
                'driver'        => $_driver,
                'toAdmin'       => false
            ]);
            
            $FrontApp   = new FrontApp($frontApiToken);

            $channels   = $FrontApp->get("channels");
            if( array_key_exists( "_error", $channels ) ){ 
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $channels["_error"]["message"] ] );
            }

            $channel_id   = "";
            $existing_channels = [];
            foreach($channels["_results"] as $channels_result ){
                // echo $channels_result["name"]."\n";
                array_push( $existing_channels, $channels_result["name"] );
                if( strtolower( $channels_result["name"] ) == strtolower( $frontApiChannelName ) ){
                    $channel_id = $channels_result["id"];
                }
            }

            $teammates   = $FrontApp->get("teammates");
            if( array_key_exists( "_error", $teammates ) ){ 
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $teammates["_error"]["message"] ] );
            }

            $teammate_id   = "";
            $existing_teammates = [];
            foreach($teammates["_results"] as $teammates_result ){
                array_push( $existing_teammates, $teammates_result["email"] );
                if( strtolower( $teammates_result["email"] ) == strtolower($frontApiAuthorEmail) ){
                    $teammate_id = $teammates_result["id"];
                }
            }

    
            if( !empty( $channel_id ) and  !empty( $teammate_id ) ){



                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api2.frontapp.com/channels/$channel_id/drafts");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $frontApiToken
                ));
                curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/FrontApp/0.1 (github.com/drewm/frontapp)');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($ch, CURLOPT_ENCODING, '');
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, [
                    'author_id'                     => $teammate_id,
                    'body'                          => $bodyDriver,
                    'mode'                          => "shared",
                    'should_add_default_signature'  => false,
                ]);
                
                $response['body']    = curl_exec($ch);
                $response['headers'] = curl_getinfo($ch);
        
                if (isset($response['headers']['request_header'])) {
                    $this->last_request['headers'] = $response['headers']['request_header'];
                }
        
                if ($response['body'] === false) {
                    $this->last_error = curl_error($ch);
                }
        
                curl_close($ch);

                $response["body"]   = json_decode(json_encode(json_decode($response["body"])), true);
                $create_draft       = $response["body"];

                if( array_key_exists( "_error", $create_draft ) ){ 
                    return $this->returnSuccessResponse( [ 
                        'status'    => 'failed', 
                        'message'   => $create_draft["_error"]["message"],
                        'response'  => $create_draft
                    ] );
                }
                
                return $this->returnSuccessResponse( [ 
                    'status'        => 'success', 
                    'message'       => "Draft created in front app!",
                    'channels'      => $channels,
                    'teammates'     => $teammates,
                    'create_draft'  => $create_draft,

                ] );

            }else{
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => "Front channel name is missing or invalid!", 'existing_teammates' => $existing_teammates, 'existing_channels' => $existing_channels ] );
            }
        

        } catch (Throwable $e) {
            return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $e->getMessage() ] );
        }
    }


    /**
     * send-confirmation-from-front
     *
     * @Route("/admin/dashboard/send-confirmation-from-front", name="send_confirmation_from_front")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendConfirmationFromFrontAction(Request $request)
    {
        $em                     = $this->getDoctrine()->getManager();
        $utils                  = $this->get('wicrew.core.utils');
        $frontApiSettings       = $utils->getSystemConfigValues('front/api', true);
        $frontApiSettings       = $frontApiSettings['front']['api'];
        
        $frontApiToken          = $frontApiSettings['token'];
        $frontApiChannelName    = $frontApiSettings['channel']['name'];
        $frontApiAuthorEmail    = $frontApiSettings['author'];

        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));

            if (!$orderItem) {
                return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
            }
            // LOGGING INTO HISTORYLOG
            $historyLog         = new HistoryLog();
            $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
            $historyLog->setUser( $this->get('security.token_storage')->getToken()->getUser() );
            $order_id = $orderItem->getOrder()->getId();
            $historyLog->setModifications("#RJ$order_id - Draft created in frontApp." );
            $em->persist($historyLog);
            $em->flush();
            // LOGGING INTO HISTORYLOG
            $orders                 = [];
            $orders[]               = $orderItem;        
            $order                  = $orderItem->getOrder();
            $customerEmail          = $order->getEmail();

            $subject    = "#RJ$order_id Service Confirmation";
            $template   = 'WicrewSaleBundle:Admin:Email/order.mail.front.confirmation.html.twig';

            $confirmationBody = $this->renderTwigToString($template, [
                'orders'    => $orders,
            ]);
            
            $FrontApp   = new FrontApp($frontApiToken);

            $channels   = $FrontApp->get("channels");
            if( array_key_exists( "_error", $channels ) ){ 
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $channels["_error"]["message"] ] );
            }

            $channel_id   = "";
            $existing_channels = [];
            foreach($channels["_results"] as $channels_result ){
                // echo $channels_result["name"]."\n";
                array_push( $existing_channels, $channels_result["name"] );
                if( strtolower( $channels_result["name"] ) == strtolower( $frontApiChannelName ) ){
                    $channel_id = $channels_result["id"];
                }
            }

            $teammates   = $FrontApp->get("teammates");
            if( array_key_exists( "_error", $teammates ) ){ 
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $teammates["_error"]["message"] ] );
            }

            $teammate_id   = "";
            $existing_teammates = [];
            foreach($teammates["_results"] as $teammates_result ){
                array_push( $existing_teammates, $teammates_result["email"] );
                if( strtolower( $teammates_result["email"] ) == strtolower($frontApiAuthorEmail) ){
                    $teammate_id = $teammates_result["id"];
                }
            }

    
            if( !empty( $channel_id ) and  !empty( $teammate_id ) ){



                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api2.frontapp.com/channels/$channel_id/drafts");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $frontApiToken
                ));
                curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/FrontApp/0.1 (github.com/drewm/frontapp)');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($ch, CURLOPT_ENCODING, '');
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, [
                    'author_id'                     => $teammate_id,
                    'to'                            => [ $customerEmail ],
                    'subject'                       => $subject,
                    'body'                          => $confirmationBody,
                    'mode'                          => "shared",
                    'should_add_default_signature'  => false,
                ]);
                
                $response['body']    = curl_exec($ch);
                $response['headers'] = curl_getinfo($ch);
        
                if (isset($response['headers']['request_header'])) {
                    $this->last_request['headers'] = $response['headers']['request_header'];
                }
        
                if ($response['body'] === false) {
                    $this->last_error = curl_error($ch);
                }
        
                curl_close($ch);

                $response["body"]   = json_decode(json_encode(json_decode($response["body"])), true);
                $create_draft       = $response["body"];

                if( array_key_exists( "_error", $create_draft ) ){ 
                    return $this->returnSuccessResponse( [ 
                        'status'    => 'failed', 
                        'message'   => $create_draft["_error"]["message"],
                        'response'  => $create_draft
                    ] );
                }
                
                return $this->returnSuccessResponse( [ 
                    'status'        => 'success', 
                    'message'       => "Confirmation created in front app!",
                    'channels'      => $channels,
                    'teammates'     => $teammates,
                    'create_draft'  => $create_draft,

                ] );

            }else{
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => "Front channel name is missing or invalid!", 'existing_teammates' => $existing_teammates, 'existing_channels' => $existing_channels ] );
            }
        

        } catch (Throwable $e) {
            return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $e->getMessage() ] );
        }
    }


    /**
     * bulk-send-confirmation-from-front
     *
     * @Route("/admin/dashboard/bulk-send-confirmation-from-front", name="bulk_send_confirmation_from_front")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function bulkSendConfirmationFromFrontAction(Request $request)
    {
        $em                     = $this->getDoctrine()->getManager();
        $utils                  = $this->get('wicrew.core.utils');
        $frontApiSettings       = $utils->getSystemConfigValues('front/api', true);
        $frontApiSettings       = $frontApiSettings['front']['api'];
        
        $frontApiToken          = $frontApiSettings['token'];
        $frontApiChannelName    = $frontApiSettings['channel']['name'];
        $frontApiAuthorEmail    = $frontApiSettings['author'];
        $orderItemIds           = $request->request->get('data');
        $orderItemIds           = json_decode($orderItemIds);
        try {
            $orders                 = [];
            $order_id_toDisplay     = 0;
            $customerEmail          = "";
            foreach($orderItemIds as $orderItem_id){

                $orderItem = $em->getRepository(OrderItem::class)->find( (int)$orderItem_id );

                if (!$orderItem) {
                    return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
                }
                $order                  = $orderItem->getOrder();
                $customerEmail          = $order->getEmail();
                $order_id_toDisplay     = $orderItem->getOrder()->getId();
                $orders[] = $orderItem;        
            }    
            
            $subject    = "#RJ$order_id_toDisplay Service Confirmation";
            $template   = 'WicrewSaleBundle:Admin:Email/order.mail.front.confirmation.html.twig';
            
            
            $confirmationBody = $this->renderTwigToString($template, [
                'orders'    => $orders,
            ]);
            
            $FrontApp   = new FrontApp($frontApiToken);
            $channels   = $FrontApp->get("channels");
            if( array_key_exists( "_error", $channels ) ){ 
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $channels["_error"]["message"] ] );
            }

            $channel_id   = "";
            $existing_channels = [];
            foreach($channels["_results"] as $channels_result ){
                // echo "channel name: ".$channels_result["name"]."\n";
                array_push( $existing_channels, $channels_result["name"] );
                if( strtolower( $channels_result["name"] ) == strtolower( $frontApiChannelName ) ){
                    $channel_id = $channels_result["id"];
                }
            }

            $teammates   = $FrontApp->get("teammates");
            if( array_key_exists( "_error", $teammates ) ){ 
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $teammates["_error"]["message"] ] );
            }

            $teammate_id   = "";
            $existing_teammates = [];
            foreach($teammates["_results"] as $teammates_result ){
                // echo "Author Email: ".$teammates_result["email"]."\n";
                array_push( $existing_teammates, $teammates_result["email"] );
                if( strtolower( $teammates_result["email"] ) == strtolower($frontApiAuthorEmail) ){
                    $teammate_id = $teammates_result["id"];
                }
            }

            if( !empty( $channel_id ) and  !empty( $teammate_id ) ){



                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api2.frontapp.com/channels/$channel_id/drafts");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $frontApiToken
                ));
                curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/FrontApp/0.1 (github.com/drewm/frontapp)');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($ch, CURLOPT_ENCODING, '');
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, [
                    'author_id'                     => $teammate_id,
                    'to'                            => [ $customerEmail ],
                    'subject'                       => $subject,
                    'body'                          => $confirmationBody,
                    'mode'                          => "shared",
                    'should_add_default_signature'  => false,
                ]);
                
                $response['body']    = curl_exec($ch);
                $response['headers'] = curl_getinfo($ch);
        
                if (isset($response['headers']['request_header'])) {
                    $this->last_request['headers'] = $response['headers']['request_header'];
                }
        
                if ($response['body'] === false) {
                    $this->last_error = curl_error($ch);
                }
        
                curl_close($ch);

                $response["body"]   = json_decode(json_encode(json_decode($response["body"])), true);
                $create_draft       = $response["body"];

                if( array_key_exists( "_error", $create_draft ) ){ 
                    return $this->returnSuccessResponse( [ 
                        'status'    => 'failed', 
                        'message'   => $create_draft["_error"]["message"],
                        'response'  => $create_draft
                    ] );
                }
                
                return $this->returnSuccessResponse( [ 
                    'status'        => 'success', 
                    'message'       => "Confirmation created in front app!",
                    'channels'      => $channels,
                    'teammates'     => $teammates,
                    'create_draft'  => $create_draft,

                ] );

                // LOGGING INTO HISTORYLOG
                $historyLog         = new HistoryLog();
                $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                
                $historyLog->setCreatedAt( $currentDateTime );
                $historyLog->setUser( $this->get('security.token_storage')->getToken()->getUser() );
                $historyLog->setModifications("Bulk draft created in frontApp." );
                $em->persist($historyLog);
                $em->flush();
                // LOGGING INTO HISTORYLOG

            }else{
                return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => "Front channel name is missing or invalid!", 'existing_teammates' => $existing_teammates, 'existing_channels' => $existing_channels ] );
            }
            

        } catch (Throwable $e) {
            return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $e->getMessage() ] );
        }
    }
    
   /**
     * bulk-send-email-to-driver-from-front
     *
     * @Route("/admin/dashboard/bulk-send-email-from-front", name="bulk_send_email_from_front")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function bulkSendEmailFromFrontAction(Request $request)
    {
        $em                     = $this->getDoctrine()->getManager();
        $utils                  = $this->get('wicrew.core.utils');
        $frontApiSettings       = $utils->getSystemConfigValues('front/api', true);
        $frontApiSettings       = $frontApiSettings['front']['api'];
        
        $frontApiToken          = $frontApiSettings['token'];
        $frontApiChannelName    = $frontApiSettings['channel']['name'];
        $frontApiAuthorEmail    = $frontApiSettings['author'];
        $orderItemIds           = $request->request->get('data');
        $orderItemIds           = json_decode($orderItemIds);
        try {
            $orders                 = [];
            $assignments_to_send    = [];
            foreach($orderItemIds as $orderItem_id){
                
                $orderItem = $em->getRepository(OrderItem::class)->find( (int)$orderItem_id );

                if (!$orderItem) {
                    return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
                }

                $orderItemType      = $orderItem->getType();

                if($orderItemType == "activity_regular" || $orderItemType == "activity_regular"){
                    $orderItemType = "Activity";

                }else if( $orderItemType == "private_shuttle" ){
                    $orderItemType = "Private shuttle";
                    
                }else if( $orderItemType == "shared_shuttle" ){
                    $orderItemType = "Shared shuttle";

                }else if( $orderItemType == "private_flight" ){
                    $orderItemType = "Private flight";

                }else if( $orderItemType == "private_jbj" ){
                    $orderItemType = "Private JBJ";

                }else if( $orderItemType == "shared_jbj" ){
                    $orderItemType = "Shared JBJ";

                }else if( $orderItemType == "riding_jbj" ){
                    $orderItemType = "Riding JBJ";

                }else if( $orderItemType == "water_taxi" ){
                    $orderItemType = "Water taxi";

                }

                $orders[] = $orderItem;
                
            }

            
            // if ($orderItem->getSupplier()) {
        
        
                // if ($orderItemType == 'Activity') {
                //     $template   = 'WicrewSaleBundle:Admin:Email/order.mail.front.assigndriver.html.twig';
                // } else {
                $template   = 'WicrewSaleBundle:Admin:Email/order.mail.front.assignsupplier.html.twig';
                // }
                $bodyDriver = $this->renderTwigToString($template, [
                    'assignments'   => $assignments_to_send,
                    'orders'        => $orders,
                    'driver'        => false,
                    'toAdmin'       => true
                ]);
                
                $FrontApp   = new FrontApp($frontApiToken);

                $channels   = $FrontApp->get("channels");
                if( array_key_exists( "_error", $channels ) ){ 
                    return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $channels["_error"]["message"] ] );
                }

                $channel_id   = "";
                foreach($channels["_results"] as $channels_result ){
                    // echo $channels_result["name"]."\n";
                    if( strtolower( $channels_result["name"] ) == strtolower( $frontApiChannelName ) ){
                        $channel_id = $channels_result["id"];
                    }
                }

                $teammates   = $FrontApp->get("teammates");
                if( array_key_exists( "_error", $teammates ) ){ 
                    return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $teammates["_error"]["message"] ] );
                }

                $teammate_id   = "";
                foreach($teammates["_results"] as $teammates_result ){
                    // echo $teammates_result["email"]."\n";
                    if( strtolower( $teammates_result["email"] ) == strtolower($frontApiAuthorEmail) ){
                        $teammate_id = $teammates_result["id"];
                    }
                }

        
                if( !empty( $channel_id ) and  !empty( $teammate_id ) ){



                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://api2.frontapp.com/channels/$channel_id/drafts");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $frontApiToken
                    ));
                    curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/FrontApp/0.1 (github.com/drewm/frontapp)');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                    curl_setopt($ch, CURLOPT_ENCODING, '');
                    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            
                    curl_setopt($ch, CURLOPT_POST, true);
                    $this->attachRequestPayload($ch, [
                        'author_id'                     => $teammate_id,
                        'body'                          => $bodyDriver,
                        'mode'                          => "shared",
                        'should_add_default_signature'  => false,
                    ]);
                    
                    $response['body']    = curl_exec($ch);
                    $response['headers'] = curl_getinfo($ch);
            
                    if (isset($response['headers']['request_header'])) {
                        $this->last_request['headers'] = $response['headers']['request_header'];
                    }
            
                    if ($response['body'] === false) {
                        $this->last_error = curl_error($ch);
                    }
            
                    curl_close($ch);

                    $response["body"]   = json_decode(json_encode(json_decode($response["body"])), true);
                    $create_draft       = $response["body"];

                    if( array_key_exists( "_error", $create_draft ) ){ 
                        return $this->returnSuccessResponse( [ 
                            'status'    => 'failed', 
                            'message'   => $create_draft["_error"]["message"],
                            'response'  => $create_draft
                        ] );
                    }
                    
                    return $this->returnSuccessResponse( [ 
                        'status'        => 'success', 
                        'message'       => "Draft created in front app!",
                        'channels'      => $channels,
                        'teammates'     => $teammates,
                        'create_draft'  => $create_draft,

                    ] );

                }else{
                    return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => "Front channel name is missing or invalid!" ] );
                }
            
            // }else{
            //     return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => "Supplier must be assigned!" ] );
            // }

        } catch (Throwable $e) {
            return $this->returnSuccessResponse( [ 'status' => 'failed', 'message' => $e->getMessage() ] );
        }
    }

    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->last_request['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    /**    
     * Export the report
     */
    public function exportAction()
    {
 

        $toUseOrderSupplierId;
        $supplierFilterApplied = false;
        // LOGGING INTO HISTORYLOG
        $em = $this->getDoctrine()->getManager();
        $em->beginTransaction();
        $historyLog = new HistoryLog();
        $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
        $historyLog->setCreatedAt( $currentDateTime );
        $historyLog->setUser( $this->get('security.token_storage')->getToken()->getUser() );
        $historyLog->setModifications("Excel sheet exported." );
        $em->persist($historyLog);
        $em->flush();
        $em->commit();
        // LOGGING INTO HISTORYLOG
        $spreadsheet    = new Spreadsheet();
        $sheet          = $spreadsheet->getActiveSheet();
        $rowToInsert    = 1;

        $translator     = $this->container->get('translator');
        $twig           = $this->container->get('twig');
        $twigExtension  = $this->container->get('twig')->getExtension(\EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension::class);

        // Add the header of the CSV file
        $fields = $this->entity['list']['fields'];
        $fields["service"] = array("label" => "Service");
        
        $headers = array(
            "Billing status",
            "Service Type",
            "Booking",
            "Date",
            "Time",
            // "Arrival Time",
            // "Departure Time",
            "Pax/Qty",
            "Pickup Place",
            // "From",
            "Drop-off Place",
            // "To",
            "Supplier",
            "Type",
            "Vehicle",
            "Confirmation status",
            "Client",
            "Airline",
            "Flight",
            "Commission (%)",
            "Addons & Extras Services",
            "Rack(A)",
            "Rack(C)",
            "Taxes",
            "Rack + Tax",
            "Net ",
            "Net + Tax",
        );
        $sheet->fromArray($headers, NULL, 'A' . $rowToInsert);
        $rowToInsert++;

        $_multiColumn   = $this->request->query->get('multiColumn', null);
        $_selectedData  = json_decode($this->request->query->get('selectedData', null));
        $_selectedData  = array_map(function($val) { return explode(',', $val); }, (array)$_selectedData); // exploding array values on comma (,).
        
        
        $_selectedColumn = $this->request->query->get('selectedColumn', null);
        $_query          = $this->request->query->get('query', null);


        $_filters          = $this->request->query->get('filters', null);
        $_sortField        = $this->request->query->get('sortField', null);
        $_sortDirection    = $this->request->query->get('sortDirection', null);
        $_action           = $this->request->query->get('action', null);
        


        if( $_multiColumn ){
            $findByArray = array_merge([ 
                'archiveStatus'    => [null, 0],
                'status'            => [0, 1],
                
            ], $_selectedData);
            $allOrderItems  = $this->em->getRepository(OrderItem::class)->findBy( $findByArray );
          
        }else if( $_selectedColumn ){
            $findByArray = array_merge([ 
                'archiveStatus'    => [null, 0],
                'status'            => [0, 1],
                
            ], [$_selectedColumn => $_query]);
            $allOrderItems  = $this->em->getRepository(OrderItem::class)->findBy( $findByArray );
          
        }else if( $_filters ){
            $queryBuilder = $em->createQueryBuilder()->select('entity')->from(OrderItem::class, 'entity');
            foreach($_filters as $_toFilterField => $_filter_array){
                if($_toFilterField == 'order_supplier_id'){
                    $order_supplier_id = $_filter_array;
                    $queryBuilder->leftJoin('entity.supplier', 'supplier'); // join the 'user' entity
                    
                    // ALSO ADDIND THOSE ITEMS THOSE HAVE SUPPLIER EVEN IN ADDITIONAL DRIVERS  
                    $queryBuilder->leftJoin('App\Wicrew\SaleBundle\Entity\OrderItemHasDriver', 'OrderItemHasDriver', 'WITH',  'OrderItemHasDriver.orderItem = entity.id'); 
                    
                    $queryBuilder->andWhere(
                        '( supplier.id  = ' . $order_supplier_id . ' OR OrderItemHasDriver.driver  = ' . $order_supplier_id . ')'
                    );
                    $toUseOrderSupplierId = $order_supplier_id;
                    $supplierFilterApplied = true;

                }else if($_toFilterField == 'order_vehicle_id'){
                    $order_vehicle_id = $_filter_array;
                    $queryBuilder->leftJoin('entity.vehicle', 'vehicle') // join the 'user' entity
                    ->andWhere( $queryBuilder->expr()->eq('vehicle.id', $order_vehicle_id) ); // search for the query in the 'id' field of the 'vehicle' entity
                }else{
                    $_filter_value      = $_filter_array["value"];
                    $_filter_comparison = $_filter_array["comparison"];

                    if( strtolower($_filter_comparison) == 'between' ){
                        $_filter_value2      = $_filter_array["value2"];
                        $queryBuilder->andWhere("entity.".$_toFilterField.' '.$_filter_comparison." '".$_filter_value."' AND '".$_filter_value2."'");
                    }else{
                        $queryBuilder->andWhere("entity.".$_toFilterField.' '.$_filter_comparison." '".$_filter_value."'");
                    }
                }
            }
            
            $queryBuilder->andWhere('entity.status != 2 AND entity.archiveStatus != 1');
            if ( $_filters && array_key_exists('pickDate', $_filters) ){
                $queryBuilder->orderBy('entity.pickDate', 'ASC');    
            }else{
                $queryBuilder->orderBy('entity.'.$_sortField, $_sortDirection);
            }
            
            // $queryBuilder->addOrderBy('entity.pickTime', 'ASC');
            $queryBuilder->addSelect('CASE WHEN(entity.pickTimeTransport IS NULL) THEN entity.pickTime ELSE entity.pickTimeTransport END AS pickTime_dl');
            $queryBuilder->addOrderBy('pickTime_dl', 'ASC');

            
            $allOrderItems = array_map(function ($contributor) {
                return $contributor[0];
            }, $queryBuilder->getQuery()->getResult());

        }else if ( $_query ){
            $_query = str_ireplace("#", "", $_query);
            $_query = str_ireplace("rj", "", $_query);
            $queryBuilder = $em->createQueryBuilder()->select('e')->from(OrderItem::class, 'e');

            // Get all the columns of the entity
            $columns = $em->getClassMetadata(OrderItem::class)->getReflectionProperties();

            $orX = $queryBuilder->expr()->orX();

            // Dynamically create an 'or' query for each column in the entity
            foreach ($columns as $column) {
                $columnName = $column->getName();
                if( $columnName == "customServices" || $columnName == "order" || $columnName == "product" || $columnName == "activity" || $columnName == "comboChildren" || $columnName == "addons" || $columnName == "extras" || $columnName == "pickArea" || $columnName == "dropArea" || $columnName == "supplier" || $columnName == "vehicle" || $columnName == "additionalDrivers" || $columnName == "history" || $columnName == "dlOrder" ){
                    continue;
                }
                // echo "columnName: $columnName<br>";
                $orX->add($queryBuilder->expr()->like('e.'.$columnName, ':query'));
            }
            
            $queryBuilder->andWhere($orX)->setParameter('query', '%' . $_query . '%');

            $queryBuilder->andWhere('e.status != 2 AND e.archiveStatus != 1');
            $queryBuilder->orderBy('e.'.$_sortField, $_sortDirection);
            
            $sql = $queryBuilder->getQuery()->getSQL();
        
            $allOrderItems = $queryBuilder->getQuery()->getResult();
        }else{

            $allOrderItems  = $this->em->getRepository(OrderItem::class)->findBy(
                [ 
                    'archiveStatus'    => [null, 0],
                    'status'            => [0, 1],
     
                ]
            );
        }


        $isDiscountAlreadyAdded = [];
        /* @var OrderItem $item */
        foreach ($allOrderItems as $item) {
            // echo "<pre>".print_r($item, true)."</pre>item id: ".$item->getId();
            
            $addons = [];
            if ($item->getAddons()->count() > 0) {
                /* @var OrderItemHasAddon $addon */

                foreach ($item->getAddons() as $addon) {
                    $_temp_arr                          = [];
                    $_temp_arr["type"]          = "Addons";
                    $_temp_arr["label"]         = $addon->getLabel();
                    $_temp_arr["supplier"]      = $addon->getSupplier();
                    if($addon->getSupplier()){
                        $_temp_arr["supplier"]      = $addon->getSupplier()->getBizName();
                    }
                    $_temp_arr["pax_qty"]       = $addon->getAdultQuantity() + $addon->getChildQuantity();
                    $_temp_arr["rack_a_rate"]   = ( $addon->getAdultRackPrice() + $addon->getExtraTransportation() );
                    $_temp_arr["rack_c_rate"]   = $addon->getChildRackPrice();
                    $_temp_arr["net_a_rate"]    = $addon->getAdultNetPrice();
                    $_temp_arr["net_c_rate"]    = $addon->getChildNetPrice();
                    $_temp_arr["tax"]           = $addon->getTax();
                    $_temp_arr["total_rack"]    = ($_temp_arr["rack_a_rate"] + $_temp_arr["rack_c_rate"] + $_temp_arr["tax"]);
                    $_temp_arr["total_net"]     = ( ( 0.13 * $_temp_arr["net_a_rate"]) + $_temp_arr["net_a_rate"] ) + ( ( 0.13 * $_temp_arr["net_c_rate"]) + $_temp_arr["net_c_rate"] );
                    $_temp_arr["total_rack"]    = number_format((float)$_temp_arr["total_rack"], 2, '.', '');
                    $_temp_arr["total_net"]     = number_format((float)$_temp_arr["total_net"], 2, '.', '');
                    if( $supplierFilterApplied ){
                        if( $addon->getSupplier() == null || $toUseOrderSupplierId != $addon->getSupplier()->getId() ){
                            continue;
                        }
                    } 
                    // echo "<pre>".print_r($_temp_arr, true)."</pre>";
                    array_push($addons, $_temp_arr);
                }
            }
            $extras = [];
            if ($item->getExtras()->count() > 0) {
                /* @var OrderItemHasAddon $addon */

                foreach ($item->getExtras() as $extra) {
                    $_temp_arr                  = [];
                    $_temp_arr["type"]          = "Extra";
                    $_temp_arr["label"]         = $extra->getLabel();
                    $_temp_arr["supplier"]      = $extra->getSupplier();
                    if($extra->getSupplier()){
                        $_temp_arr["supplier"]      = $extra->getSupplier()->getBizName();
                    }
                    $_temp_arr["pax_qty"]       = $extra->getQuantity();
                    $_temp_arr["rack_a_rate"]   = $extra->getRackPrice();
                    $_temp_arr["rack_c_rate"]   = "-";
                    $_temp_arr["net_a_rate"]    = $extra->getNetPrice();
                    $_temp_arr["net_a_rate"]    = $extra->getNetPrice();
                    $_temp_arr["net_c_rate"]    = "-";
                    $_temp_arr["tax"]           = $extra->getTax();
                    $_temp_arr["total_rack"]    = ($_temp_arr["rack_a_rate"] + $_temp_arr["tax"]);
                    $_temp_arr["total_net"]     = ( ( 0.13 * $_temp_arr["net_a_rate"]) + $_temp_arr["net_a_rate"] );
                    
                    $_temp_arr["total_rack"]    = number_format((float)$_temp_arr["total_rack"], 2, '.', '');
                    $_temp_arr["total_net"]     = number_format((float)$_temp_arr["total_net"], 2, '.', '');
                    
                    if( $supplierFilterApplied ){
                        if( $extra->getSupplier() == null || $toUseOrderSupplierId != $extra->getSupplier()->getId() ){
                            continue;
                        }
                    } 
                    // echo "<pre>".print_r($_temp_arr, true)."</pre>";
                    array_push($extras, $_temp_arr);
                }
            }


            $custom_services = [];
            if ($item->getCustomServices()->count() > 0) {
                foreach ($item->getCustomServices() as $customService) {

                        $_temp_arr                  = [];
                        $_temp_arr["type"]          = "Custom Service";
                        $_temp_arr["label"]         = $customService->getLabel();
                        $_temp_arr["supplier"]      = "";
                        $_temp_arr["pax_qty"]       = "";
                        $_temp_arr["rack_a_rate"]   = $customService->getRackPrice();
                        $_temp_arr["rack_c_rate"]   = "";
                        $_temp_arr["net_a_rate"]    = "";
                        $_temp_arr["net_c_rate"]    = "";
                        $_temp_arr["tax"]           = ( $customService->getRackPrice() * 0.13 );
                        $_temp_arr["total_rack"]    = ($_temp_arr["rack_a_rate"] + $_temp_arr["tax"]);
                        $_temp_arr["total_net"]     = "";
                        $_temp_arr["total_rack"]    = number_format((float)$_temp_arr["total_rack"], 2, '.', '');
                        array_push($custom_services, $_temp_arr);
                    
                }
            }

            $discounts_data = [];
            if ($item->getOrder()->getDiscountValues() > 0 && !in_array( $item->getOrder()->getId(), $isDiscountAlreadyAdded ) ) {
                foreach ($item->getOrder()->getDiscountValues() as $discount) {

                        $_temp_arr                  = [];
                        $_temp_arr["type"]          = "Discount";
                        $_temp_arr["label"]         = $discount["discount"]->getName();
                        $_temp_arr["supplier"]      = "";
                        $_temp_arr["pax_qty"]       = "";
                        $_temp_arr["rack_a_rate"]   = "";
                        $_temp_arr["rack_c_rate"]   = "";
                        $_temp_arr["net_a_rate"]    = "";
                        $_temp_arr["net_c_rate"]    = "";
                        $_temp_arr["tax"]           = "";
                        $_temp_arr["total_rack"]    = "-".$discount["discountRack"];
                        $_temp_arr["total_net"]     = "";
                        array_push($discounts_data, $_temp_arr);
                        array_push($isDiscountAlreadyAdded, $item->getOrder()->getId());
                }
            }

            $time_ranges_fee = [];
            if ( $item->anyTimeRangeFees() ) {
                if( !is_null( $item->getRegularTimeFeeRack() ) ){
                    array_push( $time_ranges_fee, array(
                        "type"         => "Time Range Fee",
                        "label"        => 'Late Night Surcharge',
                        "supplier"     => "",
                        "pax_qty"      => "", 
                        "rack_a_rate"  => $item->getRegularTimeFeeRack(), 
                        "rack_c_rate"  => "",
                        "net_a_rate"   => "", 
                        "net_c_rate"   => "", 
                        "tax"          => ( $item->getRegularTimeFeeRack() * 0.13 ), 
                        "total_rack"   => ($item->getRegularTimeFeeRack() + ( $item->getRegularTimeFeeRack() * 0.13 )), 
                        "total_net"    => "",
                        "total_rack"   => number_format((float)($item->getRegularTimeFeeRack() + ( $item->getRegularTimeFeeRack() * 0.13 )), 2, '.', ''), 
                    ));
                }       
                if( !is_null( $item->getFlightPickTimeFeeRack() ) ){
                    array_push( $time_ranges_fee, array(
                        "type"         => "Time Range Fee",
                        "label"        => 'Late Night Surcharge',
                        "supplier"     => "",
                        "pax_qty"      => "", 
                        "rack_a_rate"  => $item->getFlightPickTimeFeeRack(), 
                        "rack_c_rate"  => "",
                        "net_a_rate"   => "", 
                        "net_c_rate"   => "", 
                        "tax"          => ( $item->getFlightPickTimeFeeRack() * 0.13 ), 
                        "total_rack"   => ($item->getFlightPickTimeFeeRack() + ( $item->getFlightPickTimeFeeRack() * 0.13 )), 
                        "total_net"    => "",
                        "total_rack"   => number_format((float)($item->getFlightPickTimeFeeRack() + ( $item->getFlightPickTimeFeeRack() * 0.13 )), 2, '.', ''), 
                    ));
                }       
                if( !is_null( $item->getFlightDropTimeFeeRack() ) ){
                    array_push( $time_ranges_fee, array(
                        "type"         => "Time Range Fee",
                        "label"        => 'Late Night Surcharge',
                        "supplier"     => "",
                        "pax_qty"      => "", 
                        "rack_a_rate"  => $item->getFlightDropTimeFeeRack(), 
                        "rack_c_rate"  => "",
                        "net_a_rate"   => "", 
                        "net_c_rate"   => "", 
                        "tax"          => ( $item->getFlightDropTimeFeeRack() * 0.13 ), 
                        "total_rack"   => ( $item->getFlightDropTimeFeeRack() + ( $item->getFlightDropTimeFeeRack() * 0.13 ) ), 
                        "total_net"    => "",
                        "total_rack"   => number_format((float)( $item->getFlightDropTimeFeeRack() + ( $item->getFlightDropTimeFeeRack() * 0.13 ) ), 2, '.', ''), 
                    ));
                }           
            }

            $_additional_drivers = [];
            if( count( $item->getAdditionalDrivers( ) ) > 0 ){
                foreach( $item->getAdditionalDrivers( ) as $driver ){
                    
                    $_temp_arr = array();

                    $_add_pick_date = $driver->getPickDate();
                    if($_add_pick_date == null )
                        $_add_pick_date = "Null";
                    else
                        $_add_pick_date = $_add_pick_date->format('Y,m,d');
                    
                    $_temp_arr["pickup_date"]           = $_add_pick_date;
                    $_add_pick_time = $driver->getPickTime();
                    if($_add_pick_time == null )
                        $_add_pick_time = "Null";
                    else
                        $_add_pick_time = $_add_pick_time->format('H,i,s');
                    $_temp_arr["pickup_time"]           = $_add_pick_time;

                    $_temp_arr["pickup_location"]       = $driver->getFromDescription();
                    $_temp_arr["dropoff_location"]      = $driver->getToDescription();
                    $_temp_arr["confirmation_status"]   = $driver->getConfirmationStatus();
                    $_temp_arr["supplier"]              = $driver->getDriver();
                    $_temp_arr["vehicle"]               = $driver->getVehicle();
                    $_temp_arr["net_rate"]              = $driver->getNet();
                    $_temp_arr["total_net"]             = ( ( 0.13 * $_temp_arr["net_rate"]) + $_temp_arr["net_rate"] );
                    
                    $_temp_arr["net_rate"]              = number_format($_temp_arr["net_rate"], 2, '.', '');
                    $_temp_arr["total_net"]             = number_format($_temp_arr["total_net"], 2, '.', '');
                    if ( $driver->getDriver() == null && $driver->getToDescription() == "" && $driver->getToDescription() == "" && $driver->getVehicle() == null ) {
                        continue;
                    }

                    if( $supplierFilterApplied ){
                        if( $toUseOrderSupplierId != $driver->getDriver()->getId() ){
                            continue;
                        }
                    } 
                    array_push($_additional_drivers, $_temp_arr);
                }
            }

            $_item_extra_rows   = count($discounts_data) + count($addons) + count($extras) + count($custom_services) + count($time_ranges_fee);
            $_extras_addons     = array_merge($discounts_data, $custom_services, $addons, $extras, $time_ranges_fee);
            $data = array(
                "billing_status"            => "",
                "type"                      => "",
                "booking"                   => "",
                "pickup_date"               => "",
                "pickTimeTransport"         => "",
                "arrival_time"              => "",
                "departure_time"            => "",
                "pax_qty"                   => "",
                "pickup_area"               => "",
                // "pickup_location"           => "",
                "dropoff_area"              => "",
                // "dropoff_location"          => "",
                "supplier"                  => "",
                "vehicle_type"              => "",
                "vehicle"                   => "",
                "confirmation_status"       => "",
                "passenger_name"            => "",
                "airline"                   => "",
                "flight"                    => "",
                "commission"                => "",
                "addons_extras_services"    => "",
                "rack_a_rate"               => "",
                "rack_c_rate"               => "",
                "tax"                       => "",
                "total_rack"                => "",
                "net_rate"                  => "",
                "total_net"                 => "",
                
            );
            
            unset($fields["editLink"]);
            // echo "<pre>".print_r(array_keys($fields), true)."</pre>"; exit;
            foreach ($fields as $field => $metaData) {
                // echo "$field<br>";
                if ($field == "supplier.id") {

                    $displayValue = "";
                    if ($item->getSupplier()) {
                        $value = $item->getSupplier();
                        $displayValue = $value;
                    }
                    $data["supplier"] = trim($displayValue);

                }else if ($field == "product.vehicle.type") {

                    $displayValue = "--";
                    if ($item->getProduct()) {
                        $value = $item->getProduct()->getVehicleType();
                        $displayValue = $value;
                    }
                    $data["vehicle_type"] = trim($displayValue);

                } else if ($field == "vehicle.vehicleType.id") {

                    $displayValue = "--";
                    if ($item->getProduct()) {
                        $value = $item->getProduct()->getVehicleType();
                        $displayValue = $value;
                    }
                    $data["vehicle_type"] = trim($displayValue);

                }else if ($field == "vehicle.id") {

                    $displayValue = "";
                    if ($item->getVehicle()) {
                        $value = $item->getVehicle();
                        $displayValue = $value;
                    }
                    $data["vehicle"] = trim($displayValue);

                } else if ($field == "type") {

                    $displayValue = "";
                    if ($item->getType()) {
                        $value = $item->getType();
                        $displayValue = $value;
                    }

                    if( trim( $displayValue ) == "private_shuttle" ){
                        $displayValue = "Private Shuttle";
                    }else if( trim( $displayValue ) == "activity_regular" ){
                        $displayValue = "Activity (".$item->getActivity()->getName().")";

                    }else if( trim( $displayValue ) == "water_taxi" ){
                        $displayValue = "Water Taxi";
                    }else if( trim( $displayValue ) == "private_jbj" ){
                        $displayValue = "Jeep-Boat-Jeep (Private)";
                    }else if( trim( $displayValue ) == "shared_jbj" ){
                        $displayValue = "Jeep-Boat-Jeep (Shared)";
                    }else if( trim( $displayValue ) == "private_flight" ){
                        $displayValue = "Private Flight";
                    }
                    $data["type"] = $displayValue;

                }else if ($field == "pickDate") {

                    $displayValue = "-";
                    if ($item->getPickDate()){

                        if ($item->getPickDate()->format('d/m/Y')) {
                            $value = $item->getPickDate()->format('Y,m,d');
                            $displayValue = $value;
                        }
                    }
                    $data["pickup_date"] = trim($displayValue);
                    
                } else if ($field == "pickTimeTransport") { // PICK-UP TIME

                    $displayValue = "-";
                    if( $item->getPickArea()->getType() != 1 and $item->getDropArea()->getType() != 1 ){
                        if($item->getPickTime()){
                            if ($item->getPickTime()->format('H,i,s')) {
                                $value = $item->getPickTime()->format('H,i,s');
                                $displayValue = $value;
                            }
                        }
                    }else{
                        if($item->getPickTimeTransport()){
                            if ($item->getPickTimeTransport()->format('H,i,s')) {
                                $value = $item->getPickTimeTransport()->format('H,i,s');
                                $displayValue = $value;
                            }
                        }
                    }
                    
                    $data["pickTimeTransport"] =  $displayValue;

                } else if ($field == "pickTime") { //ARRIVAL
                    
                    $displayValue = "-";
                    if( $item->getPickArea()->getType() == 1 ){
                        if($item->getPickTime()){
                            if ($item->getPickTime()->format('H,i,s')) {
                                $value = $item->getPickTime()->format('H,i,s');
                                $displayValue = $value;
                            }
                        }
                    }
                    $data["arrival_time"] = trim($displayValue);

                } else if (strpos($field, 'easyadmin_form_design_element') !== false) { // DEPARTURE TIME
                    
                    
                    $displayValue = "-";
                    if( $item->getDropArea()->getType() == 1 and $item->getPickArea()->getType() != 1 ){
                        if($item->getPickTime()){
                            if ($item->getPickTime()->format('H,i,s')) {
                                $value = $item->getPickTime()->format('H,i,s');
                                $displayValue = $value;
                            }
                        }
                    }
                    $data["departure_time"] = trim($displayValue);

                }
                else if ($field == 'passengerName') {
                    $passenger_name = $item->getPassengerName();
                    if( empty($passenger_name) OR is_null($passenger_name) ){
                        $passenger_name = $item->getOrder()->getFullName();
                    }
                    $data["passenger_name"] = $passenger_name;

                } else if ($field == 'passengerQty') {
                    $adultCount =  $item->getAdultCount();
                    if( empty($adultCount) OR is_null($adultCount) ){
                        $adultCount = 0;
                    }
                    $childCount =  $item->getChildCount();
                    if( empty($childCount) OR is_null($childCount) ){
                        $childCount = 0;
                    }
                    $data["pax_qty"] = $adultCount + $childCount;

                } else if ($field == 'booking') {

                    $data["booking"] = trim( strip_tags($twigExtension->renderEntityField(
                        $twig,  
                        'list', 
                        $this->entity['name'], 
                        $item, 
                        $metaData
                    )));
                } else if ($field == 'confirmationStatus') {

                    $data["confirmation_status"] = trim( strip_tags($twigExtension->renderEntityField(
                        $twig,  
                        'list', 
                        $this->entity['name'], 
                        $item, 
                        $metaData
                    )));

                } else if ($field == 'status') {

                    $data["billing_status"] = trim( strip_tags($twigExtension->renderEntityField(
                        $twig,  
                        'list', 
                        $this->entity['name'], 
                        $item, 
                        $metaData
                    )));

                } else if ($field == 'dropAddress') {

                    // $data["dropoff_location"] = $item->getDropAddress();
                } else if ($field == 'dropArea.name') {
                    
                    $data["dropoff_area"] = trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    ) . " " . $item->getDropAddress(). " " . $item->getDropAirlineCompany(). " " . $item->getDropFlightNumber();

                } else if ($field == 'pickAddress') {

                    // $data["pickup_location"] = $item->getPickAddress();
                    
                } else if ($field == 'pickArea.name') {

                    $data["pickup_area"] = trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    ) . " " . $item->getPickAddress(). " " . $item->getPickAirlineCompany(). " " . $item->getPickFlightNumber();

                }  else if ($field == 'pickFlightNumber') {

                    $data["flight"] = $item->getPickAirlineCompany();

                } else if ($field == 'pickAirlineCompany') {

                    $data["airline"] = $item->getPickFlightNumber();

                } else if ($field == 'supplierCommission') {
                    if ($item->getSupplier()) {
                        $value = $item->getSupplier()->getCommission();
                        $displayValue = $value . '%';
                    } else {
                        $value = 0;
                        $displayValue = 'Null';
                    }
                    $data["commission"] = trim($displayValue);

                } else if ($field == 'adultRackPrice') {

                    $data["rack_a_rate"] = trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    );

                } else if ($field == 'childRackPrice') {

                    $data["rack_c_rate"] = trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    );

                } else if ($field == 'titleNetPrice') {

                    $data["net_rate"] = "$".trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    );

                }
                else if ($field == 'totalTax') {

                    $data["tax"] = "$".trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    );

                } else if ($field == 'totalRackPrice') {

                    $data["total_rack"] = trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    );

                } else if ($field == 'totalNetPrice') {

                    $data["total_net"] = trim(
                        strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                    );

                }
            }
            if( $data['arrival_time'] != '-' ){
                $data['pickTimeTransport'] = $data['arrival_time'];
            }
            unset($data['arrival_time']);
            unset($data['departure_time']);

            $sheet->fromArray($data, NULL, 'A' . $rowToInsert);
            $rowToInsert++;
            if ($_item_extra_rows > 0) {

                for ($x = 0; $x < $_item_extra_rows; $x++) {
                    $data = array(
                        "billing_status"            => "",
                        "type"                      => "",
                        "booking"                   => "",
                        "pickup_date"               => "",
                        "pickTimeTransport"         => "",
                        // "arrival_time"              => "",
                        // "departure_time"            => "",
                        "pax_qty"                   => "",
                        "pickup_area"               => "",
                        // "pickup_location"           => "",
                        "dropoff_area"              => "",
                        // "dropoff_location"          => "",
                        "supplier"                  => "",
                        "vehicle_type"              => "",
                        "vehicle"                   => "",
                        "confirmation_status"       => "",
                        "passenger_name"            => "",
                        "airline"                   => "",
                        "flight"                    => "",
                        "commission"                => "",
                        "addons_extras_services"    => "",
                        "rack_a_rate"               => "",
                        "rack_c_rate"               => "",
                        "tax"                       => "",
                        "total_rack"                => "",
                        "net_rate"                  => "",
                        "total_net"                 => "",
                        
                    );

                    foreach ($fields as $field => $metaData) {
                        

                        if ($field == "supplier.id") {

                            $data["supplier"] = $_extras_addons[$x]["supplier"];

                        }else if ($field == "vehicle.vehicleType.id") {

                            $displayValue = "--";
                            if ($item->getVehicle()) {
                                $value = $item->getVehicle()->getVehicleType();
                                $displayValue = $value;
                            }
                            $data["vehicle_type"] = trim($displayValue);
    
                        }else if ($field == "vehicle.id") {

                            $displayValue = "";
                            if ($item->getVehicle()) {
                                $value = $item->getVehicle();
                                $displayValue = $value;
                            }
                            $data["vehicle"] = trim($displayValue);
    
                        } else if ($field == "type") {
    
                            $data["type"] = $_extras_addons[$x]["type"];
    
                        }else if ($field == "pickDate") {
    
                            $displayValue = "-";
                            if ($item->getPickDate()){
    
                                if ($item->getPickDate()->format('d/m/Y')) {
                                    $value = $item->getPickDate()->format('Y,m,d');
                                    $displayValue = $value;
                                }
                            }
                            $data["pickup_date"] = trim($displayValue);
                            
                        }  else if ($field == "pickTimeTransport") {

                            $displayValue = "-";
                            $data["pickTimeTransport"] = trim($displayValue);
    
                        } else if ($field == "pickTime") {
                            
                            // $data["arrival_time"] = " ";
    
                        } else if ($field == "_easyadmin_form_design_element_4") {
                            
                            // $data["departure_time"] = "-";
    
                        }  else if ($field == 'passengerName') {
    
                            $data["passenger_name"] = "-";

                        }  else if ($field == 'passengerQty') {
                            $pax_qty =  $_extras_addons[$x]["pax_qty"];
                            $data["pax_qty"] = $pax_qty;
    
                        }else if ($field == 'booking') {
    
                            $data["booking"] = trim( strip_tags($twigExtension->renderEntityField(
                                $twig,  
                                'list', 
                                $this->entity['name'], 
                                $item, 
                                $metaData
                            )));
                        } else if ($field == 'confirmationStatus') {
    
                            $data["confirmation_status"] = "-";
    
                        } else if ($field == 'status') {
    
                            $data["billing_status"] = "-";
                            
                        } else if ($field == 'dropAddress') {
    
                            // $data["dropoff_location"] = "-";
                            
                        } else if ($field == 'dropArea.name') {
    
                            $data["dropoff_area"] = "-";
                            
                        } else if ($field == 'pickAddress') {
    
                            // $data["pickup_location"] = "-";
                            
                        } else if ($field == 'pickArea.name') {
    
                            $data["pickup_area"] =  "-";
    
                        }  else if ($field == 'pickFlightNumber') {
    
                            $data["flight"] = "-";
                            
                        } else if ($field == 'pickAirlineCompany') {
    
                            $data["airline"] = "-";
                            
                        } else if ($field == 'supplierCommission') {
                            if ($item->getSupplier()) {
                                $value = $item->getSupplier()->getCommission();
                                $displayValue = $value . '%';
                            } else {
                                $value = 0;
                                $displayValue = '-';
                            }
                            $data["commission"] = trim($displayValue);
    
                        } else if ($field == 'adultRackPrice') {
    
                            $data["rack_a_rate"] = "$".$_extras_addons[$x]["rack_a_rate"];
    
                        } else if ($field == 'childRackPrice') {
                            if($_extras_addons[$x]["rack_c_rate"] != "-"){
                                $data["rack_c_rate"] = "$".$_extras_addons[$x]["rack_c_rate"];
                            }else{
                                $data["rack_c_rate"] = "-".$_extras_addons[$x]["rack_c_rate"];
                            
                            }
    
                        } else if ($field == 'titleNetPrice') {
    
                            $data["net_rate"] = "$" . $_extras_addons[$x]["net_a_rate"];
    
                        }
                        else if ($field == 'totalTax') {
    
                            $data["tax"] = "$".$_extras_addons[$x]["tax"];
    
                        } else if ($field == 'totalRackPrice') {
    
                            $data["total_rack"] = "$".$_extras_addons[$x]["total_rack"];
    
                        } else if ($field == 'totalNetPrice') {
    
                            $data["total_net"] = "$".$_extras_addons[$x]["total_net"];
    
                        } else if ($field == 'service') {

                            $data["addons_extras_services"] = strip_tags($_extras_addons[$x]["label"]);
                        }
                    }
                    $sheet->fromArray($data, NULL, 'A' . $rowToInsert);
                    $rowToInsert++;
                }
            }
            if (count( $item->getAdditionalDrivers() ) > 0 && count( $_additional_drivers ) > 0 ) {

                for ( $x = 0; $x < count( $item->getAdditionalDrivers() ); $x++ ) {
                    if( !array_key_exists( $x, $_additional_drivers ) ){
                        continue;
                    }
                    $data = array(
                        "billing_status"            => "",
                        "type"                      => "",
                        "booking"                   => "",
                        "pickup_date"               => "",
                        "pickTimeTransport"         => "",
                        // "arrival_time"              => "",
                        // "departure_time"            => "",
                        "pax_qty"                   => "",
                        "pickup_area"               => "",
                        // "pickup_location"           => "",
                        "dropoff_area"              => "",
                        // "dropoff_location"          => "",
                        "supplier"                  => "",
                        "vehicle_type"              => "",
                        "vehicle"                   => "",
                        "confirmation_status"       => "",
                        "passenger_name"            => "",
                        "airline"                   => "",
                        "flight"                    => "",
                        "commission"                => "",
                        "addons_extras_services"    => "",
                        "rack_a_rate"               => "",
                        "rack_c_rate"               => "",
                        "tax"                       => "",
                        "total_rack"                => "",
                        "net_rate"                  => "",
                        "total_net"                 => "",
                        
                    );
                    
                    foreach ($fields as $field => $metaData) {
                        

                        if ($field == "supplier.id") {

                            $data["supplier"] = $_additional_drivers[$x]["supplier"];

                        }else if ($field == "vehicle.vehicleType.id") {

                            $data["vehicle_type"] = " ";
    
                        }else if ($field == "vehicle.id") {

                            $data["vehicle"] = $_additional_drivers[$x]["vehicle"];
    
                        } else if ($field == "type") {
    
                            $data["type"] = "Additional Supplier";
    
                        }else if ($field == "pickDate") {
    
                            $displayValue =  $_additional_drivers[$x]["pickup_date"];
                            
                            $data["pickup_date"] = trim($displayValue);
                            
                        }  else if ($field == "pickTimeTransport") {

                            $displayValue = $_additional_drivers[$x]["pickup_time"];
                            $data["pickTimeTransport"] = trim($displayValue);
    
                        } else if ($field == "pickTime") {
                            
                            // $data["arrival_time"] = " ";
    
                        } else if ($field == "_easyadmin_form_design_element_4") {
                            
                            // $data["departure_time"] = " ";
    
                        }  else if ($field == 'passengerName') {
    
                            $data["passenger_name"] = " ";

                        }  else if ($field == 'passengerQty') {
                            $adultCount =  $item->getAdultCount();
                            if( empty($adultCount) OR is_null($adultCount) ){
                                $adultCount = 0;
                            }
                            $childCount =  $item->getChildCount();
                            if( empty($childCount) OR is_null($childCount) ){
                                $childCount = 0;
                            }
                            $data["pax_qty"] = $adultCount + $childCount;
    
                        } else if ($field == 'booking') {
    
                            $data["booking"] = trim( strip_tags($twigExtension->renderEntityField(
                                $twig,  
                                'list', 
                                $this->entity['name'], 
                                $item, 
                                $metaData
                            )));
                        } else if ($field == 'confirmationStatus') {
                            $display_value = "";
                            if( $_additional_drivers[$x]["confirmation_status"] == 0 ){
                                $display_value = "Unassigned";
                            } else if( $_additional_drivers[$x]["confirmation_status"] == 1 ){
                                $display_value = "Assigned";
                            } else if( $_additional_drivers[$x]["confirmation_status"] == 2 ){
                                $display_value = "Approved";
                            } else if( $_additional_drivers[$x]["confirmation_status"] == 3 ){
                                $display_value = "Confirmed";
                            } 
                            $data["confirmation_status"] =  $display_value;
    
                        } else if ($field == 'status') {
    
                            $data["billing_status"] = " ";
                            
                        } else if ($field == 'dropAddress') {
    
                            // $data["dropoff_location"] = $_additional_drivers[$x]["dropoff_location"];

                            
                        } else if ($field == 'dropArea.name') {
    
                            $data["dropoff_area"] = $_additional_drivers[$x]["dropoff_location"];
                            
                        } else if ($field == 'pickAddress') {
    
                            // $data["pickup_location"] = $_additional_drivers[$x]["pickup_location"];

                            
                        } else if ($field == 'pickArea.name') {
    
                            $data["pickup_area"] = $_additional_drivers[$x]["pickup_location"];
    
                        }  else if ($field == 'pickFlightNumber') {
    
                            $data["flight"] = " ";
                            
                        } else if ($field == 'pickAirlineCompany') {
    
                            $data["airline"] = " ";
                            
                        } else if ($field == 'supplierCommission') {
                            
                            $data["commission"] = " ";
    
                        } else if ($field == 'adultRackPrice') {
    
                            $data["rack_a_rate"] = " ";
    
                        } else if ($field == 'childRackPrice') {
                            $data["rack_c_rate"] = " ";
                            
                        } else if ($field == 'titleNetPrice') {
    
                            $data["net_rate"] = "$".number_format((float)$_additional_drivers[$x]["net_rate"], 2, '.', '');
                        }
                        else if ($field == 'totalTax') {
    
                            $data["tax"] = " ";
                        } else if ($field == 'totalRackPrice') {
    
                            $data["total_rack"] = " ";
    
                        } else if ($field == 'totalNetPrice') {
    
                            $data["total_net"] = "$".number_format((float)$_additional_drivers[$x]["total_net"], 2, '.', '');

    
                        } else if ($field == 'service') {

                            $data["addons_extras_services"] = " ";
                        }
                    }
                    $sheet->fromArray($data, NULL, 'A' . $rowToInsert);
                    $rowToInsert++;
                }
            }
        }
        for ($i = 'A'; $i !=  $spreadsheet->getActiveSheet()->getHighestColumn(); $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
        }


        // Define the column letter you want to modify
        $columnLetter = 'D'; // Replace 'A' with the actual column letter

        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();

        // Iterate over each row in the column and modify the values
        for ($row = 1; $row <= $highestRow; $row++) {
            
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == 'date' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            
            // Set the date value in cell D
            $sheet->setCellValue($columnLetter . $row, '=DATE('.$cellValue.')');
        
            // Set the cell format as a date
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD2);
        }

        // Define the column letter you want to modify
        $columnLetter = 'E'; // Replace 'E' with the actual column letter
        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();
        // Iterate over each row in the column and modify the values
        for ($row = 2; $row <= $highestRow; $row++) {
            
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == '-' || strtolower($cellValue) == '' || strtolower($cellValue) == ' ' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            
            $sheet->setCellValue($columnLetter . $row, '=TIME('.$cellValue.')');

            // Convert the DateTime object to an Excel serial number
            // $excelTimeValue = Date::PHPToExcel($cellValue);

            // Set the formatted time value in a cell
            // $cell = $sheet->getCell($columnLetter . $row);
            // $cell->setValueExplicit($excelTimeValue, DataType::TYPE_NUMERIC);
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode('h:mm AM/PM');
        }


        

        // CURRENCY SYMBOLS -need to manually replace these symbols with Excel's designated currency symbols to ensure proper currency recognition and interpretation
        // Apply the currency format to the cell
        $currencySymbol = '$'; // Replace with the desired currency symbol

        $currencyFormatCode = '"'.$currencySymbol.'"#########';
        // Define the column letter you want to modify
        $columnLetter = 'T'; // Replace 'E' with the actual column letter
        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();
        // Iterate over each row in the column and modify the values
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == '-' || strtolower($cellValue) == '--' || strtolower($cellValue) == ' ' || strtolower($cellValue) == '' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            // $cellValue = number_format((float)$cellValue, 2, '.', ''); 
            $str = ['$',','];
            $rplc =['',''];
            $sheet->setCellValue($columnLetter . $row, str_ireplace( $str, $rplc, $cellValue ) );
            if($cellValue == 0.00){
                $currencyFormatCode = '"'.$currencySymbol.'"#,####0.00';

            }
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode($currencyFormatCode);
        }

        // Define the column letter you want to modify
        $columnLetter = 'U'; // Replace 'E' with the actual column letter
        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();
        // Iterate over each row in the column and modify the values
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == '-' || strtolower($cellValue) == '--' || strtolower($cellValue) == ' ' || strtolower($cellValue) == '' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            // $cellValue = number_format((float)$cellValue, 2, '.', ''); 
            $str = ['$',','];
            $rplc =['',''];
            $sheet->setCellValue($columnLetter . $row, str_ireplace( $str, $rplc, $cellValue ) );
            if($cellValue == 0.00){
                $currencyFormatCode = '"'.$currencySymbol.'"#,####0.00';

            }
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode($currencyFormatCode);
        }

        // Define the column letter you want to modify
        $columnLetter = 'V'; // Replace 'E' with the actual column letter
        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();
        // Iterate over each row in the column and modify the values
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == '-' || strtolower($cellValue) == '--' || strtolower($cellValue) == ' ' || strtolower($cellValue) == '' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            // $cellValue = number_format((float)$cellValue, 2, '.', ''); 
            $str = ['$',','];
            $rplc =['',''];
            $sheet->setCellValue($columnLetter . $row, str_ireplace( $str, $rplc, $cellValue ) );
            if($cellValue == 0.00){
                $currencyFormatCode = '"'.$currencySymbol.'"#,####0.00';

            }
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode($currencyFormatCode);
        }

        // Define the column letter you want to modify
        $columnLetter = 'W'; // Replace 'E' with the actual column letter
        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();
        // Iterate over each row in the column and modify the values
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == '-' || strtolower($cellValue) == '--' || strtolower($cellValue) == ' ' || strtolower($cellValue) == '' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            // $cellValue = number_format((float)$cellValue, 2, '.', ''); 
            $str = ['$',','];
            $rplc =['',''];
            $sheet->setCellValue($columnLetter . $row, str_ireplace( $str, $rplc, $cellValue ) );
            if($cellValue == 0.00){
                $currencyFormatCode = '"'.$currencySymbol.'"#,####0.00';

            }
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode($currencyFormatCode);
        }

        // Define the column letter you want to modify
        $columnLetter = 'R'; // Replace 'E' with the actual column letter
        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();
        // Iterate over each row in the column and modify the values
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == '-' || strtolower($cellValue) == '--' || strtolower($cellValue) == ' ' || strtolower($cellValue) == '' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            // $cellValue = number_format((float)$cellValue, 2, '.', ''); 
            $str = ['$',','];
            $rplc =['',''];
            $sheet->setCellValue($columnLetter . $row, str_ireplace( $str, $rplc, $cellValue ) );
            if($cellValue == 0.00){
                $currencyFormatCode = '"'.$currencySymbol.'"#,####0.00';

            }
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode($currencyFormatCode);
        }

        // Define the column letter you want to modify
        $columnLetter = 'S'; // Replace 'E' with the actual column letter
        // Get the highest row index in the column
        $highestRow = $sheet->getHighestRow();
        // Iterate over each row in the column and modify the values
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the cell value in the current row and column
            $cellValue = $sheet->getCell($columnLetter . $row)->getValue();
            if( strtolower($cellValue) == '-' || strtolower($cellValue) == '--' || strtolower($cellValue) == ' ' || strtolower($cellValue) == '' || strtolower($cellValue) == 'null' ) {
                continue;    
            }
            // $cellValue = number_format((float)$cellValue, 2, '.', ''); 
            $str = ['$',','];
            $rplc =['',''];
            $sheet->setCellValue($columnLetter . $row, str_ireplace( $str, $rplc, $cellValue ) );
            if($cellValue == 0.00){
                $currencyFormatCode = '"'.$currencySymbol.'"#,####0.00';

            }
            $sheet->getStyle($columnLetter . $row)->getNumberFormat()->setFormatCode($currencyFormatCode);
        }

        

        // exit;
        // Enable AutoFilter for all columns
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $range = 'A1:' . $highestColumn . '1';
        $sheet->setAutoFilter($range);

        $writer = new Writer\Xlsx($spreadsheet);
        
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="dashboard'.date("_Y-m-d_h-i-sa").'.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }


    /**
     *  management send-bulk-email-to-driver
     *
     * @Route("/admin/management/send-bulk-email-to-driver", name="management_send_bulk_email_to_driver")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendBulkEmailToDriverAction(Request $request)
    {
        
        $em = $this->getDoctrine()->getManager();
        // LOGGING INTO HISTORYLOG
        $em->beginTransaction();
        $historyLog = new HistoryLog();
        $currentDateTime = new DateTime('now', new \DateTimeZone('GMT-6')); 
             
            $historyLog->setCreatedAt( $currentDateTime );
        $historyLog->setUser( $this->get('security.token_storage')->getToken()->getUser() );
        $historyLog->setModifications("Created bulk e-mail in frontApp!" );
        $em->persist($historyLog);
        $em->flush();
        $em->commit();
        // LOGGING INTO HISTORYLOG
        $messages = [];
        
        try {
            $data_to_proceed                = [];
            $bulk_data                      = json_decode($_POST["data"]);
            $additionalDrivers              = [];
            foreach ($bulk_data as $_bulk_data) {
                $data_id                    = $_bulk_data->id;
                $data_status                = $_bulk_data->status;
                // $data_pickTimeTransport     = $_bulk_data->pickTimeTransport;
                $data_supplier_id           = $_bulk_data->{'supplier.id'};
                $data_supplierCommission    = $_bulk_data->supplierCommission;
                    //   $data_vehicle_id            = $_bulk_data->{'vehicle.id'};
                $data_send_email            = 0;
                $data_pickDate              = $_bulk_data->pickDate;
                $data_pickTime              = $_bulk_data->pickTime;
                array_push(
                    $data_to_proceed,
                    array(
                        "id"                    => $data_id,
                        "status"                => $data_status,
                        // "pickTimeTransport"     => $data_pickTimeTransport,
                        "supplier_id"           => $data_supplier_id,
                        "supplierCommission"    => $data_supplierCommission,
                        // "vehicle_id"            => $data_vehicle_id,
                        "send_email"            => $data_send_email,
                        "pickDate"              => $data_pickDate,
                        "pickTime"              => $data_pickTime,
                        "additionalDrivers"     => $additionalDrivers,
                    )
                );
            }

            $orders                 = [];
            $assignments_to_send    = [];
            $orderItemSupplier      = [];
            foreach ($data_to_proceed as $_data) {
                $data_id                    = $_data["id"];
                $data_status                = $_data["status"];
                // $data_pickTimeTransport     = $_data["pickTimeTransport"];
                $data_supplier_id           = $_data["supplier_id"];
                $data_supplierCommission    = $_data["supplierCommission"];
                // $data_vehicle_id            = $_data["vehicle_id"];
                $data_send_email            = $_data["send_email"];
                $data_pickDate              = $_data["pickDate"];
                $data_pickTime              = $_data["pickTime"];
                $additionalDrivers          = $_data["additionalDrivers"];

                $orderItem = $em->getRepository(OrderItem::class)->find($data_id);

                if (!$orderItem) {
                    return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
                }

                $additionalDrivers = $orderItem->getAdditionalDrivers();

                $orderUtils = $this->get('wicrew.order.utils');
                $orderItemType = $orderItem->getType();
                if($orderItemType == "activity_regular" || $orderItemType == "activity_regular"){
                    $orderItemType = "Activity";
    
                }else if( $orderItemType == "private_shuttle" ){
                    $orderItemType = "Private shuttle";
                    
                }else if( $orderItemType == "shared_shuttle" ){
                    $orderItemType = "Shared shuttle";
    
                }else if( $orderItemType == "private_flight" ){
                    $orderItemType = "Private flight";
    
                }else if( $orderItemType == "private_jbj" ){
                    $orderItemType = "Private JBJ";
    
                }else if( $orderItemType == "shared_jbj" ){
                    $orderItemType = "Shared JBJ";
    
                }else if( $orderItemType == "riding_jbj" ){
                    $orderItemType = "Riding JBJ";
    
                }else if( $orderItemType == "water_taxi" ){
                    $orderItemType = "Water taxi";
    
                }
                // $orders[] = $orderItem;
                array_push($orders, $orderItem);
                $orderItemSupplier = $orderItem->getSupplier();

                if ($additionalDrivers) {
                    foreach ($additionalDrivers as $key => $additionalDriver) {
                        $assignments_to_send    = [];
                        $assignments_to_send[]  = $additionalDriver;
                        $orders                 = [];
                        if ($additionalDriver->getDriver()) {
                            $orderUtils->sendDriverEmails($additionalDriver->getDriver(), $this, $assignments_to_send, $orders, $orderItemType);
                        }
                    }
                }
            }
            if ($orderItemSupplier) {
                $orderUtils->sendDriverEmails($orderItemSupplier, $this, $assignments_to_send, $orders, $orderItemType);
            }
        } catch (Throwable $e) {
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['status' => 'success']);
    }

    /**
     *  management send-email-to-driver-about-new-notes
     *
     * @Route("/admin/management/send-email-to-driver-about-new-notes", name="management_send_email_to_driver_new_notes")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendEmailToDriverNewNotesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $messages = [];

        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));

            if (!$orderItem) {
                return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'no order item']);
            }

            $additionalDrivers = $orderItem->getAdditionalDrivers();

            $orders = [];

            $orderUtils = $this->get('wicrew.order.utils');

            $orders[] = $orderItem;
            $assignments_to_send = [];
            if ($orderItem->getSupplier()) {
                $orderUtils->sendDriverEmails($orderItem->getSupplier(), $this, $assignments_to_send, $orders, $type = 'new_note');
            }

            if ($additionalDrivers) {
                foreach ($additionalDrivers as $key => $additionalDriver) {
                    $assignments_to_send = [];
                    $assignments_to_send[] = $additionalDriver;
                    $orders = [];
                    if ($additionalDriver->getDriver()) {
                        $orderUtils->sendDriverEmails($additionalDriver->getDriver(), $this, $assignments_to_send, $orders, $type = 'new_note');
                    }
                }
            }
        } catch (Throwable $e) {
            dump($e);
            die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['status' => 'success']);
    }


    /**
     *  This function is to remove additional driver from any orderitem
     *
     * @Route("/admin/management/removeAdditionalDriver", name="remove_additional_driver")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeAdditionalDriver(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // Begin a transaction
        $em->beginTransaction();
        
        try {
            $additional_driver = $em->getRepository(OrderItemHasDriver::class)->findOneBy(['id' => $request->request->get('driverid')]);

            if (!$additional_driver) {
                return $this->returnSuccessResponse(['status' => 'failed', 'message' => 'No driver found!']);
            }

            $em->remove($additional_driver);
            $em->commit();
            $em->flush();

        } catch (Throwable $e) {
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['status' => 'success']);
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
 *
 * @return QueryBuilder The Query Builder instance
 */
protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null) { 

    $em             = $this->getDoctrine()->getManagerForClass($this->entity['class']);
    $selectedColumn =  $this->request->query->get('selectedColumn', 1);     
    $multiColumn    =  $this->request->query->get('multiColumn', false); 
    if( $multiColumn ){
        $selectedData    =  $this->request->query->get('selectedData', []); 
        $queryBuilder = $em->createQueryBuilder()->select('entity')->from($this->entity['class'], 'entity');        
        $loop_count = 0;
        foreach(json_decode($selectedData) as $selectedColumn => $searchQuery ){
            $loop_count++;
            
            if ( !empty($selectedColumn) && $selectedColumn != 1 && ( $selectedColumn != "vehicle" && $selectedColumn != "vehicleType"  && $selectedColumn != "supplier" )) {
    
                if( $selectedColumn == "dropArea" ){
                    $selectedColumn = "dropAddress";
                }
        
                if( $selectedColumn == "pickArea" ){
                    $selectedColumn = "pickAddress";
                }
        
                if( $selectedColumn == "status"){
                    if( strtolower($searchQuery) == "unpaid" ){
                        $searchQuery = 0;
                    }
                    if( strtolower($searchQuery) == "paid" ){
                        $searchQuery = 1;
                    }
                } 

                if( $selectedColumn == "orderId"){
                    $searchQuery = str_ireplace("#", "", $searchQuery);
                    $searchQuery = str_ireplace("rj", "", $searchQuery);
                } 
                if( (strpos($searchQuery, ',') !== false) ){
                    
                    $Multisearched = explode(",", $searchQuery);
                    $loop_count1 = 0;
                    foreach ($Multisearched as $index => $multisearch) {
                        $loop_count1++;
                        if($loop_count1 > 1){
                            $queryBuilder->orWhere(
                                $queryBuilder->expr()->eq('entity.'.$selectedColumn, ':' . $selectedColumn)
                            )
                            ->setParameter($selectedColumn, $multisearch);
                        }else{

                            $queryBuilder->andWhere(
                                $queryBuilder->expr()->eq('entity.'.$selectedColumn, ':' . $selectedColumn)
                            )
                            ->setParameter($selectedColumn, $multisearch);
                        }

                    }
        
                
                }else{
                    
                    if($loop_count > 1){


                        $queryBuilder->andWhere(
                            $queryBuilder->expr()->eq('entity.'.$selectedColumn, ':' . $selectedColumn)
                        )
                        ->setParameter($selectedColumn, $searchQuery);

                    }else{
                        $queryBuilder->orWhere(
                            $queryBuilder->expr()->eq('entity.'.$selectedColumn, ':' . $selectedColumn)
                        )
                        ->setParameter($selectedColumn, $searchQuery);
                    }
        
                  
                }
            }
            
        }

        if (!empty($dqlFilter)) {
            $queryBuilder->andWhere($dqlFilter);
        }
         
        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.' . $sortField, $sortDirection ?: 'DESC');
        }
        $query = $queryBuilder->getQuery();
        return $queryBuilder;
        
        // $sql = $query->getSQL();
        
        // echo "$sql";
        // $result = $query->getResult();
        // echo "<pre>".print_r(count($result), true)."</pre>";
        // exit;
    }
    /* @var EntityManager */
    /* @var QueryBuilder */
    if ( !empty($selectedColumn) && $selectedColumn != 1 && ( $selectedColumn != "vehicle" && $selectedColumn != "vehicleType"  && $selectedColumn != "supplier" )) {
    
        if( $selectedColumn == "dropArea" ){
            $selectedColumn = "dropAddress";
        }

        if( $selectedColumn == "pickArea" ){
            $selectedColumn = "pickAddress";
        }

        if( (strpos($searchQuery, ',') !== false) ){

            if( $selectedColumn == "orderId"){
                $searchQuery = str_ireplace("#", "", $searchQuery);
                $searchQuery = str_ireplace("rj", "", $searchQuery);
            } 
            $queryBuilder = $em->createQueryBuilder()->select('entity')->from($this->entity['class'], 'entity');        
        
            $Multisearched = explode(",", $searchQuery);
            foreach ($Multisearched as $index => $multisearch) {
                $queryBuilder->orWhere('entity.' . $selectedColumn.' LIKE :search'.$index)->setParameter('search'.$index, '%'.$multisearch.'%');
            }

            if (!empty($dqlFilter)) {
                $queryBuilder->andWhere($dqlFilter);
            }
                
            if (null !== $sortField) {
                $queryBuilder->orderBy('entity.' . $sortField, $sortDirection ?: 'DESC');
            }
            $query = $queryBuilder->getQuery();
            return $queryBuilder;
        }else{
            if( $selectedColumn == "orderId"){
                $searchQuery = str_ireplace("#", "", $searchQuery);
                $searchQuery = str_ireplace("rj", "", $searchQuery);
            } 
            $queryBuilder = $em->createQueryBuilder()->select('entity')->from($this->entity['class'], 'entity');        
        
            $queryBuilder->orWhere('entity.' . $selectedColumn.' LIKE :search')->setParameter('search', '%'.$searchQuery.'%');

            if (!empty($dqlFilter)) {
                $queryBuilder->andWhere($dqlFilter);
            }
                
            if (null !== $sortField) {
                $queryBuilder->orderBy('entity.' . $sortField, $sortDirection ?: 'DESC');
            }
            $query = $queryBuilder->getQuery();
            return $queryBuilder;
        }
    }


    if (strpos(strtolower($searchQuery), '#rj') !== false) {
        $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        // unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);

        
    }

    if (strpos(strtolower($searchQuery), 'rj') !== false) {
        $searchQuery = str_ireplace("RJ", "", $searchQuery);
        // unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);

        
    }

    if (strpos(strtolower($searchQuery), 'pending') !== false) {
        $searchQuery = str_ireplace("pending", "0", $searchQuery);
        unset($this->entity['search']['fields']["orderId"]);
        // unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);

        
    }

    if (strpos(strtolower($searchQuery), 'paid cash') !== false) {
        $searchQuery = str_ireplace("paid cash", "1", $searchQuery);
        unset($this->entity['search']['fields']["orderId"]);
        // unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);

    }


    if (strpos(strtolower($searchQuery), 'paid') !== false) {
        $searchQuery = str_ireplace("paid", "1", $searchQuery);
        unset($this->entity['search']['fields']["orderId"]);
        // unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);

    }

    if (strpos(strtolower($searchQuery), 'cancelled') !== false) {
        $searchQuery = str_ireplace("cancelled", "2", $searchQuery);
        unset($this->entity['search']['fields']["orderId"]);
        // unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);

    }

    if (strtotime($searchQuery) ){

        $searchQuery = date( 'Y-m-d', strtotime($searchQuery) );
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        //    unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }
    if (strpos(strtolower($dqlFilter), '#rj') !== false) {
        $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        // unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'pickDate') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        // unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'pickTime') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        // unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }


    if (strpos(strtolower($dqlFilter), 'orderId') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        // unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'adultCount') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        // unset($this->entity['search']['fields']["adultCount"]);

        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'dlOrder.firstName') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["adultCount"]);

        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        // unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'pickArea.name') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["adultCount"]);

        unset($this->entity['search']['fields']["confirmationStatus"]);
        // unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }


    if (strpos(strtolower($dqlFilter), 'dropArea.name') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["adultCount"]);

        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        // unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'supplier.bizName') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        // unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }
    if (strpos(strtolower($dqlFilter), 'order_status') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        // unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'pickAirlineCompany') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        // unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }

    if (strpos(strtolower($dqlFilter), 'pickFlightNumber') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        // unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }



    if (strpos(strtolower($dqlFilter), 'vehicle.vehicleType.name') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        // unset($this->entity['search']['fields']["vehicle.vehicleType.name"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
    }


    if (strpos(strtolower($dqlFilter), 'type') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        unset($this->entity['search']['fields']["confirmationStatus"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["vehicle.vehicleType.name"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
        // unset($this->entity['search']['fields']["type"]);
    }


  

    if (strpos(strtolower($dqlFilter), 'childCount') !== false) {
        // $searchQuery = str_ireplace("#RJ", "", $searchQuery);
        unset($this->entity['search']['fields']["pickDate"]);
        unset($this->entity['search']['fields']["orderId"]);
        unset($this->entity['search']['fields']["status"]);
        // unset($this->entity['search']['fields']["childCount"]);
        unset($this->entity['search']['fields']["pickArea.name"]);
        unset($this->entity['search']['fields']["dropArea.name"]);
        unset($this->entity['search']['fields']["pickFlightNumber"]);
        unset($this->entity['search']['fields']["pickAirlineCompany"]);
        unset($this->entity['search']['fields']["supplier.bizName"]);
        unset($this->entity['search']['fields']["supplierCommission"]);
        unset($this->entity['search']['fields']["titleRackPrice"]);
        unset($this->entity['search']['fields']["titleNetPrice"]);
        unset($this->entity['search']['fields']["totalTax"]);
        unset($this->entity['search']['fields']["pickTime"]);
        unset($this->entity['search']['fields']["pickTimeTransport"]);
        unset($this->entity['search']['fields']["dropAddress"]);
        unset($this->entity['search']['fields']["pickAddress"]);
        unset($this->entity['search']['fields']["totalRackPrice"]);
        unset($this->entity['search']['fields']["dlOrder.firstName"]);
        unset($this->entity['search']['fields']["dlOrder.lastName"]);
        unset($this->entity['search']['fields']["order_status"]);
        unset($this->entity['search']['fields']["vehicle.vehicleType.name"]);
        unset($this->entity['search']['fields']["dlOrder.items.pickDate"]);
        unset($this->entity['search']['fields']["type"]);
    }
    
    return $this->get('easyadmin.query_builder')->createSearchQueryBuilder($this->entity, $searchQuery, $sortField, $sortDirection, $dqlFilter);
}
    /**
     * @Route("/admin/select_boxes/", name="select_boxes")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    public function select_boxes(Request $request): JsonResponse {
        $maxResults = 10; // Number of records to be fetched at once
        $em = $this->getDoctrine()->getManager();
        $offset = $request->query->getInt('offset', 0); 
        $queryBuilder = $em->createQueryBuilder()
            ->select(' entity.type, entity.orderId, entity.status, entity.adultCount, entity.childCount, entity.pickDate, entity.pickTime, entity.pickTimeTransport, entity.dropAddress, entity.pickAddress, entity.passengerName, entity.pickAirlineCompany, entity.pickFlightNumber, entity.supplierCommission, entity.pickAddress,  vehicle.name AS vehicleName')
            ->from(OrderItem::class, 'entity')
            ->leftJoin('entity.vehicle', 'vehicle');


        $queryBuilder->andWhere('entity.status != 2 AND entity.archiveStatus != 1');
        
        $queryBuilder->setMaxResults($maxResults)->setFirstResult($offset);
        
        $queryBuilder->orderBy('entity.orderId', 'DESC');

        $preResult = $queryBuilder->getQuery()->getResult();
        
        $allDataArray = [];
        foreach($preResult as $result) {
            foreach($result as $column_name => $column_value) {
                if(!array_key_exists($column_name, $allDataArray)) {
                    $allDataArray[$column_name] = array();
                }
                
                if(!in_array($column_value, $allDataArray[$column_name])) {
                    array_push($allDataArray[$column_name], $column_value);
                }
            }
        }
        
        return new JsonResponse($allDataArray);
    }

        /**
     * @Route("/admin/select_boxes_load_more/", name="select_boxes_load_more")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

     public function select_boxes_load_more(Request $request): JsonResponse {
        $maxResults = 10; // Number of records to be fetched at once
        $em         = $this->getDoctrine()->getManager();
        $offset     = $request->query->getInt('offset', 0); 
        $toLoadCol  = $request->query->get('to_load_col', null);
        $queryBuilder = $em->createQueryBuilder()
            ->select(' entity.'.$toLoadCol)
            ->from(OrderItem::class, 'entity');

        $queryBuilder->andWhere('entity.status != 2 AND entity.archiveStatus != 1');
        
        $queryBuilder->setMaxResults($maxResults)->setFirstResult($offset);
        
        $queryBuilder->orderBy('entity.orderId', 'DESC');

        $preResult = $queryBuilder->getQuery()->getResult();
        
        $allDataArray = [];
        foreach($preResult as $result) {
            foreach($result as $column_name => $column_value) {
                if(!array_key_exists($column_name, $allDataArray)) {
                    $allDataArray[$column_name] = array();
                }
                
                if(!in_array($column_value, $allDataArray[$column_name])) {
                    array_push($allDataArray[$column_name], $column_value);
                }
            }
        }
        
        return new JsonResponse($allDataArray);
    }

    /**
     * @Route("/autocomplete", name="autocomplete")
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $input = $request->get('input');

        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity.type, entity.orderId, entity.status, entity.adultCount, entity.childCount, entity.pickDate, entity.pickTime, entity.dropAddress, entity.pickAddress, entity.passengerName, entity.pickAirlineCompany, entity.pickFlightNumber, entity.supplierCommission, entity.pickAddress, vehicle.name AS vehicleName')
            ->from(OrderItem::class, 'entity')
            ->leftJoin('entity.vehicle', 'vehicle')
            ->where($em->createQueryBuilder()->expr()->like('entity.orderId', ':input'))
            ->setParameter('input', '%'.$input.'%')
            ->setMaxResults(10);

        $items = $queryBuilder->getQuery()->getResult();

        $suggestions = [];
        foreach ($items as $item) {
            $suggestion                     = new \stdClass();
            $suggestion->type               = $item['type'];
            $suggestion->orderId            = $item['orderId'];
            $suggestion->status             = $item['status'];
            $suggestion->adultCount         = $item['adultCount'];
            $suggestion->childCount         = $item['childCount'];
            $suggestion->pickDate           = $item['pickDate'];
            $suggestion->pickTime           = $item['pickTime'];
            $suggestion->dropAddress        = $item['dropAddress'];
            $suggestion->pickAddress        = $item['pickAddress'];
            $suggestion->passengerName      = $item['passengerName'];
            $suggestion->pickAirlineCompany = $item['pickAirlineCompany'];
            $suggestion->pickFlightNumber   = $item['pickFlightNumber'];
            $suggestion->supplierCommission = $item['supplierCommission'];
            $suggestion->vehicleName        = $item['vehicleName'];
            $suggestions[]                  = $suggestion;
        }

        return new JsonResponse($suggestions);
    }



    /**
     * @Route("/multicolsearch", name="multicolsearch")
     *
     * @return QueryBuilder The Query Builder instance
     */
    public function multicolsearch(Request $request) {
    }


}