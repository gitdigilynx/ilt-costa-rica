<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\AddonOption;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use App\Wicrew\PartnerBundle\Entity\Partner;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrderItemHasAddon
 *
 * @ORM\Table(name="OrderItemHasAddon", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 *     @ORM\Index(name="fk_OrderItemHasAddon_Addon_idx", columns={"addon_id"}),
 *     @ORM\Index(name="fk_OrderItemHasAddon_AddonOption_idx", columns={"addon_option_id"}),
 *     @ORM\Index(name="fk_OrderItemHasAddon_OrderItem_idx", columns={"order_item_id"})
 * })
 * @ORM\Entity
 */
class OrderItemHasAddon extends BaseEntity {
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
     * Order item
     *
     * @var OrderItem
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", inversedBy="addons", cascade={"persist"})
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $orderItem;

    /**
     * Addon
     *
     * @var Addon
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\AddonBundle\Entity\Addon", cascade={"persist"})
     * @ORM\JoinColumn(name="addon_id", referencedColumnName="id")
     */
    private $addon;

    /**
     * Addon option
     *
     * @var AddonOption|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\AddonBundle\Entity\AddonOption", cascade={"persist"})
     * @ORM\JoinColumn(name="addon_option_id", referencedColumnName="id")
     */
    private $addonOption;

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
     * addonTitle
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="addonTitle", type="string", length=255, nullable=false)
     */
    private $addonTitle;

    /**
     * Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="rack_price", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $rackPrice;

    /**
     * Net price
     *
     * @var float
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="net_price", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $netPrice;

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
     * Extra transportation
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="extra_transportation", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $extraTransportation;

    /**
     * Price type
     *
     * @var int|null
     *
     * @Assert\Length(max = 1)
     *
     * @ORM\Column(name="price_type", type="integer", length=1, nullable=true, options={"unsigned"=true,"comment"="1 = Per person, 2 = For the trip"})
     */
    private $priceType;

    /**
     * Adult quantity
     *
     * @var int|null
     *
     * @ORM\Column(name="adult_quantity", type="integer", length=1, nullable=true)
     */
    private $adultQuantity;

    /**
     * Child quantity
     *
     * @var int|null
     *
     * @ORM\Column(name="child_quantity", type="integer", length=1, nullable=true)
     */
    private $childQuantity;

    /**
     * Extra transportation quantity
     *
     * @var int|null
     *
     * @ORM\Column(name="extra_transportation_quantity", type="integer", length=1, nullable=true)
     */
    private $extraTransportationQuantity;

    /**
     * Tax
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="tax", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $tax = 0.00;

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
     * @return OrderItemHasAddon
     */
    public function setId($id): OrderItemHasAddon {
        $this->id = $id;
        return $this;
    }

    /**
     * Get order Item
     *
     * @return OrderItem
     */
    public function getOrderItem(): OrderItem {
        return $this->orderItem;
    }

    /**
     * Set order item
     *
     * @param OrderItem $orderItem
     *
     * @return OrderItemHasAddon
     */
    public function setOrderItem(OrderItem $orderItem): OrderItemHasAddon {
        $this->orderItem = $orderItem;
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
     * @return OrderItemHasAddon
     */
    public function setAddon(Addon $addon): OrderItemHasAddon {
        $this->addon = $addon;
        if (!$this->getAddonOption() && count($addon->getOptions()) == 0) {
            $this->setLabel($addon->getLabel()); 
            $this->setRackPrice($addon->getRackPrice());
            $this->setNetPrice($addon->getNetPrice());
            $this->setPriceType($addon->getPriceType());
            $this->setTax( 0.13 * $addon->getRackPrice() );
        }
        return $this;
    }

    /**
     * Get addon option
     *
     * @return AddonOption|null
     */
    public function getAddonOption(): ?AddonOption {
        return $this->addonOption;
    }

    /**
     * Set addon option
     *
     * @param AddonOption|null $addonOption
     *
     * @return OrderItemHasAddon
     */
    public function setAddonOption(?AddonOption $addonOption): OrderItemHasAddon {
        $this->addonOption = $addonOption;
        $this->setLabel($addonOption->getLabel());
        $this->setRackPrice($addonOption->getRackPrice());
        $this->setNetPrice($addonOption->getNetPrice());
        $this->setPriceType($addonOption->getPriceType());
        $this->setTax(  0.13 * $addonOption->getRackPrice()  );
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
     * @return OrderItemHasAddon
     */
    public function setLabel($label): OrderItemHasAddon {
        $this->label = $label;
        return $this;
    }

    /**
     * Get addontitle
     *
     * @return string
     */
    public function getAddonTitle() {
        return $this->addonTitle;
    }

    /**
     * Set addonTitle
     *
     * @param string $addonTitle
     *
     * @return OrderItemHasAddon
     */
    public function setAddonTitle($addonTitle): OrderItemHasAddon {
        $this->addonTitle = $addonTitle;
        return $this;
    }

    /**
     * Get rack price
     *
     * @return string
     */
    public function getRackPrice() {
        return $this->rackPrice;
    }

    /**
     * Set rack price
     *
     * @param string $rackPrice
     *
     * @return OrderItemHasAddon
     */
    public function setRackPrice($rackPrice): OrderItemHasAddon {
        $this->rackPrice = $rackPrice;
        return $this;
    }

    /**
     * Get net price
     *
     * @return string
     */
    public function getNetPrice() {
        return $this->netPrice;
    }

    /**
     * Set net price
     *
     * @param string $netPrice
     *
     * @return OrderItemHasAddon
     */
    public function setNetPrice($netPrice): OrderItemHasAddon {
        $this->netPrice = $netPrice;
        return $this;
    }

    /**
     * Get price type
     *
     * @return int
     */
    public function getPriceType() {
        return $this->priceType;
    }

    /**
     * Set price type
     *
     * @param int $priceType
     *
     * @return OrderItemHasAddon
     */
    public function setPriceType($priceType): OrderItemHasAddon {
        $this->priceType = $priceType;
        return $this;
    }

    /**
     * Get tax
     *
     * @return string
     */
    public function getTax() {
        return $this->tax;
    }

    /**
     * Set tax
     *
     * @param string $tax
     *
     * @return OrderItemHasAddon
     */
    public function setTax($tax): OrderItemHasAddon {
        $this->tax = $tax;
        return $this;
    }

    public function getAdultQuantity(): ?int
    {
        return $this->adultQuantity;
    }

    public function setAdultQuantity(?int $adultQuantity): self
    {
        $this->adultQuantity = $adultQuantity;

        return $this;
    }

    public function getChildQuantity(): ?int
    {
        return $this->childQuantity;
    }

    public function setChildQuantity(?int $childQuantity): self
    {
        $this->childQuantity = $childQuantity;

        return $this;
    }

    public function getExtraTransportationQuantity(): ?int
    {
        return $this->extraTransportationQuantity;
    }

    public function setExtraTransportationQuantity(?int $extraTransportationQuantity): self
    {
        $this->extraTransportationQuantity = $extraTransportationQuantity;

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

    public function getExtraTransportation(): ?string
    {
        return $this->extraTransportation;
    }

    public function setExtraTransportation(string $extraTransportation): self
    {
        $this->extraTransportation = $extraTransportation;

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
    public function setSupplier(?Partner $supplier): OrderItemHasAddon {
        $this->supplier = $supplier;
        return $this;
    }
}
