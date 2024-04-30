<?php

namespace App\Wicrew\AddonBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\AddonBundle\Entity\ExtraOption;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\SaleBundle\Entity\Tax;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Extra
 *
 * @ORM\Table(name="Extra", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={@ORM\Index(name="fk_Extra_Tax_idx", columns={"tax_id"}), @ORM\Index(name="fk_Extra_Supplier_idx", columns={"supplier_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class Extra extends BaseEntity {

    /**
     * Types
     */
    public const TYPE_CHECKBOX = 1;
    public const TYPE_MULTI_CHECKBOX = 2;
    public const TYPE_RADIO = 3;

    /**
     * Price types
     */
    public const PRICE_TYPE_PER_PERSON = 1;
    public const PRICE_TYPE_FOR_THE_TRIP = 2;

    /**
     * Form types
     */
    public const FORM_TYPE_ADDON = 1;
    public const FORM_TYPE_EXTRA = 2;

    /**
     * Option Label
     */
    public const EXTRA_LABEL_PRICE = 'extra_price';

    /**
     * Prices display type
     */
    public const EXTRA_PRICE_DISPLAY_TOTAL = 'total';
    public const EXTRA_PRICE_DISPLAY_PRICE = 'extra_price';

    /**
     * extra ID
     */
    public const EXTRA_ID_IMPERIAL_BEER = 2;
    public const EXTRA_ID_TODDLER_CAR_SEAT = 3;

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
     * @ORM\Column(name="type", type="integer", length=1, nullable=false, options={"unsigned"=true,"comment"="1 = Checkbox, 2 = Multi checkbox, 3 = Radio"})
     */
    private $type;

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
     * Adult rack price
     *
     * @var float
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="adult_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $adultRackPrice = 0.00;

    /**
     * Adult net price
     *
     * @var float
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="adult_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $adultNetPrice = 0.00;

    /**
     * Child rack price
     *
     * @var float
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="child_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $childRackPrice = 0.00;

    /**
     * Child net price
     *
     * @var float
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="child_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $childNetPrice = 0.00;

    /**
     * Adult net price
     *
     * @var float
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="extra_transportation", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $extraTransportation = 0.00;

    /**
     * Rack price
     *
     * @var float
     *
     * @Assert\NotBlank()
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
     *
     * @ORM\Column(name="net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $netPrice = 0.00;

    /**
     * Discount price
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\PositiveOrZero()
     * @Assert\LessThanOrEqual(value="100")
     *
     * @ORM\Column(name="discount_percentage", type="decimal", precision=5, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $discountPercentage = 0.00;

    /**
     * Tax
     *
     * @var Tax|null
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
     * @Assert\Length(max = 1)
     *
     * @ORM\Column(name="price_type", type="integer", length=1, nullable=true, options={"unsigned"=true,"comment"="1 = Per person, 2 = For the trip"})
     */
    private $priceType;

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
     * Image
     *
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * Image binary
     *
     * @var File
     *
     * @Assert\Expression("this.getImageFile() or this.getImage()", message="error.image.required")
     * @Assert\File(maxSize = "8m")
     * @Assert\Image(
     *     mimeTypesMessage = "error.invalid.image",
     *     detectCorrupted = true,
     *     corruptedMessage = "error.image.corrupted"
     * )
     *
     * @Vich\UploadableField(mapping="addon.image", fileNameProperty="image")
     */
    protected $imageFile;

    /**
     * Image description
     *
     * @var string
     *
     * @Assert\Length(max = 16777215)
     *
     * @ORM\Column(name="image_description", type="text", length=16777215, nullable=true)
     */
    protected $imageDescription;

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
     * Options
     *
     * @var ArrayCollection
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\AddonBundle\Entity\ExtraOption", mappedBy="extra", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    private $options;

    /**
     * Products
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Wicrew\ProductBundle\Entity\Product", mappedBy="extras")
     */
    private $products;

    /**
     * add by default
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false, options={"default"="1"})
     */
    private $addByDefault = true;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());
        $this->setOptions(new ArrayCollection());
        $this->setProducts(new ArrayCollection());
        $this->options = new ArrayCollection();
        $this->products = new ArrayCollection();
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
     * @return Extra
     */
    public function setId($id): Extra {
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
     * @return Extra
     */
    public function setType($type): Extra {
        $this->type = $type;
        return $this;
    }

    /**
     * Set label
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
     * @return Extra
     */
    public function setLabel($label): Extra {
        $this->label = $label;
        return $this;
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string|null $description
     *
     * @return Addon
     */
    public function setDescription($description): Extra {
        $this->description = $description;
        return $this;
    }

    /**
     * Get discounted rack price
     *
     * @return string
     */
    public function getRackPriceWithDiscount(): string {
        return self::getPriceWithDiscount($this->rackPrice, $this->discountPercentage);
    }

    /**
     * @param string $price
     * @param string $discount
     *
     * @return string
     */
    public static function getPriceWithDiscount(string $price, string $discount): string {
        $decimalPrecision = 4;
        $discountScale = bcmul($discount, '0.01', $decimalPrecision);
        $discountScale = bcsub('1', $discountScale, $decimalPrecision); // Invert because this is a discount.

        $decimalPrecision = 2;
        return bcmul($price, $discountScale, $decimalPrecision);
    }

    /**
     * Get discount price
     *
     * @return float
     */
    public function getDiscountPercentage() {
        return $this->discountPercentage;
    }

    /**
     * Set discount price
     *
     * @param float $discountPercentage
     *
     * @return Extra
     */
    public function setDiscountPercentage($discountPercentage): Extra {
        $this->discountPercentage = $discountPercentage;
        return $this;
    }

    /**
     * Get tax
     *
     * @return Tax|null
     */
    public function getTax(): ?Tax {
        return $this->tax;
    }

    /**
     * Set tax
     *
     * @param Tax|null $tax
     *
     * @return Extra
     */
    public function setTax(?Tax $tax): Extra {
        $this->tax = $tax;
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
     * @return Extra
     */
    public function setPriceType($priceType): Extra {
        $this->priceType = $priceType;
        return $this;
    }

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
     * @return Extra
     */
    public function setSupplier(?Partner $supplier): Extra {
        $this->supplier = $supplier;
        return $this;
    }

    /**
     * Get image
     *
     * @return string|null
     */
    public function getImage(): ?string {
        return $this->image;
    }

    /**
     * Set image
     *
     * @param string|null $image
     *
     * @return Extra
     */
    public function setImage(?string $image): Extra {
        $this->image = $image;
        return $this;
    }

    /**
     * Get image file
     *
     * @return File
     */
    public function getImageFile() {
        return $this->imageFile;
    }

    /**
     * Set image file
     *
     * @param File $imageFile
     *
     * @return Extra
     */
    public function setImageFile($imageFile): Extra {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->setModifiedAt(new DateTime());
        }
        return $this;
    }

    /**
     * Get image description
     *
     * @return string|null
     */
    public function getImageDescription(): ?string {
        return $this->imageDescription;
    }

    /**
     * Set image description
     *
     * @param string|null $imageDescription
     *
     * @return Extra
     */
    public function setImageDescription(?string $imageDescription): Extra {
        $this->imageDescription = $imageDescription;
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
     * @return Extra
     */
    public function setCreatedAt(DateTime $createdAt): Extra {
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
     * @return Extra
     */
    public function setModifiedAt(DateTime $modifiedAt): Extra {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Get options
     *
     * @return ArrayCollection
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Set options
     *
     * @param ArrayCollection $options
     *
     * @return Extra
     */
    public function setOptions($options): Extra {
        $this->options = $options;
        return $this;
    }

    /**
     * Add option
     *
     * @param ExtraOption $option
     *
     * @return Extra
     */
    public function addOption(ExtraOption $option): Extra {
        if (!$this->getOptions()->contains($option)) {
            $option->setExtra($this);
            $this->getOptions()->add($option);
        }

        return $this;
    }

    /**
     * Remove option
     *
     * @param ExtraOption $option
     *
     * @return Extra
     */
    public function removeOption(ExtraOption $option): Extra {
        if ($this->getOptions()->contains($option)) {
            if ($option->getExtra() === $this) {
                //                $option->setExtra(null);
            }
            $this->getOptions()->removeElement($option);
        }

        return $this;
    }

    /**
     * Get products
     *
     * @return ArrayCollection|Product[]
     */
    public function getProducts() {
        return $this->products;
    }

    /**
     * Set products
     *
     * @param ArrayCollection $products
     *
     * @return Extra
     */
    public function setProducts($products): Extra {
        $this->products = $products;
        return $this;
    }

    public function getTypeLabel(): string {
        if ($this->getPriceType() === Extra::PRICE_TYPE_PER_PERSON) {
            return 'booking.Extra.perperson';
        } else if ($this->getPriceType() === Extra::PRICE_TYPE_FOR_THE_TRIP) {
            return 'booking.Extra.pertrip';
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
     * Validate dynamic required fields
     *
     * @param ExecutionContextInterface $context
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context) {
        if ($this->getRackPrice() === "") {
            $context->buildViolation('This value should not be blank.')
                ->atPath('rackPrice')
                ->addViolation();
        }
        if ($this->getNetPrice() === "") {
            $context->buildViolation('This value should not be blank.')
                ->atPath('netPrice')
                ->addViolation();
        }
        if (!$this->getTax()) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('tax')
                ->addViolation();
        }
        if (!$this->getSupplier()) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('supplier')
                ->addViolation();
        }
    }

    public function getAdultRackPrice(): ?string
    {
        return $this->adultRackPrice;
    }

    public function setAdultRackPrice(?string $adultRackPrice): self
    {
        $this->adultRackPrice = $adultRackPrice;

        return $this;
    }

    public function getAdultNetPrice(): ?string
    {
        return $this->adultNetPrice;
    }

    public function setAdultNetPrice(?string $adultNetPrice): self
    {
        $this->adultNetPrice = $adultNetPrice;

        return $this;
    }

    public function getChildRackPrice(): ?string
    {
        return $this->childRackPrice;
    }

    public function setChildRackPrice(?string $childRackPrice): self
    {
        $this->childRackPrice = $childRackPrice;

        return $this;
    }

    public function getChildNetPrice(): ?string
    {
        return $this->childNetPrice;
    }

    public function setChildNetPrice(?string $childNetPrice): self
    {
        $this->childNetPrice = $childNetPrice;

        return $this;
    }

    public function getExtraTransportation(): ?string
    {
        return $this->extraTransportation;
    }

    public function setExtraTransportation(?string $extraTransportation): self
    {
        $this->extraTransportation = $extraTransportation;

        return $this;
    }

    public function getRackPrice(): ?string
    {
        return $this->rackPrice;
    }

    public function setRackPrice(?string $rackPrice): self
    {
        $this->rackPrice = $rackPrice;

        return $this;
    }

    public function getNetPrice(): ?string
    {
        return $this->netPrice;
    }

    public function setNetPrice(?string $netPrice): self
    {
        $this->netPrice = $netPrice;

        return $this;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->addExtra($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            $product->removeExtra($this);
        }

        return $this;
    }

    public function getAddByDefault()
    {
        return $this->addByDefault;
    }

    public function setAddByDefault($addByDefault): self
    {
        $this->addByDefault = $addByDefault;

        return $this;
    }



    /**
     * Sorting order
     *
     * @var int
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=false, options={"default"="0"})
     */
    private $sortOrder = 0;

    /**
     * Get sort_order
     *
     * @return int
     */
    public function getSortOrder() {
        return $this->sortOrder;
    }

    /**
     * Set sort_order
     *
     * @param int $sortOrder
     *
     * @return Extra
     */
    public function setSortOrder($sortOrder): Extra {
        $this->sortOrder = $sortOrder;
        return $this;
    }

}
