<?php

namespace App\Wicrew\SaleBundle\Controller\Admin;

require_once(dirname(__DIR__,5)."/phpSpreadsheet/vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer as Writer;

use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use DateTime;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use App\Wicrew\SaleBundle\Service\OrderService;
use App\Wicrew\CoreBundle\Service\Mailer;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Knp\Snappy\Pdf;

/**
 * ActivityManagementController
 */
class ActivityManagementController extends BaseAdminController {
    const ADDITIONAL_DRIVERS_CLASS_NAME = 'OrderItemHasDriver';
    private $additionalDriversConfig = array();
    
    public static function getSubscribedServices(): array {
        return parent::getSubscribedServices() + [
            'wicrew.order.utils' => OrderService::class,
            'knp_snappy.pdf' => Pdf::class,
        ];
    }

    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null) {
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

        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.' . $sortField, $sortDirection ?: 'DESC');
        }
       
        return $queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(Request $request) {
        parent::initialize($request);
        $this->additionalDriversConfig = $this->get('easyadmin.config.manager')->getEntityConfig(self::ADDITIONAL_DRIVERS_CLASS_NAME);
    }

    /**
     * {@inheritDoc}
     */
    protected function listAction() {
        $this->dispatch(EasyAdminEvents::PRE_LIST);

        $fields = $this->entity['list']['fields'];
      
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->entity['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $this->dispatch(EasyAdminEvents::POST_LIST, ['paginator' => $paginator]);

        // Inject additional drivers config to get its data in the TM's list view.
        $additionalDriverFields = $this->additionalDriversConfig['list']['fields'];

        $parameters = [
            'paginator' => $paginator,
            'fields' => $fields,
            '_driver_entity_config_name' => self::ADDITIONAL_DRIVERS_CLASS_NAME,
            'driverFields' => $additionalDriverFields,
            'batch_form' => $this->createBatchForm($this->entity['name'])->createView(),
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['list', $this->entity['templates']['list'], $parameters]);
    }

    /**
     * Activity management save
     *
     * @Route("/admin/activitymanagement/save", name="activity_management_save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $messages = [];

        $driversThatHaveEmailsSentIDs = [];
        $driversToSendEmailsTo = [];

        $old_driversThatHaveEmailsSentIDs = [];
        $old_driversToSendEmailsTo = [];

        $new_driversThatHaveEmailsSentIDs = [];
        $new_driversToSendEmailsTo = [];

        try {
            $em->beginTransaction();

            /* @var OrderItem $orderItem */
            $orderItem = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('id')]);
            $order = $orderItem->getOrder();

            $newStatus =  (int) $request->request->get('status');
            
            if (is_int($newStatus)) {
                $orderItem->setStatus($newStatus);
            }

            $confirmationStatus = (int) $request->request->get('confirmation_status');
            if (is_int($confirmationStatus)) {
                $orderItem->setConfirmationStatus($confirmationStatus);
            }

            $oldConfirmationStatus = $orderItem->getConfirmationStatus();
            $newConfirmationStatus = $confirmationStatus;
            $activity = $orderItem->getActivity();

            // TODO: refactor this into a function
            // email confirm
                $isConfirmed = $newConfirmationStatus == OrderItem::CONFIRMATION_STATUS_CONFIRMED && $newConfirmationStatus !=  $oldConfirmationStatus;
                
                if ($isConfirmed) {  
                    $siteEmail = 'info@iltcostarica.com';
                    $mailerService = $this->container->get('wicrew.core.mailer');
                    $translator = $this->container->get('translator');
                    $customerEmail = $order->getEmail();
                    $subject_trans_key = $order->getQuote() ? 'email.confirm.order.quote' : 'email.confirm.order';
                    $subject = $translator->trans($subject_trans_key);
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
                    $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                        'order' => $order,
                        // 'cardBrand' => $cardBrand,
                        // 'last4Digits' => $last4Digits,
                        'logoSrc' => $logoSrc,
                        'tripadvisorSrc' => $tripadvisorSrc,
                        'facebookSrc' => $facebookSrc,
                        'wopitaSrc' => $wopitaSrc,
                        'imageItemSrcs' => $imageItemSrcs,
                    ]);

                    $body_pdf = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                        'order' => $order,
                        // 'cardBrand' => $cardBrand,
                        // 'last4Digits' => $last4Digits,
                        'isUsedInPdf' => true
                    ]);
                    $pdfOutputPath = $this->get('kernel')->getProjectDir() . '/var/log/confirm.pdf';
                    $this->container->get('knp_snappy.pdf')->generateFromHtml($body_pdf, $pdfOutputPath, [
                        'margin-right' => '0mm',
                        'margin-left' => '0mm'
                    ], true);
                    $pdfAttachment = [
                        'path' => $pdfOutputPath,
                        'filename' => 'confirmation.pdf'
                    ];
                    
                    $mailerService->send([
                        'from' => $siteEmail,
                        'to' => $customerEmail,
                        'subject' => $subject,
                        'body' => $body,
                        'attachments' => [ $pdfAttachment ]
                    ]); 
                }
            // email confirm

            $orderItem->setConfirmationStatus($newConfirmationStatus);

            $supplier = $em->getRepository(Partner::class)->findOneBy(['id' => $request->request->get('supplier_id')]);
            $currentDriver = $orderItem->getSupplier();
            $orderItem->setSupplier($supplier);
            if ($request->request->get('supplierCommission') === '0' && $supplier !== null) {
                $orderItem->setSupplierCommission($supplier->getCommission());
            } else {
                $orderItem->setSupplierCommission($request->request->get('supplierCommission'));
            }

            // vehicle
            $vehicle = $em->getRepository(Vehicle::class)->findOneBy(['id' => $request->request->get('vehicle_id')]);
            $orderItem->setVehicle($vehicle);
 
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
            if ($newDriver && $newDriver->getEmail() === null) {
                $messages[] = $this->translator()->trans('transport_management.save.no_email', ['driverName' => $newDriver->getBizName()]);
            } else {
                if ($sendEmail) {
                    if (!in_array($newDriver->getId(), $driversThatHaveEmailsSentIDs)) {
                        $driversToSendEmailsTo[] = $newDriver;
                        $driversThatHaveEmailsSentIDs[] = $newDriver->getId();
                    } 
                }
                if ($oldDriver) {
                    $old_driversToSendEmailsTo[] = $oldDriver;
                    $old_driversThatHaveEmailsSentIDs[] = $oldDriver->getId();
                }
                if ($newDriver) {
                    $new_driversToSendEmailsTo[] = $newDriver;
                    $new_driversThatHaveEmailsSentIDs[] = $newDriver->getId();
                }
            } 

            $orderItem->setPickDate(new DateTime($request->request->get('pickDate')));
            $orderItem->setPickTime(new DateTime($request->request->get('pickTime'))); 

            $currentDrivers = $orderItem->getAdditionalDrivers();
            $currentIndices = $currentDrivers->getKeys();
            $additionalDrivers = $this->getRequestDataNoThrow($request, 'additionalDrivers', array());

            $editedOrAddedIndices = [];
            foreach ($currentDrivers as $key => $currentDriver) {
                $driver = $currentDriver->getDriver();
                if (!$driver) continue;
                if ($driver->getEmail() === null) {
                    $messages[] = $this->translator()->trans('transport_management.save.no_email', ['driverName' => $driver->getBizName()]);
                } else { 
                    $old_driversToSendEmailsTo[] = $driver;
                    $old_driversThatHaveEmailsSentIDs[] = $driver->getId();
                }
            }
         
            /* @var Partner[] $driversToSendEmailsTo */ 
            foreach ($additionalDrivers as $index => $addDriver) {
                $editedOrAddedIndices[] = $index;

                $newDriver = $em->getRepository(Partner::class)->findOneBy(['id' => $addDriver['driver.id']]);
                $vehicle = $em->getRepository(Vehicle::class)->findOneBy(['id' => $addDriver['vehicle.id']]);
                $fromDesc = $addDriver['fromDescription'];
                $toDesc = $addDriver['toDescription'];
                $rack = $addDriver['rack'];
                $net = $addDriver['net'];

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
                $itemHasDriver->setRack($rack);
                $itemHasDriver->setNet($net);

                $hasNewDriver = $newDriver !== null && ($oldDriver === null || !$newDriver->equalsID($oldDriver));
                if ($hasNewDriver) {
                    $itemHasDriver->setSendEmail(new DateTime());
                } else if ($newDriver === null) {
                    $itemHasDriver->setSendEmail(null);
                }

                $sendEmail = isset($addDriver['sendEmail']);
                
                if ($newDriver && $newDriver->getEmail() === null) {
                    $messages[] = $this->translator()->trans('transport_management.save.no_email', ['driverName' => $newDriver->getBizName()]);
                } else {
                    if ($sendEmail) {
                        if (!in_array($newDriver->getId(), $driversThatHaveEmailsSentIDs)) {
                            $driversToSendEmailsTo[] = $newDriver;
                            $driversThatHaveEmailsSentIDs[] = $newDriver->getId();
                        }
                    }
                    if ($newDriver) {
                        $new_driversToSendEmailsTo[] = $newDriver;
                        $new_driversThatHaveEmailsSentIDs[] = $newDriver->getId();
                    }
                }
            }

            // Remove any unused indices (I.E. they were removed).
            foreach ($currentIndices as $key) {
                if (!in_array($key, $editedOrAddedIndices)) {
                    $currentDrivers->remove($key);
                }
            }
           
            $driver = $em->getRepository(Partner::class)->findOneBy(['id' => $request->request->get('activity_driver_id')]); 
           
            if ($driver) {
                $activity->setDriver($driver);
            }

            $em->flush();
            $em->commit();
        } catch (Throwable $e) { dump($e);die;
            $em->rollback(); 
            return $this->returnExceptionResponse($e);
        }
       
        try { 
            $orderUtils = $this->get('wicrew.order.utils'); 
            // if new driver or change send confirmation to all driver  
            $new_driversThatHaveEmailsSentIDs = [];
            
            if ($new_driversThatHaveEmailsSentIDs != $old_driversThatHaveEmailsSentIDs) {  
                foreach ($new_driversToSendEmailsTo as $driver) {
                    if (!in_array($driver->getId(), $new_driversThatHaveEmailsSentIDs)) {
                        $new_driversThatHaveEmailsSentIDs[] = $driver->getId();
                    } else {
                        continue;
                    }

                    $driver_assignments = $driver_orders = [];
                    $assignments = $orderItem->getAdditionalDrivers();
                    
                    foreach ($assignments as $key => $assignment) {
                        if ($assignment->getDriver() == $driver) {
                            $driver_assignments[] = $assignment;
                        }
                    }
               
                    if ($orderItem->getSupplier() == $driver) {
                        $driver_orders[] = $orderItem;
                    } 
                  
                    // $orderUtils->sendDriverEmails($driver, $this, $driver_assignments, $driver_orders, $type = 'activity');
                }
            }
           
            foreach ($driversToSendEmailsTo as $driver) {
                // $orderUtils->sendDriverEmails($driver, $this, $assignments = null, $orders = null, $type = 'activity');
            }
            $em->flush();
        } catch (Throwable $e) {  
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse([ 'messages' => $messages, 'sentIDs' => $driversThatHaveEmailsSentIDs ]);
    }

    /**
     * activity management resent-confirmation
     *
     * @Route("/admin/activitymanagement/resent-confirmation", name="activity_management_resent_confirmation")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function resentConfirmationAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $messages = [];
  
        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));

            if (!$orderItem) {
                return $this->returnSuccessResponse([ 'status' => 'failed', 'message' => 'no order item' ]);
            }

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

            $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                'order' => $order,
                'cardBrand' => $cardBrand,
                'last4Digits' => $last4Digits,
                'logoSrc' => $logoSrc,
                'tripadvisorSrc' => $tripadvisorSrc,
                'facebookSrc' => $facebookSrc,
                'wopitaSrc' => $wopitaSrc,
                'imageItemSrcs' => $imageItemSrcs,
            ]);

            $body_pdf = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.html.twig', [
                'order' => $order,
                'cardBrand' => $cardBrand,
                'last4Digits' => $last4Digits,
                'isUsedInPdf' => true
            ]);

            $translator = $this->get('translator');
            $subject_trans_key = $order->getQuote() ? 'email.confirm.order.quote' : 'email.confirm.order';
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
                'attachments' => [ $pdfAttachment ]
            ]);
        } catch (Throwable $e) { dump($e);die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }
         
        return $this->returnSuccessResponse([ 'status' => 'success' ]);
    }

    /**
     * activity management send-email-to-driver
     *
     * @Route("/admin/activity/send-email-to-driver", name="activity_management_send_email_to_driver")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendEmailToDriverAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $messages = [];
  
        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));

            if (!$orderItem) {
                return $this->returnSuccessResponse([ 'status' => 'failed', 'message' => 'no order item' ]);
            }
 
            $additionalDrivers = $orderItem->getAdditionalDrivers();
           
            $orders = [];
  
            $orderUtils = $this->get('wicrew.order.utils');
            
            $orders[] = $orderItem;  
            $assignments_to_send = [];
            if ($orderItem->getSupplier()) { 
                $orderUtils->sendDriverEmails($orderItem->getSupplier(), $this, $assignments_to_send, $orders, $type = 'activity');
            }
             
            if ($additionalDrivers) {
                foreach ($additionalDrivers as $key => $additionalDriver) { 
                    $assignments_to_send = [];
                    $assignments_to_send[] = $additionalDriver;
                    $orders = []; 
                    if ($additionalDriver->getDriver()) {
                        $orderUtils->sendDriverEmails($additionalDriver->getDriver(), $this, $assignments_to_send, $orders, $type = 'activity');
                    }
                }
            } 
        } catch (Throwable $e) { dump($e);die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }
         
        return $this->returnSuccessResponse([ 'status' => 'success' ]);
    }
 
    /**    
     * Export the report
     */
    public function exportAction() {
        $spreadsheet    = new Spreadsheet();
        $sheet          = $spreadsheet->getActiveSheet();
        $rowToInsert    = 1;

        $translator = $this->container->get('translator');
        $twig = $this->container->get('twig');
        $twigExtension = $this->container->get('twig')->getExtension(\EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension::class);


        // Add the header of the CSV file
        $fields = $this->entity['list']['fields'];
        $fields["service"] = array("label" => "Service");
        $headers = array(
            "Date",
            "Time",
            "Booking #",
            "Service Type",
            "Activity",
            "Passenger Name",
            "Adults",
            "Children",
            "Pick-up Area",
            "Pick-up Location",
            "Dropoff Area",
            "Dropoff Location",
            "Extras or Addons",
            "Supplier",
            "Vehicle",
            "Confirmation Status",
            "Billing Status",
            "Commission",
            "Adult Rack Price",
            "Child Rack Price",
            "Adult Net Price",
            "Child Net Price",
            "Tax",
            "Sub Total Rack",
            "Sub Total Net",
            "Service"
        );

        $sheet->fromArray($headers, NULL, 'A' . $rowToInsert);
        $rowToInsert++;

        $paginatorS = array();

        $_temp_paginator = $this->findAll(
            $this->entity['class'],
            $this->request->query->get('page', 1),
            $this->entity['list']['max_results'],
            $this->request->query->get('sortField'),
            $this->request->query->get('sortDirection'),
            $this->entity['list']['dql_filter']
        );
        array_push($paginatorS, $_temp_paginator);
        $resultTotalPageCount = ceil($_temp_paginator->count() / 16);
        for ($x = 2; $x <= $resultTotalPageCount; $x++) {
            $_temp_paginator = $this->findAll(
                $this->entity['class'],
                $this->request->query->get('page', $x),
                $this->entity['list']['max_results'],
                $this->request->query->get('sortField'),
                $this->request->query->get('sortDirection'),
                $this->entity['list']['dql_filter']
            );
            array_push($paginatorS, $_temp_paginator);
        }

        foreach ($paginatorS as $paginator) {

            /* @var OrderItem $item */
            foreach ($paginator as $item) {
                $addons = [];
                if ($item->getAddons()->count() > 0) {
                    /* @var OrderItemHasAddon $addon */

                    foreach ($item->getAddons() as $addon) {
                        $_temp_arr                        = [];
                        $_temp_arr["label"]               = $addon->getLabel();
                        $_temp_arr["rack_price"]          = $addon->getRackPrice();
                        $_temp_arr["net_price"]           = $addon->getNetPrice();
                        $_temp_arr["tax_price"]           = $addon->getTax();
                        $_temp_arr["total_rack_price"]    = $addon->getRackPrice() + $addon->getTax();
                        $_temp_arr["total_net_price"]     = $addon->getNetPrice() + $addon->getTax();
                        array_push($addons, $_temp_arr);
                    }
                }

                $extras = [];
                if ($item->getExtras()->count() > 0) {
                    /* @var OrderItemHasAddon $addon */

                    foreach ($item->getExtras() as $extra) {
                        $_temp_arr                        = [];
                        $_temp_arr["label"]               = $extra->getLabel();
                        $_temp_arr["rack_price"]          = $extra->getRackPrice();
                        $_temp_arr["net_price"]           = $extra->getNetPrice();
                        $_temp_arr["tax_price"]           = $extra->getTax();
                        $_temp_arr["total_rack_price"]    = $extra->getRackPrice() + $extra->getTax();
                        $_temp_arr["total_net_price"]     = $extra->getNetPrice()  + $extra->getTax();
                        array_push($extras, $_temp_arr);
                    }
                }

                $_item_extra_rows   = count($addons) + count($extras);
                $_extras_addons     = array_merge($addons, $extras);
                $data = array(
                    "date"                  => "",
                    "time"                  => "",
                    "order_id"              => "",
                    "service_type"          => "",
                    "activity"              => "",
                    "passenger_name"        => "",
                    "adults"                => "",
                    "children"              => "",
                    "pickup_area"           => "",
                    "pickup_location"       => "",
                    "dropoff_area"          => "",
                    "dropoff_location"      => "",
                    "extras_or_addons"      => "",
                    "supplier"              => "",
                    "select_vehicle"        => "",
                    "confirmation_status"   => "",
                    "billing_status"        => "",
                    "commission"            => "",
                    "adultRackPrice"        => "",
                    "childRackPrice"        => "",
                    "adultNetPrice"         => "",
                    "childNetPrice"         => "",
                    "taxValue"              => "",
                    "subTotalRack"          => "",
                    "subTotalNet"           => "",
                    "service"               => "",
                );

                unset($fields["editLink"]);

                foreach ($fields as $field => $metaData) {
                   

                    if ($field == "supplier.id") {
                        $displayValue = "";
                        if ($item->getSupplier()) {
                            $value = $item->getSupplier();
                            $displayValue = $value;
                        }
                        $data["supplier"] = trim($displayValue);
                    } else if ($field == "vehicle.id") {
                        $displayValue = "";
                        if ($item->getVehicle()) {
                            $value = $item->getVehicle();
                            $displayValue = $value;
                        }
                        $data["select_vehicle"] = trim($displayValue);
                    } else if ($field == "pickDate") {
                        $displayValue = "Null";
                        if ($item->getPickDate()->format('Y-m-d')) {
                            $value = $item->getPickDate()->format('Y-m-d');
                            $displayValue = $value;
                        }
                        $data["date"] = trim($displayValue);
                    } else if ($field == "pickTime") {
                        $displayValue = "Null";
                        if ($item->getPickTime()){

                            if ($item->getPickTime()->format('H:i')) {
                                $value = $item->getPickTime()->format('H:i');
                                $displayValue = $value;
                            }
                        }
                        $data["time"] = trim($displayValue);
                    } else if ($field == "pickTimeTransport") {
                        continue;
                    } else if ($field == 'supplierCommission') {
                        if ($item->getSupplier()) {
                            $value = $item->getSupplier()->getCommission();
                            $displayValue = $value . '%';
                        } else {
                            $value = 0;
                            $displayValue = 'Null';
                        }
                        $data["commission"] = trim($displayValue);
                    } else if ($field == 'service') {

                        $data["service"] = "";
                        $data["extras_or_addons"] = "";
                    } else if ($field == 'product.vehicle.type') {

                        $data["vehicle_type"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'pickFlightNumber') {

                        $data["flight_no"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'pickAirlineCompany') {

                        $data["airline"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'dropAddress') {

                        $data["dropoff_location"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'dropArea.name') {

                        $data["dropoff_area"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'pickAddress') {

                        $data["pickup_location"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'pickArea.name') {

                        $data["pickup_area"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'childCount') {

                        $data["children"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'adultCount') {

                        $data["adults"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'customerName') {

                        $data["passenger_name"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'booking') {

                        $data["order_id"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'confirmationStatus') {

                        $data["confirmation_status"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'status') {

                        $data["billing_status"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }
                    else if ($field == 'serviceType') {

                        $data["service_type"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }
                    else if ($field == 'activity') {

                        $data["activity"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }
                    else if ($field == 'adultRackPrice') {

                        $data["adultRackPrice"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }else if ($field == 'childRackPrice') {

                        $data["childRackPrice"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }else if ($field == 'adultNetPrice') {

                        $data["adultNetPrice"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }else if ($field == 'childNetPrice') {

                        $data["childNetPrice"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }else if ($field == 'taxValue') {

                        $data["taxValue"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }else if ($field == 'subTotalRack') {

                        $data["subTotalRack"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }else if ($field == 'subTotalNet') {

                        $data["subTotalNet"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }

                }
                $sheet->fromArray($data, NULL, 'A' . $rowToInsert);
                $rowToInsert++;
                if ($_item_extra_rows > 0) {

                    for ($x = 0; $x < $_item_extra_rows; $x++) {
                        $data = array(
                            "date"                  => "",
                            "time"                  => "",
                            "order_id"              => "",
                            "passenger_name"        => "",
                            "adults"                => "",
                            "children"              => "",
                            "vehicle_type"          => "",
                            "pickup_area"           => "",
                            "pickup_location"       => "",
                            "dropoff_area"          => "",
                            "dropoff_location"      => "",
                            "extras_or_addons"      => "",
                            "airline"               => "",
                            "flight_no"             => "",
                            "supplier"              => "",
                            "select_vehicle"        => "",
                            "confirmation_status"   => "",
                            "billing_status"        => "",
                            "commission"            => "",
                            "rack_rate"             => "",
                            "tax"                   => "",
                            "rack_rate_tax"         => "",
                            "supplier_net_rate"     => "",
                            "supplier_tax"          => "",
                            "supplier_net_rate_tax" => "",
                            "service"               => "",
                        );

                        foreach ($fields as $field => $metaData) {
                            if ($field == "supplier.id") {
                                $displayValue = "";
                                if ($item->getSupplier()) {
                                    $value = $item->getSupplier();
                                    $displayValue = $value;
                                }
                                $data["supplier"] = trim($displayValue);
                            } else if ($field == "vehicle.id") {
                                $displayValue = "-";
                                $data["select_vehicle"] = trim($displayValue);
                            } else if ($field == "pickDate") {
                                $displayValue = "Null";
                                if ($item->getPickDate()->format('Y-m-d')) {
                                    $value = $item->getPickDate()->format('Y-m-d');
                                    $displayValue = $value;
                                }
                                $data["date"] = trim($displayValue);
                            } else if ($field == "pickTime") {
                                $displayValue = "Null";
                                if ($item->getPickTime()){
                                    if ($item->getPickTime()->format('H:i')) {
                                        $value = $item->getPickTime()->format('H:i');
                                        $displayValue = $value;
                                    }
                                }
                                $data["time"] = "-";
                            } else if ($field == "pickTimeTransport") {
                                continue;
                            } else if ($field == 'supplierCommission') {
                                if ($item->getSupplier()) {
                                    $value = $item->getSupplier()->getCommission();
                                    $displayValue = $value . '%';
                                } else {
                                    $value = 0;
                                    $displayValue = 'Null';
                                }
                                $data["commission"] = trim($displayValue);
                            } else if ($field == 'service') {

                                $data["service"] = strip_tags($_extras_addons[$x]["label"]);
                                $data["extras_or_addons"] = strip_tags($_extras_addons[$x]["label"]);
                            } else if ($field == 'totalNetPrice') {

                                $data["supplier_net_rate_tax"] = $_extras_addons[$x]["total_net_price"];
                            } else if ($field == 'totalRackPrice') {

                                $data["rack_rate_tax"] = $_extras_addons[$x]["total_rack_price"];
                            } else if ($field == 'totalTax') {

                                $data["tax"] = $_extras_addons[$x]["tax_price"];
                            } else if ($field == 'titleNetPrice') {

                                $data["supplier_net_rate"] = $_extras_addons[$x]["net_price"];
                            } else if ($field == 'titleRackPrice') {

                                $data["rack_rate"] =  $_extras_addons[$x]["rack_price"];
                            } else if ($field == 'product.vehicle.type') {

                                $data["vehicle_type"] = "-";
                            } else if ($field == 'pickFlightNumber') {

                                $data["flight_no"] = "-";
                            } else if ($field == 'pickAirlineCompany') {

                                $data["airline"] = "-";
                            } else if ($field == 'dropAddress') {

                                $data["dropoff_location"] = "-";
                            } else if ($field == 'dropArea.name') {

                                $data["dropoff_area"] = "-";
                            } else if ($field == 'pickAddress') {

                                $data["pickup_location"] = "-";
                            } else if ($field == 'pickArea.name') {

                                $data["pickup_area"] = "-";
                            } else if ($field == 'childCount') {

                                $data["children"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            } else if ($field == 'adultCount') {

                                $data["adults"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            } else if ($field == 'customerName') {

                                $data["passenger_name"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            } else if ($field == 'customerName') {

                                $data["passenger_name"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            } else if ($field == 'booking') {

                                $data["order_id"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            } else if ($field == 'confirmationStatus') {
                                $data["confirmation_status"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            } else if ($field == 'status') {
                                $data["billing_status"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }
                            else if ($field == 'serviceType') {
        
                                $data["service_type"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }
                            else if ($field == 'activity') {
        
                                $data["activity"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }
                            else if ($field == 'adultRackPrice') {
        
                                $data["adultRackPrice"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }else if ($field == 'childRackPrice') {
        
                                $data["childRackPrice"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }else if ($field == 'adultNetPrice') {
        
                                $data["adultNetPrice"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }else if ($field == 'childNetPrice') {
        
                                $data["childNetPrice"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }else if ($field == 'taxValue') {
        
                                $data["taxValue"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }else if ($field == 'subTotalRack') {
        
                                $data["subTotalRack"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }else if ($field == 'subTotalNet') {
        
                                $data["subTotalNet"] = trim(
                                    strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                                );
                            }
                        }
                        $sheet->fromArray($data, NULL, 'A' . $rowToInsert);
                        $rowToInsert++;
                    }
                }
            }
        }

        for ($i = 'A'; $i !=  $spreadsheet->getActiveSheet()->getHighestColumn(); $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
        }

        $writer = new Writer\Xlsx($spreadsheet);

        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="activity_management.xlsx"');
        $response->headers->set('Cache-Control','max-age=0');
        return $response;
    }

    /**
     * activity management send-email-to-driver-about-new-notes
     *
     * @Route("/admin/activity/send-email-to-driver-about-new-notes", name="activity_management_send_email_to_driver_new_notes")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendEmailToDriverNewNotesAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        try {
            $orderItem = $em->getRepository(OrderItem::class)->find($request->request->get('id'));
            if (!$orderItem) {
                return $this->returnSuccessResponse([ 'status' => 'failed', 'message' => 'no order item' ]);
            }
            $additionalDrivers  = $orderItem->getAdditionalDrivers();
            $orders             = [];
            $orderUtils         = $this->get('wicrew.order.utils');
            
            $orders[] = $orderItem;  
            $assignments_to_send = [];
            if ($orderItem->getSupplier()) { 
                $orderUtils->sendDriverEmails($orderItem->getSupplier(), $this, $assignments_to_send, $orders, $type = 'new_note');
            }
             
            if ($additionalDrivers) {                
                foreach ($additionalDrivers as $key => $additionalDriver) { 
                    $assignments_to_send    = [];
                    $assignments_to_send[]  = $additionalDriver;
                    $orders                 = []; 
                    if ($additionalDriver->getDriver()) {
                        $orderUtils->sendDriverEmails($additionalDriver->getDriver(), $this, $assignments_to_send, $orders, $type = 'new_note');
                    }
                }
            } 
        } catch (Throwable $e) { dump($e);die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }
        return $this->returnSuccessResponse([ 'status' => 'success' ]);
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
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if (strpos(strtolower($searchQuery), '#rj') !== false) {
            $searchQuery = str_replace("#RJ", "", $searchQuery);
        }

        if (strpos(strtolower($searchQuery), 'rj') !== false) {
            $searchQuery = str_replace("RJ", "", $searchQuery);
        }


        if (strpos(strtolower($searchQuery), 'unpaid') !== false) {
            $searchQuery = str_replace("unpaid", "0", $searchQuery);
        }

        if (strpos(strtolower($searchQuery), 'paid') !== false) {
            $searchQuery = str_replace("paid", "1", $searchQuery);
        }

        if (strpos(strtolower($searchQuery), 'cancelled') !== false) {
            $searchQuery = str_replace("cancelled", "2", $searchQuery);
        }


        if (strpos(strtolower($searchQuery), 'unassigned') !== false) {
            $searchQuery = str_replace("unassigned", "0", $searchQuery);
        }

        if (strpos(strtolower($searchQuery), 'assigned') !== false) {
            $searchQuery = str_replace("assigned", "1", $searchQuery);
        }

        if (strpos(strtolower($searchQuery), 'approved') !== false) {
            $searchQuery = str_replace("approved", "2", $searchQuery);
        }

        if (strpos(strtolower($searchQuery), 'confirmed') !== false) {
            $searchQuery = str_replace("confirmed", "3", $searchQuery);
        }        
        return $this->get('easyadmin.query_builder')->createSearchQueryBuilder($this->entity, $searchQuery, $sortField, $sortDirection, $dqlFilter);
    }
}