<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\SaleBundle\Entity\OrderItemHasCustomService;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrderItem
 *
 * @ORM\Table(name="OrderItem", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 *     @ORM\Index(name="fk_OrderItem_Order_idx", columns={"order_id"}),
 *     @ORM\Index(name="fk_OrderItem_Product_idx", columns={"product_id"}),
 *     @ORM\Index(name="fk_OrderItem_Activity_idx", columns={"activity_id"}),
 *     @ORM\Index(name="fk_OrderItem_Supplier_idx", columns={"supplier_id"}),
 *     @ORM\Index(name="fk_OrderItem_Area_PickUp_idx", columns={"pick_area_id"}),
 *     @ORM\Index(name="fk_OrderItem_Area_Drop_idx", columns={"drop_area_id"}),
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class OrderItem extends BaseEntity {
    /**
     * Types
     */
    public const TYPE_PRIVATE_SHUTTLE = 'private_shuttle';
    public const TYPE_SHARED_SHUTTLE = 'shared_shuttle';
    public const TYPE_PRIVATE_FLIGHT = 'private_flight';
    public const TYPE_PRIVATE_JBJ = 'private_jbj';
    public const TYPE_SHARED_JBJ = 'shared_jbj';
    public const TYPE_RIDING_JBJ = 'riding_jbj';
    public const TYPE_WATER_TAXI = 'water_taxi';
    public const TYPE_ACTIVITY_REGULAR = 'activity_regular';
    public const TYPE_ACTIVITY_TRANSPORTATION = 'activity_transportation';

    /**
     * Statuses
     */
    public const STATUS_UNPAID = 0;
    public const STATUS_PAID = 1;
    public const STATUS_CANCELLED = 2;
    public const STATUS_REFUNDED = 3;

    /**
     * Statuses
     */
    public const CONFIRMATION_STATUS_UNASSIGNED = 0;
    public const CONFIRMATION_STATUS_ASSIGNED   = 1;
    public const CONFIRMATION_STATUS_APPROVED   = 2;
    public const CONFIRMATION_STATUS_CONFIRMED  = 3;

    /**
     * ID
     *
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Get ID
     *
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return OrderItem
     */
    public function setId(?int $id): OrderItem {
        $this->id = $id;
        return $this;
    }

    /**
     * Order
     *
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Order", inversedBy="items", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $order;

    /**
     * Get order
     *
     * @return Order
     */
    public function getOrder(): Order {
        return $this->order;
    }

    /**
     * Set order
     *
     * @param Order $order
     *
     * @return OrderItem
     */
    public function setOrder(Order $order): OrderItem {
        $this->order = $order;
        return $this;
    }

    /**
     * Customer Notes
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="customer_notes", type="text", length=65535, nullable=true)
     */
    private $customerNotes;

    /**
     * Get customer notes
     *
     * @return string|null
     */
    public function getCustomerNotes() {
        return $this->customerNotes;
    }

    /**
     * Set customer notes
     *
     * @param string|null $customerNotes
     *
     * @return OrderItem
     */
    public function setCustomerNotes($customerNotes): OrderItem {
        $this->customerNotes = $customerNotes;
        return $this;
    }

    /**
     * Product
     *
     * @var Product|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Product", cascade={"persist"})
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $product;

    /**
     * Get product
     *
     * @return Product|null
     */
    public function getProduct(): ?Product {
        return $this->product;
    }

    /**
     * Set product
     *
     * @param Product|null $product
     *
     * @return OrderItem
     */
    public function setProduct(?Product $product): OrderItem {
        $this->product = $product;
        return $this;
    }

    /**
     * Activity
     *
     * @var Activity|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ActivityBundle\Entity\Activity", cascade={"persist"})
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id")
     */
    private $activity;

    /**
     * Get activity
     *
     * @return Activity|null
     */
    public function getActivity(): ?Activity {
        return $this->activity;
    }

    /**
     * Set activity
     *
     * @param Activity|null $activity
     *
     * @return OrderItem
     */
    public function setActivity(?Activity $activity): OrderItem {
        $this->activity = $activity;
        return $this;
    }

    /**
     * Combo items
     *
     * @var Collection|OrderComboChild[]
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderComboChild", mappedBy="orderItem", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $comboChildren;

    /**
     * @return Collection|OrderComboChild[]
     */
    public function getComboChildren(): Collection {
        return $this->comboChildren;
    }

    /**
     * @param Collection|OrderComboChild[] $comboChildren
     *
     * @return OrderItem
     */
    public function setComboChildren(Collection $comboChildren): OrderItem {
        $this->comboChildren = $comboChildren;
        return $this;
    }

    /**
     * Add item
     *
     * @param OrderComboChild $item
     *
     * @return OrderItem
     */
    public function addComboChildren(OrderComboChild $item): OrderItem {
        if (!$this->getComboChildren()->contains($item)) {
            $item->setOrderItem($this);
            $this->getComboChildren()->add($item);
        }

        return $this;
    }

    /**
     * Remove item
     *
     * @param OrderComboChild $item
     *
     * @return OrderItem
     */
    public function removeComboChildren(OrderComboChild $item): OrderItem {
        if ($this->getComboChildren()->contains($item)) {
            if ($item->getOrderItem() === $this) {
                $item->setOrderItem(null);
            }
            $this->getComboChildren()->removeElement($item);
        }

        return $this;
    }

    /**
     * Type
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="type", type="string", length=32, nullable=false)
     */
    private $type;

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return OrderItem
     */
    public function setType(string $type): OrderItem {
        $this->type = $type;
        return $this;
    }

    /**
     * Addons
     *
     * @var Collection|OrderItemHasAddon[]
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItemHasAddon", mappedBy="orderItem", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $addons;

    /**
     * Extras
     *
     * @var Collection|OrderItemHasExtra[]
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItemHasExtra", mappedBy="orderItem", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $extras;

    /**
     * Get addons
     *
     * @return Collection|OrderItemHasAddon[]
     */
    public function getAddons() {
        return $this->addons;
    }

    /**
     * Set addons
     *
     * @param Collection|OrderItemHasAddon[] $addons
     *
     * @return OrderItem
     */
    public function setAddons($addons): OrderItem {
        $this->addons = $addons;
        return $this;
    }

    /**
     * Add addon
     *
     * @param OrderItemHasAddon $addon
     *
     * @return OrderItem
     */
    public function addAddon(OrderItemHasAddon $addon): OrderItem {
        if (!$this->getAddons()->contains($addon)) {
            $addon->setOrderItem($this);
            $this->getAddons()->add($addon);
        }

        return $this;
    }

    /**
     * Addons
     *
     * @var Collection|OrderItemHasCustomService[]
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItemHasCustomService", mappedBy="orderItem", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $customServices;

    /**
     * Get customServices
     *
     * @return Collection|OrderItemHasCustomService[]
     */
    public function getcustomServices() {
        return $this->customServices;
    }
    /**
     * Set addons
     *
     * @param Collection|
     * @return Collection|OrderItemHasCustomService[] $customServices
     *
     * @return OrderItem
     */
    public function setCustomServices($customServices): OrderItem {
        $this->customServices = $customServices;
        return $this;
    }

    /**
     * Add custom service
     *
     * @param OrderItemHasCustomService $customService
     *
     * @return OrderItem
     */
    public function addCustomService(OrderItemHasCustomService $customService): OrderItem {
        $customService->setOrderItem($this);
        $this->getcustomServices()->add($customService);
        return $this;
    }

    /**
     * Remove addon
     *
     * @param OrderItemHasAddon $addon
     *
     * @return OrderItem
     */
    public function removeAddon(OrderItemHasAddon $addon): OrderItem {
        if ($this->getAddons()->contains($addon)) {
            $this->getAddons()->removeElement($addon);
        }

        return $this;
    }

    public function anyAddons(): bool {
        return $this->getAddons() !== null && $this->getAddons()->count() > 0;
    }

    /**
     * Number of adults.
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Type(type = "int")
     *
     * @ORM\Column(name="adult_count", type="integer", nullable=false)
     */
    private $adultCount;

    public function setAdultCount(int $adultCount): OrderItem {
        $this->adultCount = $adultCount;
        return $this;
    }

    public function getAdultCount(): int {
        return $this->adultCount;
    }

    /**
     * Number of children.
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Type(type = "int")
     *
     * @ORM\Column(name="child_count", type="integer", nullable=false)
     */
    private $childCount;

    public function setChildCount(int $childCount): OrderItem {
        $this->childCount = $childCount;
        return $this;
    }

    public function getChildCount(): int {
        return $this->childCount;
    }

    /**
     * Children Ages, each age is separated by a comma
     *
     * @var string
     *
     * @ORM\Column(name="children_ages", type="string", nullable=true)
     */
    private $childrenAges;

    public function setChildrenAges(?string $childrenAges): OrderItem
    {
        $this->childrenAges = $childrenAges;
        return $this;
    }

    /**
     * Get Children Ages
     * 
     * @return null|string 
     */
    public function getChildrenAges(): ?string
    {
        return $this->childrenAges;
    }

    /**
     * Adult Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="adult_rack_price", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $adultRackPrice;

    /**
     * Adult Net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="adult_net_price", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $adultNetPrice;

    /**
     * Child Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="child_rack_price", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $childRackPrice;

    /**
     * Child Net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="child_net_price", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $childNetPrice;

    /**
     * tax value
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="tax_value", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $taxValue;

    /**
     * Date pickup
     *
     * @var DateTime
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="pick_date", type="date", nullable=false)
     */
    private $pickDate;

    /**
     * @return DateTime
     */
    public function getPickDate(): DateTime {
        return $this->pickDate;
    }

    /**
     * @param DateTime $pickDate
     *
     * @return OrderItem
     */
    public function setPickDate(DateTime $pickDate): OrderItem {
        $this->pickDate = $pickDate;
        return $this;
    }

    /**
     * Time pickup
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="pick_time", type="time", nullable=true)
     */
    private $pickTime;


    /**
     * @return DateTime|null
     */
    public function getPickTime(): ?DateTime {
        return $this->pickTime;
    }

    /**
     * @param DateTime|null $pickTime
     *
     * @return OrderItem
     */
    public function setPickTime(?DateTime $pickTime): OrderItem {
        $this->pickTime = $pickTime;
        return $this;
    }

    /**
     * Time pickup choosed by admin
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="pick_time_transport", type="time", nullable=true)
     */
    private $pickTimeTransport;

    /**
     * @return DateTime|null
     */
    public function getPickTimeTransport(): ?DateTime {
        return $this->pickTimeTransport;
    }

    /**
     * @param DateTime|null $pickTimeTransport
     *
     * @return OrderItem
     */
    public function setPickTimeTransport(?DateTime $pickTimeTransport): OrderItem {
        $this->pickTimeTransport = $pickTimeTransport;
        return $this;
    }

    /**
     * Activity tour time
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="tour_time", type="time", nullable=true)
     */
    private $tourTime;

    /**
     * @return DateTime|null
     */
    public function getTourTime(): ?DateTime {
        return $this->tourTime;
    }

    /**
     * @param DateTime|null $tourTime
     *
     * @return OrderItem
     */
    public function setTourTime(?DateTime $tourTime): OrderItem {
        $this->tourTime = $tourTime;
        return $this;
    }

    /**
     * Pick up area
     *
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area", cascade={"persist"})
     * @ORM\JoinColumn(name="pick_area_id", referencedColumnName="id")
     */
    private $pickArea;

    public function getPickArea(): Area {
        return $this->pickArea;
    }

    public function setPickArea(Area $pickArea): OrderItem {
        $this->pickArea = $pickArea;
        return $this;
    }

    /**
     * Pick up address
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="pick_address", type="string", length=255, nullable=true)
     */
    private $pickAddress;

    /**
     * @return string|null
     */
    public function getPickAddress(): ?string {
        return $this->pickAddress;
    }

    /**
     * @param string|null $pickAddress
     *
     * @return OrderItem
     */
    public function setPickAddress(?string $pickAddress): OrderItem {
        $this->pickAddress = $pickAddress;
        return $this;
    }

    /**
     * Pick up address
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="pick_google_place_id", type="string", length=255, nullable=true)
     */
    private $pickGooglePlaceID;

    /**
     * @return string|null
     */
    public function getPickGooglePlaceID(): ?string {
        return $this->pickGooglePlaceID;
    }

    /**
     * @param string|null $pickGooglePlaceID
     *
     * @return OrderItem
     */
    public function setPickGooglePlaceID(?string $pickGooglePlaceID): OrderItem {
        $this->pickGooglePlaceID = $pickGooglePlaceID;
        return $this;
    }

    /**
     * Pick up flight number
     *
     * @var string|null
     *
     * @Assert\Length(max = 16)
     *
     * @ORM\Column(name="pick_flight_number", type="string", length=16, nullable=true)
     */
    private $pickFlightNumber;

    /**
     * @return string|null
     */
    public function getPickFlightNumber(): ?string {
        return $this->pickFlightNumber;
    }

    /**
     * @param string|null $pickFlightNumber
     *
     * @return OrderItem
     */
    public function setPickFlightNumber(?string $pickFlightNumber): OrderItem {
        $this->pickFlightNumber = $pickFlightNumber;
        return $this;
    }

    /**
     * Pick up airline company
     *
     * @var string|null
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="pick_airline_company", type="string", length=128, nullable=true)
     */
    private $pickAirlineCompany;

    /**
     * @return string|null
     */
    public function getPickAirlineCompany(): ?string {
        return $this->pickAirlineCompany;
    }

    /**
     * @param string|null $pickAirlineCompany
     *
     * @return OrderItem
     */
    public function setPickAirlineCompany(?string $pickAirlineCompany): OrderItem {
        $this->pickAirlineCompany = $pickAirlineCompany;
        return $this;
    }

    /**
     * Pick up additional fee
     *
     * @var string|null
     *
     * @ORM\Column(name="pick_additional_fee_net", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $pickAddFeeNet;

    /**
     * @return string|null
     */
    public function getPickAddFeeNet(): ?string {
        return $this->pickAddFeeNet;
    }

    /**
     * @param string|null $pickAddFeeNet
     *
     * @return OrderItem
     */
    public function setPickAddFeeNet(?string $pickAddFeeNet): OrderItem {
        $this->pickAddFeeNet = $pickAddFeeNet;
        return $this;
    }

    /**
     * Pick up additional fee
     *
     * @var string|null
     *
     * @ORM\Column(name="pick_additional_fee_rack", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $pickAddFeeRack;

    /**
     * @return string|null
     */
    public function getPickAddFeeRack(): ?string {
        return $this->pickAddFeeRack;
    }

    /**
     * @param string|null $pickAddFeeRack
     *
     * @return OrderItem
     */
    public function setPickAddFeeRack(?string $pickAddFeeRack): OrderItem {
        $this->pickAddFeeRack = $pickAddFeeRack;
        return $this;
    }

    /**
     * Drop off date
     *
     * @var DateTime
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="drop_date", type="date", nullable=true)
     */
    private $dropDate;

    /**
     * @return DateTime|null
     */
    public function getDropDate(): ?DateTime {
        return $this->dropDate;
    }

    /**
     * @param DateTime $dropDate
     *
     * @return OrderItem
     */
    public function setDropDate(DateTime $dropDate): OrderItem {
        $this->dropDate = $dropDate;
        return $this;
    }

    /**
     * Drop off time
     *
     * @var DateTime
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="drop_time", type="time", nullable=true)
     */
    private $dropTime;

    /**
     * @return DateTime|null
     */
    public function getDropTime(): ?DateTime {
        return $this->dropTime;
    }

    /**
     * @param DateTime $dropTime
     *
     * @return OrderItem
     */
    public function setDropTime(DateTime $dropTime): OrderItem {
        $this->dropTime = $dropTime;
        return $this;
    }

    public function getPickDateAndTime(): ?DateTime {
        if ($this->getPickDate() === null || $this->getPickTime() === null) {
            return null;
        }

        $pickHour = $this->getPickTime()->format('H');
        $pickMinute = $this->getPickTime()->format('i');

        try {
            $pickDateTime = new DateTime($this->getPickDate()->format('Y-m-d'));
        } catch (Exception $e) {
            return null;
        }
        $pickDateTime = $pickDateTime->setTime($pickHour, $pickMinute);
        return $pickDateTime;
    }

    /**
     * Drop off area
     *
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area", cascade={"persist"})
     * @ORM\JoinColumn(name="drop_area_id", referencedColumnName="id")
     */
    private $dropArea;

    public function getDropArea(): Area {
        return $this->dropArea;
    }

    public function setDropArea(Area $dropArea): OrderItem {
        $this->dropArea = $dropArea;
        return $this;
    }

    /**
     * Drop off address
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="drop_address", type="string", length=255, nullable=true)
     */
    private $dropAddress;

    /**
     * @return string|null
     */
    public function getDropAddress(): ?string {
        return $this->dropAddress;
    }

    /**
     * @param string|null $dropAddress
     *
     * @return OrderItem
     */
    public function setDropAddress(?string $dropAddress): OrderItem {
        $this->dropAddress = $dropAddress;
        return $this;
    }

    /**
     * Drop off google place ID
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="drop_google_place_id", type="string", length=255, nullable=true)
     */
    private $dropGooglePlaceID;

    /**
     * @return string|null
     */
    public function getDropGooglePlaceID(): ?string {
        return $this->dropGooglePlaceID;
    }

    /**
     * @param string|null $dropGooglePlaceID
     *
     * @return OrderItem
     */
    public function setDropGooglePlaceID(?string $dropGooglePlaceID): OrderItem {
        $this->dropGooglePlaceID = $dropGooglePlaceID;
        return $this;
    }

    /**
     * Drop off flight number
     *
     * @var string|null
     *
     * @Assert\Length(max = 16)
     *
     * @ORM\Column(name="drop_flight_number", type="string", length=16, nullable=true)
     */
    private $dropFlightNumber;

    /**
     * @return string|null
     */
    public function getDropFlightNumber(): ?string {
        return $this->dropFlightNumber;
    }

    /**
     * @param string|null $dropFlightNumber
     *
     * @return OrderItem
     */
    public function setDropFlightNumber(?string $dropFlightNumber): OrderItem {
        $this->dropFlightNumber = $dropFlightNumber;
        return $this;
    }

    /**
     * Drop off airline company
     *
     * @var string|null
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="drop_airline_company", type="string", length=128, nullable=true)
     */
    private $dropAirlineCompany;

    /**
     * @return string|null
     */
    public function getDropAirlineCompany(): ?string {
        return $this->dropAirlineCompany;
    }

    /**
     * @param string|null $dropAirlineCompany
     *
     * @return OrderItem
     */
    public function setDropAirlineCompany(?string $dropAirlineCompany): OrderItem {
        $this->dropAirlineCompany = $dropAirlineCompany;
        return $this;
    }

    /**
     * Luggage weight
     *
     * @var string|null
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="luggage_weight", nullable=true, type="string", length=128)
     */
    private $luggageWeight;

    /**
     * @return string|null
     */
    public function getLuggageWeight(): ?string {
        return $this->luggageWeight;
    }

    /**
     * @param string|null $luggageWeight
     *
     * @return OrderItem
     */
    public function setLuggageWeight(?string $luggageWeight): OrderItem {
        $this->luggageWeight = $luggageWeight;
        return $this;
    }

    /**
     * Luggage weight
     *
     * @var string|null
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="passenger_weight", nullable=true, type="string", length=128)
     */
    private $passengerWeight;

    /**
     * @return string|null
     */
    public function getPassengerWeight(): ?string {
        return $this->passengerWeight;
    }

    /**
     * @param string|null $passengerWeight
     *
     * @return OrderItem
     */
    public function setPassengerWeight(?string $passengerWeight): OrderItem {
        $this->passengerWeight = $passengerWeight;
        return $this;
    }

    /**
     * Drop off additional fee
     *
     * @var string|null
     *
     * @ORM\Column(name="drop_additional_fee_net", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $dropAddFeeNet;

    /**
     * @return string|null
     */
    public function getDropAddFeeNet(): ?string {
        return $this->dropAddFeeNet;
    }

    /**
     * @param string|null $dropAddFeeNet
     *
     * @return OrderItem
     */
    public function setDropAddFeeNet(?string $dropAddFeeNet): OrderItem {
        $this->dropAddFeeNet = $dropAddFeeNet;
        return $this;
    }

    /**
     * Drop off additional fee
     *
     * @var string|null
     *
     * @ORM\Column(name="drop_additional_fee_rack", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $dropAddFeeRack;

    /**
     * @return string|null
     */
    public function getDropAddFeeRack(): ?string {
        return $this->dropAddFeeRack;
    }

    /**
     * @param string|null $dropAddFeeRack
     *
     * @return OrderItem
     */
    public function setDropAddFeeRack(?string $dropAddFeeRack): OrderItem {
        $this->dropAddFeeRack = $dropAddFeeRack;
        return $this;
    }

    /**
     * Regular time fee
     *
     * @var string|null
     *
     * @ORM\Column(name="regular_time_fee_net", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $regularTimeFeeNet;

    /**
     * @return string|null
     */
    public function getRegularTimeFeeNet(): ?string {
        return $this->regularTimeFeeNet;
    }

    /**
     * @param string|null $regularTimeFeeNet
     *
     * @return OrderItem
     */
    public function setRegularTimeFeeNet(?string $regularTimeFeeNet): OrderItem {
        $this->regularTimeFeeNet = $regularTimeFeeNet;
        return $this;
    }

    /**
     * Regular time fee
     *
     * @var string|null
     *
     * @ORM\Column(name="regular_time_fee_rack", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $regularTimeFeeRack;

    /**
     * @return string|null
     */
    public function getRegularTimeFeeRack(): ?string {
        return $this->regularTimeFeeRack;
    }

    /**
     * @param string|null $regularTimeFeeRack
     *
     * @return OrderItem
     */
    public function setRegularTimeFeeRack(?string $regularTimeFeeRack): OrderItem {
        $this->regularTimeFeeRack = $regularTimeFeeRack;
        return $this;
    }

    /**
     * Regular time fee
     *
     * @var string|null
     *
     * @ORM\Column(name="flight_pick_time_fee_net", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $flightPickTimeFeeNet;

    /**
     * @return string|null
     */
    public function getFlightPickTimeFeeNet(): ?string {
        return $this->flightPickTimeFeeNet;
    }

    /**
     * @param string|null $flightPickTimeFeeNet
     *
     * @return OrderItem
     */
    public function setFlightPickTimeFeeNet(?string $flightPickTimeFeeNet): OrderItem {
        $this->flightPickTimeFeeNet = $flightPickTimeFeeNet;
        return $this;
    }

    /**
     * Regular time fee
     *
     * @var string|null
     *
     * @ORM\Column(name="flight_pick_time_fee_rack", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $flightPickTimeFeeRack;

    /**
     * @return string|null
     */
    public function getFlightPickTimeFeeRack(): ?string {
        return $this->flightPickTimeFeeRack;
    }

    /**
     * @param string|null $flightPickTimeFeeRack
     *
     * @return OrderItem
     */
    public function setFlightPickTimeFeeRack(?string $flightPickTimeFeeRack): OrderItem {
        $this->flightPickTimeFeeRack = $flightPickTimeFeeRack;
        return $this;
    }

    /**
     * Regular time fee
     *
     * @var string|null
     *
     * @ORM\Column(name="flight_drop_time_fee_net", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $flightDropTimeFeeNet;

    /**
     * @return string|null
     */
    public function getFlightDropTimeFeeNet(): ?string {
        return $this->flightDropTimeFeeNet;
    }

    /**
     * @param string|null $flightDropTimeFeeNet
     *
     * @return OrderItem
     */
    public function setFlightDropTimeFeeNet(?string $flightDropTimeFeeNet): OrderItem {
        $this->flightDropTimeFeeNet = $flightDropTimeFeeNet;
        return $this;
    }

    /**
     * Regular time fee
     *
     * @var string|null
     *
     * @ORM\Column(name="flight_drop_time_fee_rack", nullable=true, type="decimal", precision=10, scale=2)
     */
    private $flightDropTimeFeeRack;

    /**
     * @return string|null
     */
    public function getFlightDropTimeFeeRack(): ?string {
        return $this->flightDropTimeFeeRack;
    }

    /**
     * @param string|null $flightDropTimeFeeRack
     *
     * @return OrderItem
     */
    public function setFlightDropTimeFeeRack(?string $flightDropTimeFeeRack): OrderItem {
        $this->flightDropTimeFeeRack = $flightDropTimeFeeRack;
        return $this;
    }

    public function anyTimeRangeFees(): bool {
        return $this->getRegularTimeFeeRack() !== null
            || $this->getFlightPickTimeFeeRack() !== null
            || $this->getFlightDropTimeFeeRack() !== null;
    }

    /**
     * Title price
     *
     * @var string
     *
     * @ORM\Column(name="title_rack_price", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $titleRackPrice;

    /**
     * @return string
     */
    public function getTitleRackPrice(): string {
        return $this->titleRackPrice;
    }

    /**
     * @param string $titleRackPrice
     *
     * @return OrderItem
     */
    public function setTitleRackPrice(string $titleRackPrice): OrderItem {
        $this->titleRackPrice = $titleRackPrice;
        return $this;
    }

    /**
     * Title price
     *
     * @var string
     *
     * @ORM\Column(name="title_net_price", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $titleNetPrice;

    /**
     * @return string
     */
    public function getTitleNetPrice(): string {
        return $this->titleNetPrice;
    }

    /**
     * @param string $titleNetPrice
     *
     * @return OrderItem
     */
    public function setTitleNetPrice(string $titleNetPrice): OrderItem {
        $this->titleNetPrice = $titleNetPrice;
        return $this;
    }

    /**
     * Subtotal
     *
     * @var string
     *
     * @ORM\Column(name="subtotal_rack", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $subtotalRack;

    /**
     * @return string
     */
    public function getSubtotalRack(): string {
        return $this->subtotalRack;
    }

    /**
     * @param string $subtotalRack
     *
     * @return OrderItem
     */
    public function setSubtotalRack(string $subtotalRack): OrderItem {
        $this->subtotalRack = $subtotalRack;
        return $this;
    }

    /**
     * Subtotal
     *
     * @var string
     *
     * @ORM\Column(name="subtotal_net", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $subtotalNet;

    /**
     * @return string
     */
    public function getSubtotalNet(): string {
        return $this->subtotalNet;
    }

    /**
     * @param string $subtotalNet
     *
     * @return OrderItem
     */
    public function setSubtotalNet(string $subtotalNet): OrderItem {
        $this->subtotalNet = $subtotalNet;
        return $this;
    }

    /**
     * Total tax
     *
     * @var string
     *
     * @ORM\Column(name="total_tax", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $totalTax;

    /**
     * @return string
     */
    public function getTotalTax(): string {
        return $this->totalTax;
    }

    /**
     * @param string $totalTax
     *
     * @return OrderItem
     */
    public function setTotalTax(string $totalTax): OrderItem {
        $this->totalTax = $totalTax;
        return $this;
    }

    public function getTitleRackWithTax(): string {
        $rack = new Money($this->getTitleRackPrice());
        $tax = new Money($this->getTotalTax());

        return $rack->add($tax)->__toString();
    }

    public function getTitleNetWithTax(): string {
        $net = new Money($this->getTitleNetPrice());
        $tax = new Money($this->getTotalTax());

        return $net->add($tax)->__toString();
    }

    /**
     * Subtotal
     *
     * @var string
     *
     * @ORM\Column(name="grand_total", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $grandTotal;

    /**
     * Total net
     *
     * @var string
     *
     * @ORM\Column(name="grand_total_net", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $grandTotalNet;

    /**
     * @return string
     */
    public function getGrandTotal(): string {
        return $this->grandTotal;
    }

    /**
     * @param string $grandTotal
     *
     * @return OrderItem
     */
    public function setGrandTotal(string $grandTotal): OrderItem {
        $this->grandTotal = $grandTotal;
        return $this;
    }

    /**
     * Quantity
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Type(type = "int")
     *
     * @ORM\Column(name="qty", type="integer", nullable=false, options={"default"="1","unsigned"=true})
     */
    private $qty = 1;

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQty(): int {
        return $this->qty;
    }

    /**
     * Set quantity
     *
     * @param int $qty
     *
     * @return OrderItem
     */
    public function setQty(int $qty): OrderItem {
        $this->qty = $qty;
        return $this;
    }
 
    /**
     * Supplier
     *
     * @var Partner|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\PartnerBundle\Entity\Partner", cascade={"persist"})
     * @ORM\JoinColumn(name="supplier_id", referencedColumnName="id")
     */
    private $supplier;

    /**
     * Get supplier
     *
     * @return Partner|null
     */
    public function getSupplier(): ?Partner {
        return $this->supplier;
    }

    /**
     * Set supplier
     *
     * @param Partner|null $supplier
     *
     * @return $this
     */
    public function setSupplier(?Partner $supplier): OrderItem {
        $this->supplier = $supplier;
        return $this;
    }

    /**
     * Used to figure out when to send the email to the driver. If email is sent this is null.
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="send_email", type="datetime", nullable=true, options={"default": "CURRENT_TIMESTAMP"})
     */
    private $sendEmail;

    /**
     * @return DateTime|null
     */
    public function getSendEmail(): ?DateTime {
        return $this->sendEmail;
    }

    /**
     * @param DateTime|null $sendEmail
     *
     * @return OrderItem
     */
    public function setSendEmail(?DateTime $sendEmail): OrderItem {
        $this->sendEmail = $sendEmail;
        return $this;
    }

    /**
     * Vehicle
     *
     * @var Vehicle|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\VehicleBundle\Entity\Vehicle", cascade={"persist"})
     * @ORM\JoinColumn(name="vehicle_id", referencedColumnName="id", nullable=true)
     */
    private $vehicle;

    /**
     * Get vehicle
     *
     * @return Vehicle|null
     */
    public function getVehicle(){
        return $this->vehicle;
    }

    /**
     * Set vehicle
     *
     * @param Vehicle|null $vehicle
     *
     * @return OrderItem
     */
    public function setVehicle($vehicle): OrderItem {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * Commission
     *
     * @var string
     *
     * @Assert\PositiveOrZero()
     * @Assert\LessThanOrEqual(100)
     *
     * @ORM\Column(name="supplier_commission", type="float", precision=4, scale=2, nullable=false, options={"default"="0.00", "comment"="Percentage"})
     */
    private $supplierCommission = '0.00';

    /**
     * @return string
     */
    public function getSupplierCommission(): string {
        return $this->supplierCommission;
    }

    /**
     * @param string $supplierCommission
     *
     * @return OrderItem
     */
    public function setSupplierCommission(string $supplierCommission): OrderItem {
        $this->supplierCommission = $supplierCommission;
        return $this;
    }

    /**
     * Driver
     *
     * @var Collection|OrderItemHasDriver[]
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItemHasDriver", mappedBy="orderItem", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    private $additionalDrivers;

    /**
     * @return Collection|OrderItemHasDriver[]
     */
    public function getAdditionalDrivers(): Collection {
        return $this->additionalDrivers;
    }

    /**
     * @param Collection|OrderItemHasDriver[] $additionalDrivers
     *
     * @return OrderItem
     */
    public function setAdditionalDrivers(Collection $additionalDrivers): OrderItem {
        $this->additionalDrivers = $additionalDrivers;
        foreach ($this->additionalDrivers as $additionalDriver) {
            $additionalDriver->setOrderItem($this);
        }

        return $this;
    }

    /**
     * Add item
     *
     * @param OrderItemHasDriver $item
     *
     * @return OrderItem
     */
    public function addAdditionalDrivers(OrderItemHasDriver $item): OrderItem {
        if (!$this->getAdditionalDrivers()->contains($item)) {
            $item->setOrderItem($this);
            $this->getAdditionalDrivers()->add($item);
        }

        return $this;
    }

    /**
     * Remove item
     *
     * @param OrderItemHasDriver $item
     *
     * @return OrderItem
     */
    public function removeAdditionalDrivers(OrderItemHasDriver $item): OrderItem {
        if ($this->getAdditionalDrivers()->contains($item)) {
            if ($item->getOrderItem() === $this) {
                $item->setOrderItem(null);
            }
            $this->getAdditionalDrivers()->removeElement($item);
        }

        return $this;
    }

    /**
     * Status
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Type(type = "int")
     *
     * @ORM\Column(name="status", type="integer", length=1, nullable=false, options={"default"="0","comment"="0 = Pending, 1 = Done, 2 = Cancelled"})
     */
    private $status = self::STATUS_UNPAID;

    /**
     * Status
     *
     * @var int
     *
     *
     * @ORM\Column(name="confirmation", type="integer", length=1, nullable=false, options={"default"="0","unsigned"=true,"comment"="0 = Pending, 1 = Confirmed"})
     */
    private $confirmationStatus = self::CONFIRMATION_STATUS_UNASSIGNED;

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return OrderItem
     */
    public function setStatus(int $status): OrderItem {
        $this->status = $status;
        return $this;
    }

    /**
     * Created at
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * Get created at
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param DateTime $createdAt
     *
     * @return OrderItem
     */
    public function setCreatedAt(DateTime $createdAt): OrderItem {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Modified at
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Get modified at
     *
     * @return DateTime|null
     */
    public function getModifiedAt(): ?DateTime {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param DateTime $modifiedAt
     *
     * @return OrderItem
     */
    public function setModifiedAt(DateTime $modifiedAt): OrderItem {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * History
     *
     * @var ArrayCollection
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderHistory", mappedBy="orderItem", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $history;

    /**
     * Get history
     *
     * @return Collection
     */
    public function getHistory() {
        return $this->history;
    }

    /**
     * Set history
     *
     * @param Collection $history
     *
     * @return OrderItem
     */
    public function setHistory(Collection $history): OrderItem {
        $this->history = $history;
        return $this;
    }

    /**
     * Add history
     *
     * @param OrderHistory $history
     *
     * @return OrderItem
     */
    public function addHistory(OrderHistory $history): OrderItem {
        $history->setOrderItem($this);
        $this->getHistory()->add($history);

        return $this;
    }

    /**
     * Remove history
     *
     * @param OrderHistory $history
     *
     * @return OrderItem
     */
    public function removeHistory(OrderHistory $history): OrderItem {
        foreach ($this->getHistory() as $k => $o) {
            if ($o->getId() == $history->getId()) {
                $this->getHistory()->removeElement($history);
            }
        }

        return $this;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());
        $this->setComboChildren(new ArrayCollection());
        $this->setAddons(new ArrayCollection());
        $this->setHistory(new ArrayCollection());
        $this->comboChildren = new ArrayCollection();
        $this->addons = new ArrayCollection();
        $this->extras = new ArrayCollection();
        $this->additionalDrivers = new ArrayCollection();
        $this->history = new ArrayCollection();
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate() {
        $this->setModifiedAt(new DateTime());
    }

    public function __toString(): string {
        return (string)$this->id;
    }

    public static function resolveProductType(Product $product): string {
        switch ($product->getTransportationType()->getId()) {
            case TransportationType::TYPE_PRIVATE_SHUTTLE:
            {
                return self::TYPE_PRIVATE_SHUTTLE;
            }
            case TransportationType::TYPE_SHARED_SHUTTLE:
            {
                return self::TYPE_SHARED_SHUTTLE;
            }
            case TransportationType::TYPE_AIRPLANE:
            {
                return self::TYPE_PRIVATE_FLIGHT;
            }
            case TransportationType::TYPE_JEEP_BOAT_JEEP_SHARED:
            {
                return self::TYPE_SHARED_JBJ;
            }
            case TransportationType::TYPE_JEEP_BOAT_JEEP_PRIVATE:
            {
                return self::TYPE_PRIVATE_JBJ;
            }
            case TransportationType::TYPE_JEEP_BOAT_JEEP_HORSEBACK:
            {
                return self::TYPE_RIDING_JBJ;
            }
            case TransportationType::TYPE_WATER_TAXI:
            {
                return self::TYPE_WATER_TAXI;
            }
        }
    }

    public function toTranslateString(): string {
        switch ($this->getType()) {
            case self::TYPE_PRIVATE_SHUTTLE:
            {
                return 'order_item.type.option.private_shuttle';
            }
            case self::TYPE_SHARED_SHUTTLE:
            {
                return 'order_item.type.option.shared_shuttle';
            }
            case self::TYPE_PRIVATE_FLIGHT:
            {
                return 'order_item.type.option.private_flight';
            }
            case self::TYPE_PRIVATE_JBJ:
            {
                return 'order_item.type.option.private_jbj';
            }
            case self::TYPE_SHARED_JBJ:
            {
                return 'order_item.type.option.shared_jbj';
            }
            case self::TYPE_RIDING_JBJ:
            {
                return 'order_item.type.option.riding_jbj';
            }
            case self::TYPE_WATER_TAXI:
            {
                return 'order_item.type.option.water_taxi';
            }
            case self::TYPE_ACTIVITY_REGULAR:
            {
                return 'order_item.type.option.activity_regular';
            }
            case self::TYPE_ACTIVITY_TRANSPORTATION:
            {
                return 'order_item.type.option.activity_transportation';
            }
        }

        return '';
    }

    public function addComboChild(OrderComboChild $comboChild): self
    {
        if (!$this->comboChildren->contains($comboChild)) {
            $this->comboChildren[] = $comboChild;
            $comboChild->setOrderItem($this);
        }

        return $this;
    }

    public function removeComboChild(OrderComboChild $comboChild): self
    {
        if ($this->comboChildren->removeElement($comboChild)) {
            // set the owning side to null (unless already changed)
            if ($comboChild->getOrderItem() === $this) {
                $comboChild->setOrderItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderItemHasExtra[]
     */
    public function getExtras(): Collection
    {
        return $this->extras;
    }

    /**
     * Set extras
     *
     * @param Collection|OrderItemHasExtras[] $extras
     *
     * @return OrderItem
     */
    public function setExtras($extras): OrderItem {
        $this->extras = $extras;
        return $this;
    }

    public function addExtra(OrderItemHasExtra $extra): self
    {
        if (!$this->extras->contains($extra)) {
            $this->extras[] = $extra;
            $extra->setOrderItem($this);
        }

        return $this;
    }

    public function removeExtra(OrderItemHasExtra $extra): self
    {
        if ($this->extras->removeElement($extra)) {
            // set the owning side to null (unless already changed)
            if ($extra->getOrderItem() === $this) {
                $extra->setOrderItem(null);
            }
        }

        return $this;
    }

    public function anyExtras(): bool {
        return $this->getExtras() !== null && $this->getExtras()->count() > 0;
    }

    public function addAdditionalDriver(OrderItemHasDriver $additionalDriver): self
    {
        if (!$this->additionalDrivers->contains($additionalDriver)) {
            $this->additionalDrivers[] = $additionalDriver;
            $additionalDriver->setOrderItem($this);
        }

        return $this;
    }

    public function removeAdditionalDriver(OrderItemHasDriver $additionalDriver): self
    {
        if ($this->additionalDrivers->removeElement($additionalDriver)) {
            // set the owning side to null (unless already changed)
            if ($additionalDriver->getOrderItem() === $this) {
                $additionalDriver->setOrderItem(null);
            }
        }

        return $this;
    }

    public function getConfirmationStatus(): ?int
    {
        return $this->confirmationStatus;
    }

    public function setConfirmationStatus(int $confirmationStatus): self
    {
        $this->confirmationStatus = $confirmationStatus;

        return $this;
    }

    public function getAdultRackPrice(): ?string
    {
        return $this->adultRackPrice;
    }

    public function setAdultRackPrice(string $adultRackPrice): self
    {
        $this->adultRackPrice = $adultRackPrice;

        return $this;
    }

    public function getAdultNetPrice(): ?string
    {
        return $this->adultNetPrice;
    }

    public function setAdultNetPrice(string $adultNetPrice): self
    {
        $this->adultNetPrice = $adultNetPrice;

        return $this;
    }

    public function getChildRackPrice(): ?string
    {
        return $this->childRackPrice;
    }

    public function setChildRackPrice(string $childRackPrice): self
    {
        $this->childRackPrice = $childRackPrice;

        return $this;
    }

    public function getChildNetPrice(): ?string
    {
        return $this->childNetPrice;
    }

    public function setChildNetPrice(string $childNetPrice): self
    {
        $this->childNetPrice = $childNetPrice;

        return $this;
    }

    public function getTaxValue(): ?string
    {
        return $this->taxValue;
    }

    public function setTaxValue(string $taxValue): self
    {
        $this->taxValue = $taxValue;

        return $this;
    }

    public function getGrandTotalNet(): ?string
    {
        return $this->grandTotalNet;
    }

    public function setGrandTotalNet(string $grandTotalNet): self
    {
        $this->grandTotalNet = $grandTotalNet;

        return $this;
    }

    /**
     * Get order history total
     *
     * @return Money
     */
    public function getRefundTotal(): Money {
        /* @var Money[] $total */
        $total = new Money();
        
        /* @var OrderHistory $history */
        foreach ($this->getHistory() as $history) { 
            if ($history->getType() == OrderHistory::TYPE_REFUNDED) {
                $total = $total->addStr($history->getAmount());
            }
        } 
        return $total;
    }

    /**
     * Order ID.
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Type(type = "int")
     *
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private $orderId;

    public function setOrderId(int $orderId): OrderItem {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrderId(): int {
        return $this->orderId;
    }


      /**
     * DL Order
     *
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Order", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $dlOrder;

    public function getDlOrder(): Order {
        return $this->dlOrder;
    }

    public function setDlOrder(Order $dlOrder): OrderItem {
        $this->dlOrder = $dlOrder;
        return $this;
    }



    /**
     * Passenger Name
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="passenger_name", nullable=true, type="string", length=255)
     */
    private $passengerName;

    /**
     * @return string|null
     */
    public function getPassengerName(): ?string {
        return $this->passengerName;
    }

    /**
     * @param string|null $passengerName
     *
     * @return OrderItem
     */
    public function setPassengerName(?string $passengerName): OrderItem {
        $this->passengerName = $passengerName;
        return $this;
    }


     /**
     * Activity Type
     *
     * @var string|null
     *
     * @Assert\Length(max = 16)
     *
     * @ORM\Column(name="activityType", nullable=true, type="string", length=16)
     */
    private $activityType;

    /**
     * @return string|null
     */
    public function getActivityType(): ?string {
        return $this->activityType;
    }

    /**
     * @param string|null $activityType
     *
     * @return OrderItem
     */
    public function setActivityType(?string $activityType): OrderItem {
        $this->activityType = $activityType;
        return $this;
    }


     /**
     * SENT TO AMAZEFUL
     *
     * @var int
     *
     *
     * @ORM\Column(name="sent_to_amazeful", nullable=false, type="integer", length=1)
     */
    private $amazefulStatus;

    public function getAmazefulStatus(): ?int {
        return $this->amazefulStatus;
    }

    public function setAmazefulStatus(?int $amazefulStatus): OrderItem {
        $this->amazefulStatus = $amazefulStatus;
        return $this;
    }

    /**
     * Archive
     *
     * @var int
     *
     * @ORM\Column(name="archive", nullable=false, type="integer", length=1)
     */
    private $archiveStatus;

    public function getArchiveStatus(): ?int {
        return $this->archiveStatus;
    }

    public function setArchiveStatus(?int $archiveStatus): OrderItem {
        $this->archiveStatus = $archiveStatus;
        return $this;
    }
}
