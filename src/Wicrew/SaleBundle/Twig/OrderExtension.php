<?php

namespace App\Wicrew\SaleBundle\Twig;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderHistory;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use App\Wicrew\SaleBundle\Service\OrderService;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Wicrew\ProductBundle\Entity\Area;

/**
 * OrderExtension
 */
class OrderExtension extends AbstractExtension {

    /**
     * Core utility class
     *
     * @var Utils
     */
    private $utils;

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
     * @return OrderExtension
     */
    public function setUtils(Utils $utils): OrderExtension {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('loadOrder', [$this, 'loadOrder']),
            new TwigFunction('checkHistoryType', [$this, 'checkHistoryType']),
            new TwigFunction('getPartners', [$this, 'getPartners']),
            new TwigFunction('getSuppliers', [$this, 'getSuppliers']),
            new TwigFunction('getDrivers', [$this, 'getDrivers']),
            new TwigFunction('getVehicles', [$this, 'getVehicles']),
            new TwigFunction('getVehicleTypes', [$this, 'getVehicleTypes']),
            new TwigFunction('getOrderItemImage', [$this, 'getOrderItemImage']),
            new TwigFunction('getDriverVehicleImage', [$this, 'getDriverVehicleImage']),
            new TwigFunction('getItemDriverVehicleImage', [$this, 'getItemDriverVehicleImage']),
            new TwigFunction('getOrderItemByOrder', [$this, 'getOrderItemByOrder']),
            new TwigFunction('getAllAreas', [$this, 'getAllAreas']),
        ];
    }

    /**
     * get Order data
     *
     * @param int $oid
     *
     * @return Order
     */
    public function loadOrder($oid) {
        $em = $this->getUtils()->getEntityManager();
        return $em->getRepository('\App\Wicrew\SaleBundle\Entity\Order')->findOneBy(['id' => $oid]);
    }
    public function getOrderItemByOrder($order) {
        $em = $this->getUtils()->getEntityManager();
        $orderItem = $em->getRepository(OrderItem::class)->findOneBy(['order' => $order->getId()]); 
        return $orderItem;
    }

    public function getAllAreas() {
        $em     = $this->getUtils()->getEntityManager();
        $areas  = $em->getRepository(Area::class)->findAll( );
        return $areas;
    }
    /**
     * check Order history type
     *
     * @param int $type
     *
     * @return string
     */
    public function checkHistoryType($type) {
        $typeText = '';
        switch ($type) {
            case OrderHistory::TYPE_CREATED_ORDER:
            case OrderHistory::TYPE_UPDATED_ORDER:
            case OrderHistory::TYPE_CANCELED_ORDER:
                $typeText = 'order';
                break;
            case OrderHistory::TYPE_ADDED_ITEM:
            case OrderHistory::TYPE_UPDATED_ITEM:
            case OrderHistory::TYPE_CANCELED_ITEM:
                $typeText = 'item';
                break;
            case OrderHistory::TYPE_CHARGED:
            case OrderHistory::TYPE_REFUNDED:
                $typeText = 'payment';
                break;
        }

        return $typeText;
    }

    /**
     * Get amount to refund
     *
     * @param Order $order
     *
     * @return float
     */
    public function getAmountToRefund($order) {
        $orderUtil = new OrderService($this->getUtils());
        return $orderUtil->getAmountToRefund($order);
    }

    /**
     * Get all partners
     *
     * @param array $types
     *
     * @return Partner[]
     */
    public function getPartners($types = [Partner::TYPE_PARTNER]) {
        $em = $this->getUtils()->getEntityManager();
        return $em->getRepository(Partner::class)->findBy(['type' => $types], ['bizName' => 'ASC']);
    }

    /**
     *
     * @return Partner[]
     */
    public function getSuppliers(): array {
        $partners = $this->getPartners([ Partner::TYPE_PARTNER, Partner::TYPE_AFFILIATE, Partner::TYPE_TRAVEL_AGENT ]);
        $suppliers = array();
        foreach ($partners as $supplier) {
            $suppliers[$supplier->getId()] = $supplier->getBizName();
        }

        return $suppliers;
    }

    /**
     *
     * @return Partner[]
     */
    public function getAffiliates(): array {
        $partners = $this->getPartners([ Partner::TYPE_PARTNER, Partner::TYPE_AFFILIATE, Partner::TYPE_TRAVEL_AGENT ]);
        $suppliers = array();
        foreach ($partners as $supplier) {
            $suppliers[$supplier->getId()] = $supplier->getBizName();
        }

        return $suppliers;
    }

    /**
     * Get all drivers
     *
     * @return Partner[]
     */
    public function getDrivers(): array {
        $partners = $this->getPartners([ Partner::TYPE_DRIVER, Partner::TYPE_AFFILIATE, Partner::TYPE_SUPPLIER, Partner::TYPE_TRAVEL_AGENT, Partner::TYPE_PARTNER ]);
        $drivers = array();
        foreach ($partners as $driver) {
            $drivers[$driver->getId()] = $driver->getBizName();
        }

        return $drivers;
    }

    /**
     * Get all drivers
     *
     * @return Vehicle[]
     */
    public function getVehicles(): array {
        $em = $this->getUtils()->getEntityManager();
        $vehicleSet = $em->getRepository(Vehicle::class)->findBy([], ['name' => 'ASC']);
        $vehicles = array();

        foreach ($vehicleSet as $vehicle) {
            $vehicles[$vehicle->getId()] = $vehicle->getName();
        }

        return $vehicles;
    }

     /**
     * Get all Vehicle Types
     *
     * @return VehicleType[]
     */
    public function getVehicleTypes(): array {
        $em = $this->getUtils()->getEntityManager();
        $vehicleTypesSet = $em->getRepository(VehicleType::class)->findBy([], ['name' => 'ASC']);
        $vehicleTypes = array();

        foreach ($vehicleTypesSet as $vehicleType) {
            $vehicleTypes[$vehicleType->getId()] = $vehicleType->getName();
        }

        return $vehicleTypes;
    }

    /**
     * @param OrderItem $item
     *
     * @return string|null
     */
    public function getOrderItemImage(OrderItem $item): ?string {
        $orderUtils = $this->getUtils()->getContainer()->get('wicrew.order.utils');
        return $orderUtils->getOrderItemImage($item);
    }

    /**
     * @param OrderItemHasDriver $item
     *
     * @return string|null
     */
    public function getDriverVehicleImage(OrderItemHasDriver $item): ?string {
        $orderUtils = $this->getUtils()->getContainer()->get('wicrew.order.utils');
        return $orderUtils->getDriverVehicleImage($item);
    }

    public function getItemDriverVehicleImage(OrderItem $item): ?string {
        $orderUtils = $this->getUtils()->getContainer()->get('wicrew.order.utils');
        return $orderUtils->getItemDriverVehicleImage($item);
    }
}
