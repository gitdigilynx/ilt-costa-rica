<?php


namespace App\Wicrew\SaleBundle\Entity;


use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrderItemHasDriver
 *
 * @ORM\Table(name="OrderItemHasDriver", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 *     @ORM\Index(name="fk_OrderItemHasDriver_OrderItem_idx", columns={"order_item_id"}),
 *     @ORM\Index(name="fk_OrderItemHasDriver_Driver_idx", columns={"driver_id"}),
 *     @ORM\Index(name="fk_OrderItemHasDriver_Vehicle_idx", columns={"vehicle_id"}),
 * })
 * @ORM\Entity
 */
class OrderItemHasDriver extends BaseEntity {

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
     * @return int
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return OrderItemHasDriver
     */
    public function setId(int $id): OrderItemHasDriver {
        $this->id = $id;
        return $this;
    }

    /**
     * OrderItem
     *
     * @var OrderItem|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", inversedBy="additionalDrivers", cascade={"persist"})
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id")
     */
    private $orderItem;

    /**
     * @return OrderItem
     */
    public function getOrderItem(): OrderItem {
        return $this->orderItem;
    }

    /**
     * @param OrderItem|null $orderItem
     *
     * @return OrderItemHasDriver
     */
    public function setOrderItem(?OrderItem $orderItem): OrderItemHasDriver {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * Driver
     *
     * @var Partner|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\PartnerBundle\Entity\Partner", cascade={"persist"})
     * @ORM\JoinColumn(name="driver_id", referencedColumnName="id", nullable=true)
     */
    private $driver;

    /**
     * @return Partner|null
     */
    public function getDriver(): ?Partner {
        return $this->driver;
    }

    /**
     * @param Partner|null $driver
     *
     * @return OrderItemHasDriver
     */
    public function setDriver(?Partner $driver): OrderItemHasDriver {
        $this->driver = $driver;
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
    public function getVehicle(): ?Vehicle {
        return $this->vehicle;
    }

    /**
     * Set vehicle
     *
     * @param Vehicle|null $vehicle
     *
     * @return OrderItemHasDriver
     */
    public function setVehicle(?Vehicle $vehicle): OrderItemHasDriver {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * From description
     *
     * @var string|null
     *
     * @Assert\Length(max="255")
     *
     * @ORM\Column(name="from_description", type="string", length=255, nullable=true)
     */
    private $fromDescription;

    /**
     * @return string|null
     */
    public function getFromDescription(): ?string {
        return $this->fromDescription;
    }

    /**
     * @param string|null $fromDescription
     *
     * @return OrderItemHasDriver
     */
    public function setFromDescription(?string $fromDescription): OrderItemHasDriver {
        $this->fromDescription = $fromDescription;
        return $this;
    }

    /**
     * To description
     *
     * @Assert\Length(max="255")
     *
     * @var string|null
     *
     * @ORM\Column(name="to_description", type="string", length=255, nullable=true)
     */
    private $toDescription;

    /**
     * @return string|null
     */
    public function getToDescription(): ?string {
        return $this->toDescription;
    }

    /**
     * @param string|null $toDescription
     *
     * @return OrderItemHasDriver
     */
    public function setToDescription(?string $toDescription): OrderItemHasDriver {
        $this->toDescription = $toDescription;
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
     * @return OrderItemHasDriver
     */
    public function setSendEmail(?DateTime $sendEmail): OrderItemHasDriver {
        $this->sendEmail = $sendEmail;
        return $this;
    }

    /**
     * Rack
     *
     * @var string
     *
     * @ORM\Column(name="rack", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $rack = '0.00';

    /**
     * @return string
     */
    public function getRack(): string {
        return $this->rack;
    }

    /**
     * @param string $rack
     *
     * @return OrderItemHasDriver
     */
    public function setRack(string $rack): OrderItemHasDriver {
        if ($rack == '') $rack = '0.00';
        $this->rack = $rack;
        return $this;
    }

    /**
     * Net
     *
     * @var string
     *
     * @ORM\Column(name="net", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $net = '0.00';

    /**
     * @return string
     */
    public function getNet(): string {
        return $this->net;
    }

    /**
     * @param string $net
     *
     * @return OrderItemHasDriver
     */
    public function setNet(string $net): OrderItemHasDriver {
        if ($net == '') $net = '0.00';
        $this->net = $net;
        return $this;
    }

    /**
     * Created at date.
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime {
        return $this->createdAt;
    }

    /**
     * @param DateTime|null $createdAt
     *
     * @return OrderItemHasDriver
     */
    public function setCreatedAt(?DateTime $createdAt): OrderItemHasDriver {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function __construct() {
        $this->setCreatedAt(new DateTime());
        $this->setSendEmail(new DateTime());
    }


    /**
     * Confirmation Status
     *
     * @var int
     *
     *
     * @ORM\Column(name="confirmation_status", type="integer", length=1, nullable=false, options={"default"="0","unsigned"=true,"0 = Unassigned, 1 = Assigned,2 = Approved, 3 = Confirmed"})
     */
    private $confirmationStatus = self::CONFIRMATION_STATUS_UNASSIGNED;

    /**
     * Get Confirmation Status
     *
     * @return int
     */
    public function getConfirmationStatus(): int {
        return $this->confirmationStatus;
    }

    /**
     * Set Confirmation Status
     *
     * @param int $confirmationStatus
     *
     * @return OrderItem
     */
    public function setConfirmationStatus(int $confirmationStatus): OrderItemHasDriver {
        $this->confirmationStatus = $confirmationStatus;
        return $this;
    }

    /**
     * Date pickup
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="pick_date", type="date", nullable=true)
     */
    private $pickDate;

    /**
     * @return DateTime|null
     */
    public function getPickDate(): ?DateTime {
        return $this->pickDate;
    }

    /**
     * @param DateTime|null $pickDate
     *
     * @return OrderItemHasDriver
     */
    public function setPickDate(DateTime $pickDate): OrderItemHasDriver {
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
     * @return OrderItemHasDriver
     */
    public function setPickTime(?DateTime $pickTime): OrderItemHasDriver {
        $this->pickTime = $pickTime;
        return $this;
    }

}