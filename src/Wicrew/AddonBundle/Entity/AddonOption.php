<?php

namespace App\Wicrew\AddonBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\Tax;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * AddonOption
 *
 * @ORM\Table(name="AddonOption", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={@ORM\Index(name="fk_AddonOption_Supplier_idx", columns={"supplier_id"}), @ORM\Index(name="fk_AddonOption_Addon_idx", columns={"addon_id"}), @ORM\Index(name="fk_AddonOption_Tax_idx", columns={"tax_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @Assert\GroupSequenceProvider
 */
class AddonOption extends BaseEntity implements GroupSequenceProviderInterface {

    /**
     * Price types
     */
    public const PRICE_TYPE_PER_PERSON = 1;
    public const PRICE_TYPE_FOR_THE_TRIP = 2;

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
     * Addon
     *
     * @var Addon
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\AddonBundle\Entity\Addon", inversedBy="options", cascade={"persist"})
     * @ORM\JoinColumn(name="addon_id", referencedColumnName="id")
     */
    private $addon;

    /**
     * Label
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    private $label;

    /**
     * Rack Price
     *
     * @var float
     *
     * @Assert\NotBlank()
     * @Assert\Type(type = "float")
     *
     * @ORM\Column(name="rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $rackPrice = 0.00;

    /**
     * Net price
     *
     * @var float
     *
     * @Assert\NotBlank()
     * @Assert\Type(type = "float")
     *
     * @ORM\Column(name="net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $netPrice = 0.00;

    /**
     * Tax
     *
     * @var Tax
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Tax", cascade={"persist"})
     * @ORM\JoinColumn(name="tax_id", referencedColumnName="id")
     */
    private $tax;

    /**
     * Price type
     *
     * @var int|null
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="price_type", type="integer", length=1, nullable=true, options={"comment"="1 = Per person, 2 = For the trip"})
     */
    private $priceType;

    /**
     * Position
     *
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false, options={"default"="1","unsigned"=true})
     */
    private $position = 1;

    /**
     * Supplier
     *
     * @var Partner
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\PartnerBundle\Entity\Partner", cascade={"persist"})
     * @ORM\JoinColumn(name="supplier_id", referencedColumnName="id")
     */
    private $supplier;

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
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());
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
     * @return AddonOption
     */
    public function setId($id): AddonOption {
        $this->id = $id;
        return $this;
    }

    /**
     * Get addon
     *
     * @return Addon
     */
    public function getAddon(): Addon {
        return $this->addon;
    }

    /**
     * Set addon
     *
     * @param Addon $addon
     *
     * @return AddonOption
     */
    public function setAddon(Addon $addon): AddonOption {
        $this->addon = $addon;
        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return AddonOption
     */
    public function setLabel($label): AddonOption {
        $this->label = $label;
        return $this;
    }

    /**
     * Get rack price
     *
     * @return float
     */
    public function getRackPrice() {
        return $this->rackPrice;
    }

    /**
     * Set rack price
     *
     * @param float $rackPrice
     *
     * @return AddonOption
     */
    public function setRackPrice($rackPrice): AddonOption {
        $this->rackPrice = $rackPrice;
        return $this;
    }

    /**
     * Get discounted rack price
     *
     * @return string
     */
    public function getRackPriceWithDiscount(): string {
        $discountStr = (string)$this->addon->getDiscountPercentage();
        return Addon::getPriceWithDiscount($this->rackPrice, $discountStr);
    }

    /**
     * Get net price
     *
     * @return float
     */
    public function getNetPrice() {
        return $this->netPrice;
    }

    /**
     * Set net price
     *
     * @param float $netPrice
     *
     * @return AddonOption
     */
    public function setNetPrice($netPrice): AddonOption {
        $this->netPrice = $netPrice;
        return $this;
    }

    /**
     * Get tax
     *
     * @return Tax
     */
    public function getTax() {
        return $this->tax;
    }

    /**
     * Set tax
     *
     * @param Tax $tax
     *
     * @return AddonOption
     */
    public function setTax(Tax $tax): AddonOption {
        $this->tax = $tax;
        return $this;
    }

    /**
     * Get price type
     *
     * @return int|null
     */
    public function getPriceType(): ?int {
        return $this->priceType;
    }

    /**
     * Set price type
     *
     * @param int $priceType
     *
     * @return AddonOption
     */
    public function setPriceType($priceType): AddonOption {
        $this->priceType = $priceType;
        return $this;
    }

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * Set position
     *
     * @param int $position
     *
     * @return AddonOption
     */
    public function setPosition($position): AddonOption {
        $this->position = (int)$position ?: 1;
        return $this;
    }

    /**
     * Get supplier
     *
     * @return Partner
     */
    public function getSupplier() {
        return $this->supplier;
    }

    /**
     * Set supplier
     *
     * @param Partner|null $supplier
     *
     * @return AddonOption
     */
    public function setSupplier(?Partner $supplier): AddonOption {
        $this->supplier = $supplier;
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
     * @return AddonOption
     */
    public function setCreatedAt(DateTime $createdAt): AddonOption {
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
     * @return AddonOption
     */
    public function setModifiedAt(DateTime $modifiedAt): AddonOption {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    public function getTypeLabel(): string {
        if ($this->getPriceType() === \App\Wicrew\AddonBundle\Entity\Addon::PRICE_TYPE_PER_PERSON) {
            return 'booking.addon.perperson';
        } else if ($this->getPriceType() === \App\Wicrew\AddonBundle\Entity\Addon::PRICE_TYPE_FOR_THE_TRIP) {
            return 'booking.addon.pertrip';
        }
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
     * {@inheritDoc}
     */
    public function getGroupSequence() {
        if ($this->getAddon()->getType() !== Addon::TYPE_CHECKBOX) {
            return ['AddonOption'];
        }

        return [];
    }

}
