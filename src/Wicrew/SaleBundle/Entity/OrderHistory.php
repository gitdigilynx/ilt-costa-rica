<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Entity\User;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrderHistory
 *
 * @ORM\Table(name="OrderHistory", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={@ORM\Index(name="fk_OrderHistory_Order_idx", columns={"order_id"})})
 * @ORM\Entity
 */
class OrderHistory extends BaseEntity {

    /**
     * Types
     */
    public const TYPE_CREATED_ORDER = 1;
    public const TYPE_UPDATED_ORDER = 2;
    public const TYPE_CANCELED_ORDER = 3;
    public const TYPE_ADDED_ITEM = 4;
    public const TYPE_UPDATED_ITEM = 5;
    public const TYPE_CANCELED_ITEM = 6;
    public const TYPE_CHARGED = 7;
    public const TYPE_REFUNDED = 8;

    public const TYPE_ORDER_TEXT = 'order';
    public const TYPE_ORDER_ITEM_TEXT = 'item';
    public const TYPE_PAYMENT_TEXT = 'payment';

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
     * Type
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 1)
     *
     * @ORM\Column(name="type", type="integer", length=1, nullable=false, options={"unsigned"=true})
     */
    private $type;

    /**
     * Order
     *
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Order", inversedBy="history", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $order;

    /**
     * Order item
     *
     * @var OrderItem
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", inversedBy="history", cascade={"persist"})
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $orderItem;

    /**
     * Amount
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $amount = 0.00;

    /**
     * Data
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 16777215)
     *
     * @ORM\Column(name="data", type="text", length=16777215, nullable=false, options={"comment"="JSON encode/Serialization information"})
     */
    private $data = '[]';

    /**
     * Notes
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="notes", type="text", length=65535, nullable=true)
     */
    private $notes;

    /**
     * User
     *
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $user;

    /**
     * Stripe charge ID
     *
     * @var string|null
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="stripe_charge_id", type="string", length=128, nullable=true)
     */
    private $stripeChargeId;

    /**
     * Stripe refund ID
     *
     * @var string|null
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="stripe_refund_id", type="string", length=128, nullable=true)
     */
    private $stripeRefundId;

    /**
     * Stripe statement description
     *
     * @var string|null
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="stripe_statement_description", type="string", length=128, nullable=true)
     */
    private $stripeStatementDescription;

    /**
     * Stripe response status
     *
     * @var string|null
     *
     * @Assert\Length(max = 16)
     *
     * @ORM\Column(name="stripe_response_status", type="string", length=16, nullable=true, options={"comment"="""pending"", ""succeeded"", ""failed"" or ""canceled"""})
     */
    private $stripeResponseStatus;

    /**
     * Created at
     *
     * @var \DateTime
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new \DateTime());
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
     * @return OrderHistory
     */
    public function setId($id): OrderHistory {
        $this->id = $id;
        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param int $type
     *
     * @return OrderHistory
     */
    public function setType($type): OrderHistory {
        $this->type = $type;
        return $this;
    }

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
     * @return OrderHistory
     */
    public function setOrder(Order $order): OrderHistory {
        $this->order = $order;
        return $this;
    }

    /**
     * Get order item
     *
     * @return OrderItem
     */
    public function getOrderItem(): ?OrderItem {
        return $this->orderItem;
    }

    /**
     * Set order item
     *
     * @param OrderItem $orderItem
     *
     * @return OrderHistory
     */
    public function setOrderItem(OrderItem $orderItem): OrderHistory {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * Set amount
     *
     * @param string $amount
     *
     * @return OrderHistory
     */
    public function setAmount($amount): OrderHistory {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData() {
        return $this->decrypt($this->data, self::ENCRYPT_TYPE_SERIALIZE);
    }

    /**
     * Set data
     *
     * @param mixed $data
     *
     * @return OrderHistory
     */
    public function setData($data): OrderHistory {
        $this->data = $this->encrypt($data, self::ENCRYPT_TYPE_SERIALIZE);
        return $this;
    }

    /**
     * Get notes
     *
     * @return string|null
     */
    public function getNotes() {
        return $this->notes;
    }

    /**
     * Set notes
     *
     * @param string|null $notes
     *
     * @return OrderHistory
     */
    public function setNotes($notes): OrderHistory {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Get user
     *
     * @return User|null
     */
    public function getUser(): ?User {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param User|null $user
     *
     * @return OrderHistory
     */
    public function setUser(?User $user): OrderHistory {
        $this->user = $user;
        return $this;
    }

    /**
     * Get Stripe charge ID
     *
     * @return string|null
     */
    public function getStripeChargeId() {
        return $this->stripeChargeId;
    }

    /**
     * Set Stripe charge ID
     *
     * @param string|null $stripeChargeId
     *
     * @return OrderHistory
     */
    public function setStripeChargeId($stripeChargeId): OrderHistory {
        $this->stripeChargeId = $stripeChargeId;
        return $this;
    }

    /**
     * Get Stripe refund ID
     *
     * @return string|null
     */
    public function getStripeRefundId() {
        return $this->stripeRefundId;
    }

    /**
     * Set Stripe refund ID
     *
     * @param string|null $stripeRefundId
     *
     * @return OrderHistory
     */
    public function setStripeRefundId($stripeRefundId): OrderHistory {
        $this->stripeRefundId = $stripeRefundId;
        return $this;
    }

    /**
     * Get Stripe statement description
     *
     * @return string|null
     */
    public function getStripeStatementDescription() {
        return $this->stripeStatementDescription;
    }

    /**
     * Set Stripe statement description
     *
     * @param string|null $stripeStatementDescription
     *
     * @return OrderHistory
     */
    public function setStripeStatementDescription($stripeStatementDescription): OrderHistory {
        $this->stripeStatementDescription = $stripeStatementDescription;
        return $this;
    }

    /**
     * Get Stripe response status
     *
     * @return string|null
     */
    public function getStripeResponseStatus() {
        return $this->stripeResponseStatus;
    }

    /**
     * Set Stripe response status
     *
     * @param string|null $stripeResponseStatus
     *
     * @return OrderHistory
     */
    public function setStripeResponseStatus($stripeResponseStatus): OrderHistory {
        $this->stripeResponseStatus = $stripeResponseStatus;
        return $this;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param \DateTime $createdAt
     *
     * @return OrderHistory
     */
    protected function setCreatedAt(\DateTime $createdAt): OrderHistory {
        $this->createdAt = $createdAt;
        return $this;
    }

}
