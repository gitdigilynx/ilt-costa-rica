<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DiscountItem
 *
 * @ORM\Table(name="DiscountItem", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class DiscountItem extends BaseEntity {

    public const TYPE_DISCOUNT_AMOUNT = '1';
    public const TYPE_DISCOUNT_PERCENTAGE = '2';

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
     * Label
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * Description
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * code
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=false)
     */
    private $code;

    /**
     * quantity per user
     *
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="quantity_per_user", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $quantityPerUser;

    /**
     * used number
     *
     * @var int
     *
     *
     * @ORM\Column(name="used_number", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $usedNumber = 0;

    /**
     * Amount
     *
     * @var string|null
     *
     *
     * @ORM\Column(name="reduction_amount", type="decimal", precision=8, scale=2, nullable=true, options={"comment"="Amount"})
     */
    private $reductionAmount;

    /**
     * Percentage
     *
     * @var string|null
     *
     *
     * @ORM\Column(name="reduction_percentage", type="decimal", precision=8, scale=2, nullable=true, options={"comment"="Percentage"})
     */
    private $reductionPercentage;

    /**
     * Type of discount
     *
     * @var string
     *
     * @ORM\Column(name="type_discount", type="string", length=16, nullable=false, options={"comment"="Mixed with comma separator: 1 = amount, 2 = Percentage"})
     */
    private $typeDiscount;

    /**
     * Created at
     *
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Order
     *
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Order", inversedBy="discountItems", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $order;

    /**
     * Get type of discount
     *
     * @return array
     */
    public function getTypeDiscount() {
        return $this->typeDiscount;
    }

    /**
     * Set type of discount
     *
     * @param array $typeDiscount
     *
     * @return DiscountItem
     */
    public function setTypeDiscount($typeDiscount): DiscountItem {
        $this->typeDiscount = $typeDiscount;
        return $this;
    }

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
     * @return DiscountItem
     */
    public function setId($id): DiscountItem {
        $this->id = $id;
        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * Set amoun
     *
     * @param float $amount
     *
     * @return DiscountItem
     */
    public function setAmount($amount): DiscountItem {
        $this->amount = $amount;
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
     * @return DiscountItem
     */
    public function setCreatedAt(\DateTime $createdAt): DiscountItem {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return \DateTime|null
     */
    public function getModifiedAt(): ?\DateTime {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param \DateTime $modifiedAt
     *
     * @return DiscountItem
     */
    public function setModifiedAt(\DateTime $modifiedAt): DiscountItem {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $args) {
        $this->setModifiedAt(new \DateTime());
    }

    /**
     * {@inheritDoc}
     */
    public function __toString() {
        return $this->getLabel();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getQuantityPerUser(): ?int
    {
        return $this->quantityPerUser;
    }

    public function setQuantityPerUser(int $quantityPerUser): self
    {
        $this->quantityPerUser = $quantityPerUser;

        return $this;
    }

    public function getReductionAmount(): ?string
    {
        return $this->reductionAmount;
    }

    public function setReductionAmount(?string $reductionAmount): self
    {
        $this->reductionAmount = $reductionAmount;

        return $this;
    }

    public function getReductionPercentage(): ?string
    {
        return $this->reductionPercentage;
    }

    public function setReductionPercentage(?string $reductionPercentage): self
    {
        $this->reductionPercentage = $reductionPercentage;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUsedNumber(): ?int
    {
        return $this->usedNumber;
    }

    public function setUsedNumber(int $usedNumber): self
    {
        $this->usedNumber = $usedNumber;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

}
