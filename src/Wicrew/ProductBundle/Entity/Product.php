<?php

namespace App\Wicrew\ProductBundle\Entity;

use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\CoreBundle\Entity\ProductBasePriceEntity;
use App\Wicrew\SaleBundle\Entity\Tax;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * Product
 *
 * @ORM\Table(name="Product", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 *      @ORM\Index(name="fk_Product_Area_From_idx", columns={"area_from_id"}),
 *      @ORM\Index(name="fk_Product_TransportationType_idx", columns={"transportation_type"}),
 *      @ORM\Index(name="fk_Product_Area_To_idx", columns={"area_to_id"}),
 *      @ORM\Index(name="fk_Product_VehicleType_idx", columns={"vehicle_type_id"})
 * }
 *     )
 * @ORM\Entity(repositoryClass="App\Wicrew\ProductBundle\Repository\ProductRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields={"transportationType", "areaFrom", "areaTo", "vehicleType", "departureTime"}, ignoreNull = false)
 *
 * @Assert\GroupSequenceProvider
 */
class Product extends ProductBasePriceEntity implements GroupSequenceProviderInterface {

    /**
     * Statuses
     */
    const PRODUCT_STATUS_DISABLED = 0;
    const PRODUCT_STATUS_ENABLED = 1;

    /**
     * Trip types
     */
    const TRIP_TYPE_ONE_WAY = 1;
    const TRIP_TYPE_ROUND_TRIP = 2;
    const TRIP_TYPE_MULTI_DESTINATION = 3;

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
     * Transportation type
     *
     * @var TransportationType
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\TransportationType", inversedBy="products", cascade={"persist"})
     * @ORM\JoinColumn(name="transportation_type", referencedColumnName="id")
     */
    private $transportationType;

    /**
     * Area from
     *
     * @var Area
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area", inversedBy="fromProducts", cascade={"persist"})
     * @ORM\JoinColumn(name="area_from_id", referencedColumnName="id")
     */
    private $areaFrom;

    /**
     * Area to
     *
     * @var Area
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area", inversedBy="toProducts", cascade={"persist"})
     * @ORM\JoinColumn(name="area_to_id", referencedColumnName="id")
     */
    private $areaTo;

    /**
     * Vehicle type
     *
     * @var VehicleType
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\VehicleTypeBundle\Entity\VehicleType", inversedBy="products", cascade={"persist"})
     * @ORM\JoinColumn(name="vehicle_type_id", referencedColumnName="id")
     */
    private $vehicleType;

    /**
     * Departure time
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="departure_time", type="time", nullable=true)
     */
    private $departureTime;

    /**
     * Multiple departure times
     *
     * @var Collection|DepartureTime[]
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\ProductBundle\Entity\DepartureTime", mappedBy="product", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $departureTimes;

    /**
     * @return DepartureTime[]|Collection
     */
    public function getDepartureTimes(): Collection {
        return $this->departureTimes;
    }

    /**
     * @param DepartureTime[]|Collection $departureTimes
     *
     * @return Product
     */
    public function setDepartureTimes(Collection $departureTimes) {
        $this->departureTimes = $departureTimes;
        return $this;
    }


    /**
     * Add item
     *
     * @param DepartureTime $item
     *
     * @return Product
     */
    public function addDepartureTime(DepartureTime $item): Product {
        if (!$this->getDepartureTimes()->contains($item)) {
            $item->setProduct($this);
            $this->getDepartureTimes()->add($item);
        }

        return $this;
    }

    /**
     * Remove item
     *
     * @param DepartureTime $item
     *
     * @return Product
     */
    public function removeDepartureTime(DepartureTime $item): Product {
        if ($this->getDepartureTimes()->contains($item)) {
            if ($item->getProduct() === $this) {
                $item->setProduct(null);
            }
            $this->getDepartureTimes()->removeElement($item);
        }

        return $this;
    }

    /**
     * Duration
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="duration", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $duration = '0.00';

    /**
     * km
     *
     * @var string
     * 
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="km", type="string", length=255, nullable=true)
     */
    private $km;

    /**
     * Important notes
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="note", type="text", length=65535, nullable=true)
     */
    private $note;

    /**
     * Enable status
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false, options={"default"="1"})
     */
    private $enabled = true;

    /**
     * Archive status
     *
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean", nullable=false, options={"default"="0"})
     */
    private $archived = false;

    /**
     * Created date
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * Modified date
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Addons
     *
     * @var Collection|Addon[]
     *
     * @ORM\ManyToMany(targetEntity="App\Wicrew\AddonBundle\Entity\Addon", inversedBy="products")
     * @ORM\JoinTable(name="ProductHasAddon",
     *     joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="addon_id", referencedColumnName="id")}
     * 
     * )
     * @ORM\OrderBy({"sortOrder" = "ASC"})
     */
    private $addons;

    /**
     * Extras
     *
     * @var Collection|Extra[]
     *
     * @ORM\ManyToMany(targetEntity="App\Wicrew\AddonBundle\Entity\Extra", inversedBy="products")
     * @ORM\JoinTable(name="ProductHasExtra",
     *     joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="extra_id", referencedColumnName="id")}
     * )
     * @ORM\OrderBy({"sortOrder" = "ASC"})
     */
    private $extras;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());
        $this->setDepartureTimes(new ArrayCollection());
        $this->departureTimes = new ArrayCollection();
        $this->addons = new ArrayCollection();
        $this->extras = new ArrayCollection();
    }

    /**
     * Clone
     */
    public function __clone() {
        $this->setCreatedAt(new DateTime());
        $this->setModifiedAt($this->getCreatedAt());

        parent::__clone();
    }

    /**
     * Get ID
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return Product
     */
    public function setId($id): Product {
        $this->id = $id;
        return $this;
    }

    /**
     * Get transportation type
     *
     * @return TransportationType
     */
    public function getTransportationType() {
        return $this->transportationType;
    }

    /**
     * Set transportation type
     *
     * @param TransportationType $transportationType
     *
     * @return Product
     */
    public function setTransportationType(TransportationType $transportationType): Product {
        $this->transportationType = $transportationType;
        return $this;
    }

    /**
     * Get Area from
     *
     * @return Area
     */
    public function getAreaFrom() {
        return $this->areaFrom;
    }

    /**
     * Set Area from
     *
     * @param Area $areaFrom
     *
     * @return Product
     */
    public function setAreaFrom(Area $areaFrom): Product {
        $this->areaFrom = $areaFrom;
        return $this;
    }

    /**
     * Get Area to
     *
     * @return Area
     */
    public function getAreaTo() {
        return $this->areaTo;
    }

    /**
     * Set Area to
     *
     * @param Area $areaTo
     *
     * @return Product
     */
    public function setAreaTo(Area $areaTo): Product {
        $this->areaTo = $areaTo;
        return $this;
    }

    /**
     * Get vehicle type
     *
     * @return VehicleType
     */
    public function getVehicleType() {
        return $this->vehicleType;
    }

    /**
     * Set vehicle type
     *
     * @param VehicleType $vehicleType
     *
     * @return Product
     */
    public function setVehicleType(VehicleType $vehicleType): Product {
        $this->vehicleType = $vehicleType;
        return $this;
    }

    /**
     * Get departure time
     *
     * @return DateTime|null
     */
    public function getDepartureTime(): ?DateTime {
        return $this->departureTime;
    }

    /**
     * Set departure time
     *
     * @param DateTime|null $departureTime
     *
     * @return Product
     */
    public function setDepartureTime(?DateTime $departureTime): Product {
        $this->departureTime = $departureTime;
        return $this;
    }

    /**
     * Get Duration
     *
     * @return string
     */
    public function getDuration(): string {
        return $this->duration;
    }

    public function getDurationNoTrailingZeroes(): string {
        $number = $this->getDuration();
        $number = rtrim($number, "0");
        $locale_info = localeconv();
        return rtrim($number, $locale_info['decimal_point']);
    }

    /**
     * Set Duration
     *
     * @param string $duration
     *
     * @return Product
     */
    public function setDuration(string $duration): Product {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Get km
     *
     * @return string
     */
    public function getKm(): string {
        return $this->km;
    }
 
    /**
     * Set km
     *
     * @param string $km
     *
     * @return Product
     */
    public function setKm(string $km): Product {
        $this->km = $km;
        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote() {
        return $this->note;
    }

    /**
     * Set note
     *
     * @param string|null $note
     *
     * @return Product
     */
    public function setNote(?string $note): Product {
        $this->note = $note;
        return $this;
    }

    /**
     * Get enabled
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * Set enabled
     *
     * @param bool $enabled
     *
     * @return Product
     */
    public function setEnabled($enabled): Product {
        $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * Get created at
     *
     * @return DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param DateTime $createdAt
     *
     * @return Product
     */
    public function setCreatedAt(DateTime $createdAt): Product {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return DateTime
     */
    public function getModifiedAt() {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param DateTime $modifiedAt
     *
     * @return Product
     */
    public function setModifiedAt(DateTime $modifiedAt): Product {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Get addons
     *
     * @return Collection|Addon[]
     */
    public function getAddons() {
        return $this->addons;
    }

    /**
     * Set addons
     *
     * @param Collection|Addon[] $addons
     *
     * @return Product
     */
    public function setAddons($addons): Product {
        $this->addons = $addons;
        return $this;
    }

    /**
     * Gets triggered only on insert
     *
     * @ORM\PreUpdate
     */
    public function preUpdate() {
        $this->setModifiedAt(new DateTime());
    }

    public function __toString() {
        return $this->getAreaFrom()->getName();
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var bool
     *
     * @ORM\Column(name="regular_pick_enabled", type="boolean", nullable=false, options={"default"="0"})
     */
    private $regularPickEnabled = false;

    /**
     * Get rack price
     *
     * @return bool
     */
    public function getRegularPickEnabled(): bool {
        return $this->regularPickEnabled;
    }

    /**
     * Set rack price
     *
     * @param bool $regularPickEnabled
     *
     * @return Product
     */
    public function setRegularPickEnabled(bool $regularPickEnabled): Product {
        $this->regularPickEnabled = $regularPickEnabled;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank(groups = {"regularPick"})
     *
     * @ORM\Column(name="regular_pick_time_start", type="time", nullable=true)
     */
    private $regularPickTimeStart;

    /**
     * @return DateTime|null
     */
    public function getRegularPickTimeStart(): ?DateTime {
        return $this->regularPickTimeStart;
    }

    /**
     * @param DateTime|null $regularPickTimeStart
     *
     * @return Product
     */
    public function setRegularPickTimeStart(?DateTime $regularPickTimeStart): Product {
        $this->regularPickTimeStart = $regularPickTimeStart;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank(groups = {"regularPick"})
     *
     * @ORM\Column(name="regular_pick_time_end", type="time", nullable=true)
     */
    private $regularPickTimeEnd;

    /**
     * @return DateTime|null
     */
    public function getRegularPickTimeEnd(): ?DateTime {
        return $this->regularPickTimeEnd;
    }

    /**
     * @param DateTime|null $regularPickTimeEnd
     *
     * @return Product
     */
    public function setRegularPickTimeEnd(?DateTime $regularPickTimeEnd): Product {
        $this->regularPickTimeEnd = $regularPickTimeEnd;
        return $this;
    }

    /**
     * Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="regular_pick_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $regularPickRackPrice = 0.00;

    /**
     * Get rack price
     *
     * @return string
     */
    public function getRegularPickRackPrice(): string {
        return $this->regularPickRackPrice;
    }

    /**
     * Set rack price
     *
     * @param string $regularPickRackPrice
     *
     * @return Product
     */
    public function setRegularPickRackPrice(string $regularPickRackPrice): Product {
        $this->regularPickRackPrice = $regularPickRackPrice;
        return $this;
    }

    /**
     * Net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="regular_pick_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $regularPickNetPrice = 0.00;

    /**
     * Get net price
     *
     * @return string
     */
    public function getRegularPickNetPrice(): string {
        return $this->regularPickNetPrice;
    }

    /**
     * Set net price
     *
     * @param string $regularPickNetPrice
     *
     * @return Product
     */
    public function setRegularPickNetPrice(string $regularPickNetPrice): Product {
        $this->regularPickNetPrice = $regularPickNetPrice;
        return $this;
    }

    /**
     * Tax
     *
     * @var Tax|null
     *
     * @Assert\NotBlank(groups = {"regularPick"})
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Tax", cascade={"persist"})
     * @ORM\JoinColumn(name="regular_pick_tax_id", referencedColumnName="id")
     */
    private $regularPickTax;

    /**
     * Get tax
     *
     * @return Tax|null
     */
    public function getRegularPickTax(): ?Tax {
        return $this->regularPickTax;
    }

    /**
     * Set tax
     *
     * @param Tax $regularPickTax
     *
     * @return Product
     */
    public function setRegularPickTax(Tax $regularPickTax): Product {
        $this->regularPickTax = $regularPickTax;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var bool
     *
     * @ORM\Column(name="flight_pick_enabled", type="boolean", nullable=false, options={"default"="0"})
     */
    private $flightPickEnabled = false;

    /**
     * Get rack price
     *
     * @return bool
     */
    public function getFlightPickEnabled(): bool {
        return $this->flightPickEnabled;
    }

    /**
     * Set rack price
     *
     * @param bool $flightPickEnabled
     *
     * @return Product
     */
    public function setFlightPickEnabled(bool $flightPickEnabled): Product {
        $this->flightPickEnabled = $flightPickEnabled;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank(groups = {"flightPick"})
     *
     * @ORM\Column(name="flight_pick_time_start", type="time", nullable=true)
     */
    private $flightPickTimeStart;

    /**
     * @return DateTime|null
     */
    public function getFlightPickTimeStart(): ?DateTime {
        return $this->flightPickTimeStart;
    }

    /**
     * @param DateTime|null $flightPickTimeStart
     *
     * @return Product
     */
    public function setFlightPickTimeStart(?DateTime $flightPickTimeStart): Product {
        $this->flightPickTimeStart = $flightPickTimeStart;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank(groups = {"flightPick"})
     *
     * @ORM\Column(name="flight_pick_time_end", type="time", nullable=true)
     */
    private $flightPickTimeEnd;

    /**
     * @return DateTime|null
     */
    public function getFlightPickTimeEnd(): ?DateTime {
        return $this->flightPickTimeEnd;
    }

    /**
     * @param DateTime|null $flightPickTimeEnd
     *
     * @return Product
     */
    public function setFlightPickTimeEnd(?DateTime $flightPickTimeEnd): Product {
        $this->flightPickTimeEnd = $flightPickTimeEnd;
        return $this;
    }

    /**
     * Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="flight_pick_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $flightPickRackPrice = 0.00;

    /**
     * Get rack price
     *
     * @return string
     */
    public function getFlightPickRackPrice(): string {
        return $this->flightPickRackPrice;
    }

    /**
     * Set rack price
     *
     * @param string $flightPickRackPrice
     *
     * @return Product
     */
    public function setFlightPickRackPrice(string $flightPickRackPrice): Product {
        $this->flightPickRackPrice = $flightPickRackPrice;
        return $this;
    }

    /**
     * Net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="flight_pick_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $flightPickNetPrice = 0.00;

    /**
     * Get net price
     *
     * @return string
     */
    public function getFlightPickNetPrice(): string {
        return $this->flightPickNetPrice;
    }

    /**
     * Set net price
     *
     * @param string $flightPickNetPrice
     *
     * @return Product
     */
    public function setFlightPickNetPrice(string $flightPickNetPrice): Product {
        $this->flightPickNetPrice = $flightPickNetPrice;
        return $this;
    }

    /**
     * Tax
     *
     * @var Tax|null
     *
     * @Assert\NotBlank(groups = {"flightPick"})
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Tax", cascade={"persist"})
     * @ORM\JoinColumn(name="flight_pick_tax_id", referencedColumnName="id")
     */
    private $flightPickTax;

    /**
     * Get tax
     *
     * @return Tax|null
     */
    public function getFlightPickTax(): ?Tax {
        return $this->flightPickTax;
    }

    /**
     * Set tax
     *
     * @param Tax $flightPickTax
     *
     * @return Product
     */
    public function setFlightPickTax(Tax $flightPickTax): Product {
        $this->flightPickTax = $flightPickTax;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var bool
     *
     * @ORM\Column(name="flight_drop_enabled", type="boolean", nullable=false, options={"default"="0"})
     */
    private $flightDropEnabled = false;

    /**
     * Get rack price
     *
     * @return bool
     */
    public function getFlightDropEnabled(): bool {
        return $this->flightDropEnabled;
    }

    /**
     * Set rack price
     *
     * @param bool $flightDropEnabled
     *
     * @return Product
     */
    public function setFlightDropEnabled(bool $flightDropEnabled): Product {
        $this->flightDropEnabled = $flightDropEnabled;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank(groups = {"flightDrop"})
     *
     * @ORM\Column(name="flight_drop_time_start", type="time", nullable=true)
     */
    private $flightDropTimeStart;

    /**
     * @return DateTime|null
     */
    public function getFlightDropTimeStart(): ?DateTime {
        return $this->flightDropTimeStart;
    }

    /**
     * @param DateTime|null $flightDropTimeStart
     *
     * @return Product
     */
    public function setFlightDropTimeStart(?DateTime $flightDropTimeStart): Product {
        $this->flightDropTimeStart = $flightDropTimeStart;
        return $this;
    }

    /**
     * Whether there's a regular time range that introduces a fee
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank(groups = {"flightDrop"})
     *
     * @ORM\Column(name="flight_drop_time_end", type="time", nullable=true)
     */
    private $flightDropTimeEnd;

    /**
     * @return DateTime|null
     */
    public function getFlightDropTimeEnd(): ?DateTime {
        return $this->flightDropTimeEnd;
    }

    /**
     * @param DateTime|null $flightDropTimeEnd
     *
     * @return Product
     */
    public function setFlightDropTimeEnd(?DateTime $flightDropTimeEnd): Product {
        $this->flightDropTimeEnd = $flightDropTimeEnd;
        return $this;
    }

    /**
     * Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="flight_drop_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $flightDropRackPrice = 0.00;

    /**
     * Get rack price
     *
     * @return string
     */
    public function getFlightDropRackPrice(): string {
        return $this->flightDropRackPrice;
    }

    /**
     * Set rack price
     *
     * @param string $flightDropRackPrice
     *
     * @return Product
     */
    public function setFlightDropRackPrice(string $flightDropRackPrice): Product {
        $this->flightDropRackPrice = $flightDropRackPrice;
        return $this;
    }

    /**
     * Net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="flight_drop_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $flightDropNetPrice = 0.00;

    /**
     * Get net price
     *
     * @return string
     */
    public function getFlightDropNetPrice(): string {
        return $this->flightDropNetPrice;
    }

    /**
     * Set net price
     *
     * @param string $flightDropNetPrice
     *
     * @return Product
     */
    public function setFlightDropNetPrice(string $flightDropNetPrice): Product {
        $this->flightDropNetPrice = $flightDropNetPrice;
        return $this;
    }

    /**
     * Tax
     *
     * @var Tax|null
     *
     * @Assert\NotBlank(groups = {"flightDrop"})
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Tax", cascade={"persist"})
     * @ORM\JoinColumn(name="flight_drop_tax_id", referencedColumnName="id")
     */
    private $flightDropTax;

    /**
     * Get tax
     *
     * @return Tax|null
     */
    public function getFlightDropTax(): ?Tax {
        return $this->flightDropTax;
    }

    /**
     * Set tax
     *
     * @param Tax $flightDropTax
     *
     * @return Product
     */
    public function setFlightDropTax(Tax $flightDropTax): Product {
        $this->flightDropTax = $flightDropTax;
        return $this;
    }

    public const GROUP_REGULAR_PICKUP = 'regularPick';
    public const GROUP_FLIGHT_PICKUP = 'flightPick';
    public const GROUP_FLIGHT_DROP_OFF = 'flightDrop';

    public function getGroupSequence() {
        $returnArray = ['Product'];

        if ($this->getRegularPickEnabled()) {
            $returnArray[] = self::GROUP_REGULAR_PICKUP;
        }
        if ($this->getFlightPickEnabled()) {
            $returnArray[] = self::GROUP_FLIGHT_PICKUP;
        }
        if ($this->getFlightDropEnabled()) {
            $returnArray[] = self::GROUP_FLIGHT_DROP_OFF;
        }

        return [$returnArray];
    }

    /**
     * Validate dynamic required fields
     *
     * @param ExecutionContextInterface $context
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context) {
        if ($this->getTransportationType() != null) {
            if ($this->getTransportationType()->getId() === TransportationType::TYPE_SHARED_SHUTTLE) {
                if ($this->getDepartureTimes()->count() < 1 && $this->isEnabled()) {
                    $context->buildViolation('Shared shuttles need at least one departure time or need to be disabled.')
                        ->atPath('departureTimes')
                        ->addViolation();
                }
            }
        }
    }

    public function setDateTimeToToday(DateTime $dt): DateTime {
        $hour = $dt->format('H');
        $minute = $dt->format('i');
        return (new DateTime('today'))->setTime($hour, $minute);
    }

    private function dateWithinRange(DateTime $needle, DateTime $from, DateTime $to): bool {
        if ($from <= $to) {
            return $from <= $needle && $to >= $needle;
        } else {
            return $from <= $needle || $to >= $needle;
        }
    }

    public function inRegularTimeRange(DateTime $needle): bool {
        $from = $this->setDateTimeToToday($this->getRegularPickTimeStart());
        $to = $this->setDateTimeToToday($this->getRegularPickTimeEnd());
        $pickTimeToday = $this->setDateTimeToToday($needle);

        return $this->dateWithinRange($pickTimeToday, $from, $to);
    }

    public function inFlightPickTimeRange(DateTime $needle): bool {
        $from = $this->setDateTimeToToday($this->getFlightPickTimeStart());
        $to = $this->setDateTimeToToday($this->getFlightPickTimeEnd());
        $pickTimeToday = $this->setDateTimeToToday($needle);

        return $this->dateWithinRange($pickTimeToday, $from, $to);
    }

    public function inFlightDropTimeRange(DateTime $needle): bool {
        $from = $this->setDateTimeToToday($this->getFlightDropTimeStart());
        $to = $this->setDateTimeToToday($this->getFlightDropTimeEnd());
        $pickTimeToday = $this->setDateTimeToToday($needle);

        return $this->dateWithinRange($pickTimeToday, $from, $to);
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function addAddon(Addon $addon): self
    {
        if (!$this->addons->contains($addon)) {
            $this->addons[] = $addon;
        }

        return $this;
    }

    public function removeAddon(Addon $addon): self
    {
        $this->addons->removeElement($addon);

        return $this;
    }

    /**
     * @return Collection|Extra[]
     */
    public function getExtras(): Collection
    {
        return $this->extras;
    }

    public function addExtra(Extra $extra): self
    {
        if (!$this->extras->contains($extra)) {
            $this->extras[] = $extra;
        }

        return $this;
    }

    public function removeExtra(Extra $extra): self
    {
        $this->extras->removeElement($extra);

        return $this;
    }

    public function getArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    // november_fixedRackPrice
    // november_fixedNetPrice
    // november_adultRackPrice
    // november_adultNetPrice
    // november_childRackPrice
    // november_childNetPrice
    // november_tax


}

