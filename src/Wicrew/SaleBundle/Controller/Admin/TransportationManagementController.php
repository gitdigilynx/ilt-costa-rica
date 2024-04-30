<?php


namespace App\Wicrew\SaleBundle\Controller\Admin;

require_once(dirname(__DIR__, 5) . "/phpSpreadsheet/vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer as Writer;

use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use App\Wicrew\SaleBundle\Service\OrderService;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Knp\Snappy\Pdf;

/**
 * TransportationManagementController
 */
class TransportationManagementController extends BaseAdminController
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

        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.' . $sortField, $sortDirection ?: 'DESC');
        }

        return $queryBuilder;
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
     * Transportation management save
     *
     * @Route("/admin/transportationmanagement/save", name="transportation_management_save")
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
            $orderItem = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('id')]);
            $order = $orderItem->getOrder();

            $newStatus = (int) $request->request->get('status');
            if (is_int($newStatus)) {
                $orderItem->setStatus($newStatus);
            }

            $confirmationStatus = (int) $request->request->get('confirmation_status');
            if (is_int($confirmationStatus)) {
                $orderItem->setConfirmationStatus($confirmationStatus);
            }

            $oldPickTime = $orderItem->getPickTimeTransport();
            $newPickTime = $request->request->get('pickTimeTransport') ? new Datetime($request->request->get('pickTimeTransport')) : '';

            // TODO: refactor this into a function
            // email confirm
            $pickTimeChanged = $oldPickTime != $newPickTime;
            if ($request->request->get('pickTimeTransport')) {
                $orderItem->setPickTimeTransport(new DateTime($request->request->get('pickTimeTransport')));
            }

            if ($pickTimeChanged) {
                $siteEmail = 'info@iltcostarica.com';
                $mailerService = $this->container->get('wicrew.core.mailer');
                $translator = $this->container->get('translator');
                $customerEmail = $order->getEmail();
                $subject_trans_key = 'email.confirm.picktime';
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
                $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.pickuptime.html.twig', [
                    'order'             => $order,
                    'orderItem'         => $orderItem,
                    // 'cardBrand'      => $cardBrand,
                    // 'last4Digits'    => $last4Digits,
                    'logoSrc'           => $logoSrc,
                    'tripadvisorSrc'    => $tripadvisorSrc,
                    'facebookSrc'       => $facebookSrc,
                    'wopitaSrc'         => $wopitaSrc,
                    'imageItemSrcs'     => $imageItemSrcs,
                ]);

                $mailerService->send([
                    'from'      => $siteEmail,
                    'to'        => $customerEmail,
                    'subject'   => $subject,
                    'body'      => $body
                ]);
            }
            // email confirm

            $supplier       = $em->getRepository(Partner::class)->findOneBy(['id' => $request->request->get('supplier_id')]);
            $currentDriver  = $orderItem->getSupplier();
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

            $orderItem->setPickDate(new DateTime($request->request->get('pickDate')));

            $product = $orderItem->getProduct();
            $newPickTime = $product->setDateTimeToToday(new DateTime($request->request->get('pickTime')));
            $oldPickTime = $product->setDateTimeToToday($orderItem->getPickTime());

            if ($newPickTime != $oldPickTime) {
                if ($orderItem->anyTimeRangeFees()) {
                    // Even if the new time is within the same range, the fees themselves could have changed.
                    // So disallow any edits while a fee is present.
                    $messages[] = $this->translator()->trans('transport_management.save.fee_change');
                } else {
                    $affectsFees = $product->getRegularPickEnabled() && $product->inRegularTimeRange($newPickTime);
                    $affectsFees |= $product->getFlightPickEnabled() && $product->inFlightPickTimeRange($newPickTime);
                    $affectsFees |= $product->getFlightDropEnabled() && $product->inFlightDropTimeRange($newPickTime);

                    if ($affectsFees) {
                        $messages[] = $this->translator()->trans('transport_management.save.fee_change');
                    } else {
                        $orderItem->setPickTime($newPickTime);
                    }
                }
            }

            $currentDrivers = $orderItem->getAdditionalDrivers();
            $currentIndices = $currentDrivers->getKeys();
            $additionalDrivers = $this->getRequestDataNoThrow($request, 'additionalDrivers', array());

            $editedOrAddedIndices = [];
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
                if ($sendEmail) {
                    if ($newDriver->getEmail() === null) {
                        $messages[] = $this->translator()->trans('transport_management.save.no_email', ['driverName' => $newDriver->getBizName()]);
                    } else {
                        if (!in_array($newDriver->getId(), $driversThatHaveEmailsSentIDs)) {
                            $driversToSendEmailsTo[] = $newDriver;
                            $driversThatHaveEmailsSentIDs[] = $newDriver->getId();
                        }
                    }
                }
            }

            // Remove any unused indices (I.E. they were removed).
            foreach ($currentIndices as $key) {
                if (!in_array($key, $editedOrAddedIndices)) {
                    $currentDrivers->remove($key);
                }
            }

            $em->persist($orderItem);
            $em->flush();
            $em->commit();
        } catch (Throwable $e) {
            dump($e);
            die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        try {
            $orderUtils = $this->get('wicrew.order.utils');
            foreach ($driversToSendEmailsTo as $driver) {
                $orderUtils->sendDriverEmails($driver, $this);
            }
            $em->flush();
        } catch (Throwable $e) {
            dump($e);
            die;
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['messages' => $messages, 'sentIDs' => $driversThatHaveEmailsSentIDs]);
    }

    /**
     * Transportation management resent-confirmation
     *
     * @Route("/admin/transportationmanagement/resent-confirmation", name="transportation_management_resent_confirmation")
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
     * Transportation management send-email-to-driver
     *
     * @Route("/admin/transportationmanagement/send-email-to-driver", name="transportation_management_send_email_to_driver")
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

            $additionalDrivers = $orderItem->getAdditionalDrivers();

            $orders = [];

            $orderUtils = $this->get('wicrew.order.utils');

            $orders[] = $orderItem;
            $assignments_to_send = [];
            if ($orderItem->getSupplier()) {
                $orderUtils->sendDriverEmails($orderItem->getSupplier(), $this, $assignments_to_send, $orders);
            }

            if ($additionalDrivers) {
                foreach ($additionalDrivers as $key => $additionalDriver) {
                    $assignments_to_send = [];
                    $assignments_to_send[] = $additionalDriver;
                    $orders = [];
                    if ($additionalDriver->getDriver()) {
                        $orderUtils->sendDriverEmails($additionalDriver->getDriver(), $this, $assignments_to_send, $orders);
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
     * Export the report
     */
    public function exportAction()
    {
        $spreadsheet    = new Spreadsheet();
        $sheet          = $spreadsheet->getActiveSheet();
        $rowToInsert    = 1;

        $translator     = $this->container->get('translator');
        $twig           = $this->container->get('twig');
        $twigExtension  = $this->container->get('twig')->getExtension(\EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension::class);

        // Add the header of the CSV file
        $fields = $this->entity['list']['fields'];
        $fields["service"] = array("label" => "Service");
        
        $headers = array("Date", "Time", "Booking #", "Passenger Name", "Adults", "Children", "Vehicle Type", "Pick-up Area", "Pick-up Location", "Drop-off Area", "Drop-off Location", "Extras or Add-ons", "Airline", "Flight #", "Supplier", "Select vehicle", "Confirmation Status", "Billing Status", "Commission (%) Promo Code", "Rack Rate", "Tax", "Rack Rate + Tax", "Supplier Net Rate", "Supplier Tax", "Supplier Net Rate + Tax", "Service");

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
                        if ($item->getPickDate()){

                            if ($item->getPickDate()->format('Y-m-d')) {
                                $value = $item->getPickDate()->format('Y-m-d');
                                $displayValue = $value;
                            }
                        }
                        $data["date"] = trim($displayValue);
                    } else if ($field == "pickTime") {
                        $displayValue = "Null";
                        if($item->getPickTime()){
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
                    } else if ($field == 'totalNetPrice') {

                        $data["supplier_net_rate_tax"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'totalRackPrice') {

                        $data["rack_rate_tax"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'totalTax') {

                        $data["tax"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'titleNetPrice') {

                        $data["supplier_net_rate"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    } else if ($field == 'titleRackPrice') {

                        $data["rack_rate"] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
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
                }
                $data["supplier_tax"] = (int)$data["supplier_net_rate_tax"] - (int)$data["supplier_net_rate"];
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
                                if ($item->getPickDate()){
                                    if ($item->getPickDate()->format('Y-m-d')) {
                                        $value = $item->getPickDate()->format('Y-m-d');
                                        $displayValue = $value;
                                    }
                                }
                                $data["date"] = trim($displayValue);
                            } else if ($field == "pickTime") {
                                $displayValue = "Null";
                                if($item->getPickTime()){
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
        $response->headers->set('Content-Disposition', 'attachment;filename="transport_management.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }



    /**
     * Transportation management bulk save
     *
     * @Route("/admin/transportationmanagement/bulk/save", name="transportation_management_bulk_save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function bulkSaveAction(Request $request)
    {


        $em = $this->getDoctrine()->getManager();
        $messages = [];

        $driversThatHaveEmailsSentIDs       = [];
        $driversToSendEmailsTo              = [];
        try {
            $data_to_proceed                = [];
            $bulk_data                      = json_decode($_POST["data"]);
            $additionalDrivers              = [];
            foreach ($bulk_data as $_bulk_data) {
                $data_id                    = $_bulk_data->id;
                $data_status                = $_bulk_data->status;
                $data_pickTimeTransport     = $_bulk_data->pickTimeTransport;
                $data_supplier_id           = $_bulk_data->{'additionalDrivers[0][driver.id]'};
                $data_supplierCommission    = $_bulk_data->supplierCommission;
                $data_vehicle_id            = $_bulk_data->{'vehicle.id'};
                $data_send_email            = 0;
                $data_pickDate              = $_bulk_data->pickDate;
                $data_pickTime              = $_bulk_data->pickTime;
                array_push(
                    $data_to_proceed,
                    array(
                        "id"                    => $data_id,
                        "status"                => $data_status,
                        "pickTimeTransport"     => $data_pickTimeTransport,
                        "supplier_id"           => $data_supplier_id,
                        "supplierCommission"    => $data_supplierCommission,
                        "vehicle_id"            => $data_vehicle_id,
                        "send_email"            => $data_send_email,
                        "pickDate"              => $data_pickDate,
                        "pickTime"              => $data_pickTime,
                        "additionalDrivers"     => $additionalDrivers,
                    )
                );
            }

            foreach ($data_to_proceed as $_data) {
                $data_id                    = $_data["id"];
                $data_status                = $_data["status"];
                $data_pickTimeTransport     = $_data["pickTimeTransport"];
                $data_supplier_id           = $_data["supplier_id"];
                $data_supplierCommission    = $_data["supplierCommission"];
                $data_vehicle_id            = $_data["vehicle_id"];
                $data_send_email            = $_data["send_email"];
                $data_pickDate              = $_data["pickDate"];
                $data_pickTime              = $_data["pickTime"];
                $additionalDrivers          = $_data["additionalDrivers"];


                $em->beginTransaction();

                /* @var OrderItem $orderItem */
                $orderItem = $em->getRepository(OrderItem::class)->findOneBy(['id' => $data_id]);
                $order = $orderItem->getOrder();

                $newStatus = (int) $data_status;

                if (is_int($newStatus)) {
                    $orderItem->setStatus($newStatus);
                }

                $oldPickTime = $orderItem->getPickTimeTransport();
                $newPickTime = $data_pickTimeTransport ? new Datetime($data_pickTimeTransport) : '';

                // TODO: refactor this into a function email confirm
                $pickTimeChanged = $oldPickTime != $newPickTime;

                if ($pickTimeChanged) {
                    $siteEmail              = 'info@iltcostarica.com';
                    $mailerService          = $this->container->get('wicrew.core.mailer');
                    $translator             = $this->container->get('translator');
                    $customerEmail          = $order->getEmail();
                    $subject_trans_key      = 'email.confirm.picktime';
                    $subject                = $translator->trans($subject_trans_key);
                    $message                = new \Swift_Message();
                    $media_path             = $this->get('kernel')->getProjectDir() . '/public/';
                    $logoSrc                = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/logo.png'));
                    $tripadvisorSrc         = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/tripadvisor.png'));
                    $facebookSrc            = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/facebook.png'));
                    $wopitaSrc              = $message->embed(\Swift_Image::fromPath($media_path . 'bundles/wicrewcore/images/social-media-links/wopita.png'));
                    $imageItemSrcs          = [];
                    $orderUtils             = $this->get('wicrew.order.utils');
                    foreach ($order->getItems() as $key => $item) {
                        $imageItemSrcs[$order->getId()] = $orderUtils->getOrderItemImage($item);
                    }
                    $body = $this->renderTwigToString('WicrewSaleBundle:Email:confirm.order.pickuptime.html.twig', [
                        'order'             => $order,
                        'orderItem'         => $orderItem,
                        // 'cardBrand'      => $cardBrand,
                        // 'last4Digits'    => $last4Digits,
                        'logoSrc'           => $logoSrc,
                        'tripadvisorSrc'    => $tripadvisorSrc,
                        'facebookSrc'       => $facebookSrc,
                        'wopitaSrc'         => $wopitaSrc,
                        'imageItemSrcs'     => $imageItemSrcs,
                    ]);

                    $mailerService->send([
                        'from'      => $siteEmail,
                        'to'        => $customerEmail,
                        'subject'   => $subject,
                        'body'      => $body
                    ]);
                }
                // email confirm

                $supplier = $em->getRepository(Partner::class)->findOneBy(['id' => $data_supplier_id]);
                $currentDriver = $orderItem->getSupplier();
                $orderItem->setSupplier($supplier);
                if ($data_supplierCommission === '0' && $supplier !== null) {
                    $orderItem->setSupplierCommission($supplier->getCommission());
                } else {
                    $orderItem->setSupplierCommission($data_supplierCommission);
                }

                // vehicle
                $vehicle = $em->getRepository(Vehicle::class)->findOneBy(['id' => $data_vehicle_id]);
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
                } elseif ($newDriver === null) {
                    $orderItem->setSendEmail(null);
                }

                $sendEmail = $data_send_email;
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

                $orderItem->setPickDate(new DateTime($data_pickDate));
                if ($data_pickTimeTransport) {
                    $orderItem->setPickTimeTransport(new DateTime($data_pickTimeTransport));
                }

                // SET ORDER CONFIRMATION STATUS TO `ASSIGNED`
                $orderItem->setConfirmationStatus(1);


                $product        = $orderItem->getProduct();
                $newPickTime    = $product->setDateTimeToToday(new DateTime($data_pickTime));
                $oldPickTime    = $product->setDateTimeToToday($orderItem->getPickTime());

                if ($newPickTime != $oldPickTime) {
                    if ($orderItem->anyTimeRangeFees()) {
                        // Even if the new time is within the same range, the fees themselves could have changed.
                        // So disallow any edits while a fee is present.
                        $messages[] = $this->translator()->trans('transport_management.save.fee_change');
                    } else {
                        $affectsFees = $product->getRegularPickEnabled() && $product->inRegularTimeRange($newPickTime);
                        $affectsFees |= $product->getFlightPickEnabled() && $product->inFlightPickTimeRange($newPickTime);
                        $affectsFees |= $product->getFlightDropEnabled() && $product->inFlightDropTimeRange($newPickTime);

                        if ($affectsFees) {
                            $messages[] = $this->translator()->trans('transport_management.save.fee_change');
                        } else {
                            $orderItem->setPickTime($newPickTime);
                        }
                    }
                }

                $currentDrivers = $orderItem->getAdditionalDrivers();
                $currentIndices = $currentDrivers->getKeys();

                $editedOrAddedIndices = [];
                /* @var Partner[] $driversToSendEmailsTo */

                foreach ($additionalDrivers as $index => $addDriver) {
                    $editedOrAddedIndices[] = $index;

                    $newDriver  = $em->getRepository(Partner::class)->findOneBy(['id' => $addDriver['driver.id']]);
                    $vehicle    = $em->getRepository(Vehicle::class)->findOneBy(['id' => $addDriver['vehicle.id']]);
                    $fromDesc   = $addDriver['fromDescription'];
                    $toDesc     = $addDriver['toDescription'];
                    $rack       = $addDriver['rack'];
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
                    $itemHasDriver->setRack($rack);
                    $itemHasDriver->setNet($net);

                    $hasNewDriver = $newDriver !== null && ($oldDriver === null || !$newDriver->equalsID($oldDriver));
                    if ($hasNewDriver) {
                        $itemHasDriver->setSendEmail(new DateTime());
                    } elseif ($newDriver === null) {
                        $itemHasDriver->setSendEmail(null);
                    }

                    $sendEmail = isset($addDriver['sendEmail']);
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
                }

                // Remove any unused indices (I.E. they were removed).
                foreach ($currentIndices as $key) {
                    if (!in_array($key, $editedOrAddedIndices)) {
                        $currentDrivers->remove($key);
                    }
                }

                $em->persist($orderItem);
                $em->flush();
                $em->commit();
            }
        } catch (Throwable $e) {
            dump($e);
            die;
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        try {
            $orderUtils = $this->get('wicrew.order.utils');
            foreach ($driversToSendEmailsTo as $driver) {
                $orderUtils->sendDriverEmails($driver, $this);
            }
            $em->flush();
        } catch (Throwable $e) {
            dump($e);
            die;
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['messages' => $messages, 'sentIDs' => $driversThatHaveEmailsSentIDs]);
    }


    /**
     * Transportation management send-bulk-email-to-driver
     *
     * @Route("/admin/transportationmanagement/send-bulk-email-to-driver", name="transportation_management_send_bulk_email_to_driver")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendBulkEmailToDriverAction(Request $request)
    {


        $em = $this->getDoctrine()->getManager();
        $messages = [];

        try {
            $data_to_proceed                = [];
            $bulk_data                      = json_decode($_POST["data"]);
            $additionalDrivers              = [];
            foreach ($bulk_data as $_bulk_data) {
                $data_id                    = $_bulk_data->id;
                $data_status                = $_bulk_data->status;
                $data_pickTimeTransport     = $_bulk_data->pickTimeTransport;
                $data_supplier_id           = $_bulk_data->{'supplier.id'};
                $data_supplierCommission    = $_bulk_data->supplierCommission;
                $data_vehicle_id            = $_bulk_data->{'vehicle.id'};
                $data_send_email            = 0;
                $data_pickDate              = $_bulk_data->pickDate;
                $data_pickTime              = $_bulk_data->pickTime;
                array_push(
                    $data_to_proceed,
                    array(
                        "id"                    => $data_id,
                        "status"                => $data_status,
                        "pickTimeTransport"     => $data_pickTimeTransport,
                        "supplier_id"           => $data_supplier_id,
                        "supplierCommission"    => $data_supplierCommission,
                        "vehicle_id"            => $data_vehicle_id,
                        "send_email"            => $data_send_email,
                        "pickDate"              => $data_pickDate,
                        "pickTime"              => $data_pickTime,
                        "additionalDrivers"     => $additionalDrivers,
                    )
                );
            }

            $orders                 = [];
            $assignments_to_send    = [];
            $orderItemSupplier;
            foreach ($data_to_proceed as $_data) {
                $data_id                    = $_data["id"];
                $data_status                = $_data["status"];
                $data_pickTimeTransport     = $_data["pickTimeTransport"];
                $data_supplier_id           = $_data["supplier_id"];
                $data_supplierCommission    = $_data["supplierCommission"];
                $data_vehicle_id            = $_data["vehicle_id"];
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

                // $orders[] = $orderItem;
                array_push($orders, $orderItem);
                $orderItemSupplier = $orderItem->getSupplier();

                if ($additionalDrivers) {
                    foreach ($additionalDrivers as $key => $additionalDriver) {
                        $assignments_to_send    = [];
                        $assignments_to_send[]  = $additionalDriver;
                        $orders                 = [];
                        if ($additionalDriver->getDriver()) {
                            $orderUtils->sendDriverEmails($additionalDriver->getDriver(), $this, $assignments_to_send, $orders);
                        }
                    }
                }
            }
            if ($orderItemSupplier) {
                $orderUtils->sendDriverEmails($orderItemSupplier, $this, $assignments_to_send, $orders);
            }
        } catch (Throwable $e) {
            $em->rollback();
            return $this->returnExceptionResponse($e);
        }

        return $this->returnSuccessResponse(['status' => 'success']);
    }

    /**
     * Transportation management send-email-to-driver-about-new-notes
     *
     * @Route("/admin/transportationmanagement/send-email-to-driver-about-new-notes", name="transportation_management_send_email_to_driver_new_notes")
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
