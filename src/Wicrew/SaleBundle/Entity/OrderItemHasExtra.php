<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\AddonBundle\Entity\ExtraOption;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use App\Wicrew\PartnerBundle\Entity\Partner;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrderItemHasExtra
 *
 * @ORM\Table(name="OrderItemHasExtra", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 *     @ORM\Index(name="fk_OrderItemHasExtra_Extra_idx", columns={"Extra_id"}),
 *     @ORM\Index(name="fk_OrderItemHasExtra_ExtraOption_idx", columns={"Extra_option_id"}),
 *     @ORM\Index(name="fk_OrderItemHasExtra_OrderItem_idx", columns={"order_item_id"})
 * })
 * @ORM\Entity
 */
class OrderItemHasExtra extends BaseEntity {
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
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", inversedBy="Extras", cascade={"persist"})
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $orderItem;

    /**
     * Extra
     *
     * @var Extra
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\AddonBundle\Entity\Extra", cascade={"persist"})
     * @ORM\JoinColumn(name="extra_id", referencedColumnName="id")
     */
    private $extra;

    /**
     * Extra option
     *
     * @var ExtraOption|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\AddonBundle\Entity\ExtraOption", cascade={"persist"})
     * @ORM\JoinColumn(name="extra_option_id", referencedColumnName="id")
     */
    private $extraOption;

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
     * Price type
     *
     * @var int|null
     *
     * @ORM\Column(name="quantity", type="integer", length=1, nullable=true)
     */
    private $quantity;

    /**
     * @return int|null
     */
    public function getQuantity(): ?int {
        return $this->quantity;
    }

    /**
     * @param int|null $quantity
     *
     * @return OrderItemHasExtra
     */
    public function setQuantity(?int $quantity): OrderItemHasExtra {
        $this->quantity = $quantity;
        return $this;
    }

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
     * @return OrderItemHasExtra
     */
    public function setId($id): OrderItemHasExtra {
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
     * @return OrderItemHasExtra
     */
    public function setOrderItem(OrderItem $orderItem): OrderItemHasExtra {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * Get extra
     *
     * @return Extra
     */
    public function getExtra(): Extra {
        return $this->extra;
    }

    /**
     * Set Extra
     *
     * @param Extra $extra
     *
     * @return OrderItemHasExtra
     */
    public function setExtra(Extra $extra): OrderItemHasExtra {
        $this->extra = $extra;
        if (!$this->getExtraOption() && count($extra->getOptions()) == 0) {
            $this->setLabel($extra->getLabel());
            $this->setRackPrice($extra->getRackPrice());
            $this->setNetPrice($extra->getNetPrice());
            $this->setPriceType($extra->getPriceType());
            $this->setTax( (0.8 * $extra->getRackPrice()) );
        }
        return $this;
    }

    /**
     * Get Extra option
     *
     * @return ExtraOption|null
     */
    public function getExtraOption(): ?ExtraOption {
        return $this->extraOption;
    }

    /**
     * Set Extra option
     *
     * @param ExtraOption|null $extraOption
     *
     * @return OrderItemHasExtra
     */
    public function setExtraOption(?ExtraOption $extraOption): OrderItemHasExtra {
        $this->extraOption = $extraOption;
        $this->setLabel($extraOption->getLabel());
        $this->setRackPrice($extraOption->getRackPrice());
        $this->setNetPrice($extraOption->getNetPrice());
        $this->setPriceType($extraOption->getPriceType());
        $this->setTax( (0.8 * $extraOption->getRackPrice()) );
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
     * @return OrderItemHasExtra
     */
    public function setLabel($label): OrderItemHasExtra {
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
     * @return OrderItemHasExtra
     */
    public function setAddonTitle($addonTitle): OrderItemHasExtra {
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
     * @return OrderItemHasExtra
     */
    public function setRackPrice($rackPrice): OrderItemHasExtra {
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
     * @return OrderItemHasExtra
     */
    public function setNetPrice($netPrice): OrderItemHasExtra {
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
     * @return OrderItemHasExtra
     */
    public function setPriceType($priceType): OrderItemHasExtra {
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
     * @return OrderItemHasExtra
     */
    public function setTax($tax): OrderItemHasExtra {
        $this->tax = $tax;
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
    public function setSupplier(?Partner $supplier): OrderItemHasExtra {
        $this->supplier = $supplier;
        return $this;
    }
}
