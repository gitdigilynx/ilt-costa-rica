<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Entity\User;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\SaleBundle\Service\DiscountService;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Order
 *
 * @ORM\Table(name="`Order`", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={@ORM\Index(name="fk_Order_Supplier_idx", columns={"supplier_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Order extends BaseEntity {
    /**
     * Payment types
     */
    public const PAYMENT_TYPE_CREDIT_CARD = 1;
    public const PAYMENT_TYPE_CASH = 2;
    public const PAYMENT_TYPE_CHEQUE = 3;

    /**
     * Statuses
     */
    public const STATUS_PENDING = 0;
    public const STATUS_PAID = 1;
    public const STATUS_CANCELLED = 2;

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
     * First name
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=false)
     */
    private $firstName;

    /**
     * Last name
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=false)
     */
    private $lastName;

    /** 
     *
     * @var string
     * 
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="card_brand", type="string", length=255, nullable=true)
     */
    private $cardBrand;

    /** 
     *
     * @var string
     * 
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="last_4_digits", type="string", length=255, nullable=true)
     */
    private $last4Digits;

    /**
     * Email
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max = 50)
     *
     * @ORM\Column(name="email", type="string", length=50, nullable=false)
     */
    private $email;

    /**
     * Telephone
     *
     * @var string|null
     *
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="tel", type="string", length=32, nullable=true)
     */
    private $tel;

    /**
     * WhatsApp
     *
     * @var string|null
     *
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="whatsapp", type="string", length=32, nullable=true)
     */
    private $whatsapp;

    /**
     * Country
     *
     * @var string|null
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="country", type="string", length=32, nullable=true)
     */
    private $country;

    /**
     * Stripe customer ID
     *
     * @var string
     *
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="stripe_customer_id", type="string", length=128, nullable=true)
     */
    private $stripeCustomerId;

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
     * Quote
     *
     * @var bool
     *
     * @ORM\Column(name="quote", type="boolean", nullable=false, options={"default"="0", "comment"="0 = No, 1 = Yes"})
     */
    private $quote = false;

    /**
     * Payment type
     *
     * @var int|null
     *
     * @ORM\Column(name="payment_type", type="integer", length=1, nullable=false, options={"default"="0","unsigned"=true,"comment"="1 = Credit card, 2 = Cash, 3 = Cheque"})
     */
    private $paymentType;

    /**
     * Status
     *
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="status", type="integer", length=1, nullable=false, options={"default"="0","unsigned"=true,"comment"="0 = Pending, 1 = Paid, 2 = Cancelled"})
     */
    private $status = self::STATUS_PENDING;

    /**
     * Feedback email sent
     *
     * @var bool
     *
     * ssss@Assert\NotBlank()
     *
     * @ORM\Column(name="feedback_email_sent", type="boolean", nullable=false, options={"default"="0"})
     */
    private $feedbackEmailSent = false;

    /**
     * @return bool
     */
    public function isFeedbackEmailSent(): bool {
        return $this->feedbackEmailSent;
    }

    /**
     * @param bool $feedbackEmailSent
     *
     * @return Order
     */
    public function setFeedbackEmailSent(bool $feedbackEmailSent): Order {
        $this->feedbackEmailSent = $feedbackEmailSent;
        return $this;
    }

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
     * Created at
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * Modified at
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Order items
     *
     * @var OrderItem[]|Collection
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"pickDate" = "ASC"})
     */
    private $items;

    /**
     * Order items
     *
     * @var DiscountItem[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\DiscountItem", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $discountItems;

    /**
     * History
     *
     * @var ArrayCollection
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderHistory", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    private $history;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());
        $this->setItems(new ArrayCollection());
        $this->setHistory(new ArrayCollection());
        $this->items = new ArrayCollection();
        $this->sortedItems = new ArrayCollection();
        $this->discountItems = new ArrayCollection();
        $this->history = new ArrayCollection();
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
     * @return Order
     */
    public function setId($id): Order {
        $this->id = $id;
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
     * @return Order
     */
    public function setSendEmail(?DateTime $sendEmail): Order {
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
     * @return Order
     */
    public function setVehicle($vehicle): Order {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * Supplier
     *
     * @var Partner
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
    public function setSupplier(?Partner $supplier): Order {
        $this->supplier = $supplier;
        return $this;
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

    /**
     * Set first name
     *
     * @param string $firstName
     *
     * @return Order
     */
    public function setFirstName($firstName): Order {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastName() {
        return $this->lastName;
    }

    /**
     * Set last name
     *
     * @param string $lastName
     *
     * @return Order
     */
    public function setLastName($lastName): Order {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Order
     */
    public function setEmail($email): Order {
        $this->email = $email;
        return $this;
    }

    /**
     * Get telephone
     *
     * @return string|null
     */
    public function getTel() {
        return $this->tel;
    }

    /**
     * Set telephone
     *
     * @param string|null $tel
     *
     * @return Order
     */
    public function setTel($tel): Order {
        $this->tel = $tel;
        return $this;
    }

    /**
     * Get whatsapp
     *
     * @return string|null
     */
    public function getWhatsapp() {
        return $this->whatsapp;
    }

    /**
     * Set whatsapp
     *
     * @param string|null $whatsapp
     *
     * @return Order
     */
    public function setWhatsapp($whatsapp): Order {
        $this->whatsapp = $whatsapp;
        return $this;
    }

    /**
     * Get country
     *
     * @return string|null
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Set country
     *
     * @param string|null $country
     *
     * @return Order
     */
    public function setCountry($country): Order {
        $this->country = $country;
        return $this;
    }

    /**
     * Get Stripe customer ID
     *
     * @return string
     */
    public function getStripeCustomerId() {
        return $this->stripeCustomerId;
    }

    /**
     * Set Stripe customer ID
     *
     * @param string $stripeCustomerId
     *
     * @return Order
     */
    public function setStripeCustomerId($stripeCustomerId): Order {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

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
     * @return Order
     */
    public function setCustomerNotes($customerNotes): Order {
        $this->customerNotes = $customerNotes;
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
     * @return Order
     */
    public function setNotes($notes): Order {
        $date = new DateTime();
        $notes = $this->getNotes() . '<br><br>[' . $date->format('M d Y H:i:s') . ']: ' . $notes;
        $this->notes = $notes;
        return $this;
    }

     /**
     * Set All notes
     *
     * @param string|null $notes
     *
     * @return Order
     */
    public function setAllNotes($notes): Order {
        $notes = $notes;
        $this->notes = $notes;
        return $this;
    }

    /**
     * Get quote
     *
     * @return bool
     */
    public function getQuote() {
        return $this->quote;
    }

    /**
     * Set quote
     *
     * @param bool $quote
     *
     * @return Order
     */
    public function setQuote($quote): Order {
        $this->quote = filter_var($quote, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * Get payment type
     *
     * @return int|null
     */
    public function getPaymentType() {
        return $this->paymentType;
    }

    /**
     * Set payment type
     *
     * @param int|null $paymentType
     *
     * @return Order
     */
    public function setPaymentType(?int $paymentType): Order {
        $this->paymentType = $paymentType;
        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return Order
     */
    public function setStatus($status): Order {
        $this->status = $status;
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
     * @return Order
     */
    public function setUser(?User $user): Order {
        $this->user = $user;
        return $this;
    }

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
     * @return Order
     */
    public function setCreatedAt(DateTime $createdAt): Order {
        $this->createdAt = $createdAt;
        return $this;
    }

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
     * @return Order
     */
    public function setModifiedAt(DateTime $modifiedAt): Order {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Get items
     *
     * @return OrderItem[]|Collection
     */
    public function getItems(): Collection {
        return $this->items;
    }

    /**
     * Set items
     *
     * @param OrderItem[]|Collection $items
     *
     * @return Order
     */
    public function setItems($items): Order {
        $this->items = $items;
        return $this;
    }

    /**
     * Add item
     *
     * @param OrderItem $item
     *
     * @return Order
     */
    public function addItem(OrderItem $item): Order {
        if (!$this->getItems()->contains($item)) {
            $item->setOrder($this);
            $this->getItems()->add($item);
        }

        return $this;
    }

    /**
     * Remove item
     *
     * @param OrderItem $item
     *
     * @return Order
     */
    public function removeItem(OrderItem $item): Order {
        if ($this->getItems()->contains($item)) {
            if ($item->getOrder() === $this) {
                // $item->setOrder(null);
            }
            $this->getItems()->removeElement($item);
        }

        return $this;
    }

    /**
     * Get history
     *
     * @return Collection|OrderHistory[]
     */
    public function getHistory() {
        return $this->history;
    }

    /**
     * Get history
     *
     * @return ArrayCollection
     */
    public function getChargedHistory() {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('type', OrderHistory::TYPE_CHARGED))
            ->andWhere(Criteria::expr()->neq('stripeChargeId', null))
            ->orderBy(['createdAt' => 'ASC']);

        return $this->getHistory()->matching($criteria);
    }

    /**
     * Set history
     *
     * @param ArrayCollection $history
     *
     * @return Order
     */
    public function setHistory(ArrayCollection $history): Order {
        $this->history = $history;
        return $this;
    }

    /**
     * Add history
     *
     * @param OrderHistory $history
     *
     * @return Order
     */
    public function addHistory(OrderHistory $history): Order {
        $history->setOrder($this);
        $this->getHistory()->add($history);

        return $this;
    }

    /**
     * Remove history
     *
     * @param OrderHistory $history
     *
     * @return Order
     */
    public function removeHistory(OrderHistory $history): Order {
        foreach ($this->getHistory() as $k => $o) {
            if ($o->getId() == $history->getId()) {
                $this->getHistory()->removeElement($history);
            }
        }

        return $this;
    }

    /**
     * @return Money[]
     */
    public function getGrandTotal(): array {
        $subRack = new Money();
        $grandTotal = new Money();
        $subNet = new Money();
        $grandTax = new Money();

        $orderItems = $this->getItems();
        /* @var OrderItem $orderItem */
        foreach ($orderItems as $orderItem) {
            if ($orderItem->getStatus() === OrderItem::STATUS_CANCELLED) {
                continue;
            }

            $subRack = $subRack->addStr($orderItem->getSubtotalRack());
            $grandTotal = $grandTotal->addStr($orderItem->getGrandTotal());
            $subNet = $subNet->addStr($orderItem->getSubtotalNet());
            $grandTax = $grandTax->addStr($orderItem->getTotalTax());
        }

        $discountItems = $this->getDiscountItems();
        $discoutService = new DiscountService();
        $discountValues = $discoutService->getDiscountValuesFromOrderItems($orderItems, $discountItems);

        foreach ($discountValues as $key => $discountValue) {
            $grandTotal =  $grandTotal->subtractStr($discountValue['discountRack']);
        }

        return [
            'subRack' => $subRack,
            'subNet' => $subNet,
            'grandTax' => $grandTax,
            'grandTotal' => $grandTotal
        ];
    }

    public function getDiscountValues() {
        $discountItems = $this->getDiscountItems();
        $orderItems = $this->getItems();
        $discoutService = new DiscountService();
        $discountValues = $discoutService->getDiscountValuesFromOrderItems($orderItems, $discountItems);
        return $discountValues;
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate() {
        $this->setModifiedAt(new DateTime());
    }



    /**
     * Customer name
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     */
    private $fullName;

    /**
     * Get customer name
     *
     * @return string
     */
    public function getFullName(): string {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

    /**
     * Get order history total
     *
     * @return Money[]
     */
    public function getOrderHistoryTotal(): array {
        /* @var Money[] $total */
        $total = [];
        $total['additional'] = new Money();
        $total['addPayment'] = new Money();
        $total['refundPayment'] = new Money();
        $total['totalDue'] = new Money();
        $total['amountToRefund'] = new Money();

        /* @var OrderHistory $history */
        foreach ($this->getHistory() as $history) {
            if ($history->getType() != OrderHistory::TYPE_CHARGED && $history->getType() != OrderHistory::TYPE_REFUNDED) {
                $total['additional'] = $total['additional']->addStr($history->getAmount());
            }
            if ($history->getType() == OrderHistory::TYPE_CHARGED) {
                $total['totalDue'] = $total['totalDue']->subtractStr($history->getAmount());
            } else {
                $total['totalDue'] = $total['totalDue']->addStr($history->getAmount());
            }

            if ($history->getType() == OrderHistory::TYPE_CHARGED) {
                $total['addPayment'] = $total['addPayment']->addStr($history->getAmount());
            }

            if ($history->getType() == OrderHistory::TYPE_REFUNDED) {
                $total['refundPayment'] = $total['refundPayment']->addStr($history->getAmount());
            }
        }

        if ($total['totalDue']->lessThanStr('0')) {
            $total['amountToRefund'] = $total['totalDue']->negate();
        }
        return $total;
    }

    public function getFeedbackEmailSent(): ?bool
    {
        return $this->feedbackEmailSent;
    }

    /**
     * @return Collection|DiscountItem[]
     */
    public function getDiscountItems(): Collection
    {
        return $this->discountItems;
    }

    public function addDiscountItem(DiscountItem $discountItem): self
    {
        if (!$this->discountItems->contains($discountItem)) {
            $this->discountItems[] = $discountItem;
            $discountItem->setOrder($this);
        }

        return $this;
    }

    public function removeDiscountItem(DiscountItem $discountItem): self
    {
        if ($this->discountItems->removeElement($discountItem)) {
            // set the owning side to null (unless already changed)
            if ($discountItem->getOrder() === $this) {
                $discountItem->setOrder(null);
            }
        }

        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(?string $cardBrand): self
    {
        $this->cardBrand = $cardBrand;

        return $this;
    }

    public function getLast4Digits(): ?string
    {
        return $this->last4Digits;
    }

    public function setLast4Digits(?string $last4Digits): self
    {
        $this->last4Digits = $last4Digits;

        return $this;
    }
    /**
     * DL Order
     *
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Order", cascade={"persist"})
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    private $dlOrder;

    public function getDlOrder() {
        return $this->dlOrder;
    }

    public function setDlOrder(Order $dlOrder) {
        $this->dlOrder = $dlOrder;
        return $this;
    }

    /**
     * Order items
     *
     * @var OrderItem[]|Collection
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"pickDate" = "ASC"})
     */
    private $sortedItems;


    /**
     * Get items
     *
     * @return OrderItem[]|Collection
     */
    public function getSortedItems(): Collection {
        return $this->sortedItems;
    }

    /**
     * Order items
     *
     * @var OrderItem[]|Collection
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"pickDate" = "DESC"})
     */
    private $sortedItemsDesc;


    /**
     * Get items
     *
     * @return OrderItem[]|Collection
     */
    public function getSortedItemsDesc(): Collection {
        return $this->sortedItemsDesc;
    }


    /**
     * SENT TO AMAZEFUL
     *
     * @var int
     *
     * @ORM\Column(name="sent_to_amazeful", nullable=false, type="integer", length=1)
     */
    private $amazefulStatus;

    public function getAmazefulStatus(): ?int {
        return $this->amazefulStatus;
    }

    public function setAmazefulStatus(?int $amazefulStatus): Order {
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

    public function setArchiveStatus(?int $archiveStatus): Order {
        $this->archiveStatus = $archiveStatus;
        return $this;
    }
}
