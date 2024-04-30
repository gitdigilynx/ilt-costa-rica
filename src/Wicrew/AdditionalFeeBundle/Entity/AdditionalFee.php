<?php

namespace App\Wicrew\AdditionalFeeBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\SaleBundle\Entity\Tax;
use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AdditionalFee
 *
 * @ORM\Table(name="AdditionalFee", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={@ORM\Index(name="fk_AdditionalFee_Tax_idx", columns={"tax_id"}), @ORM\Index(name="fk_AdditionalFee_Area_idx", columns={"area_id"})})
 * @ORM\Entity
 */
class AdditionalFee extends BaseEntity {

    /**
     * Types
     */
    public const TYPE_TRANSPORTATION = 1;
    public const TYPE_ACTIVITY = 2;

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
     * Area
     *
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area", cascade={"persist"})
     * @ORM\JoinColumn(name="area_id", referencedColumnName="id")
     */
    private $area;

    /**
     * Types
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 16)
     *
     * @ORM\Column(name="types", type="string", length=16, nullable=false)
     */
    private $types = '';

    /**
     * Hotel name
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="hotel_name", type="string", length=255, nullable=false)
     */
    private $hotelName;

    /**
     * Google place ID
     *
     * @var string|null
     *
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="google_place_id", type="string", length=32, nullable=true)
     */
    private $googlePlaceId;

    /**
     * Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $rackPrice = 0.00;

    /**
     * Net price
     *
     * @var string
     *
     * @Assert\NotBlank()
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
     * Description
     *
     * @var string|null
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

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
     * @return AdditionalFee
     */
    public function setId($id): AdditionalFee {
        $this->id = $id;
        return $this;
    }

    /**
     * Get area
     *
     * @return Area
     */
    public function getArea() {
        return $this->area;
    }

    /**
     * Set area
     *
     * @param Area $area
     *
     * @return AdditionalFee
     */
    public function setArea(Area $area): AdditionalFee {
        $this->area = $area;
        return $this;
    }

    /**
     * Get types
     *
     * @return array
     */
    public function getTypes() {
        return explode(',', $this->types);
    }

    /**
     * Set types
     *
     * @param array $types
     *
     * @return AdditionalFee
     */
    public function setTypes(array $types): AdditionalFee {
        $this->types = implode(',', $types);
        return $this;
    }

    /**
     * Get hotel name
     *
     * @return string
     */
    public function getHotelName() {
        return $this->hotelName;
    }

    /**
     * Set hotel name
     *
     * @param string $hotelName
     *
     * @return AdditionalFee
     */
    public function setHotelName($hotelName): AdditionalFee {
        $this->hotelName = $hotelName;
        return $this;
    }

    /**
     * Get Google place ID
     *
     * @return string|null
     */
    public function getGooglePlaceId() {
        return $this->googlePlaceId;
    }

    /**
     * Set Google place ID
     *
     * @param string|null $googlePlaceId
     *
     * @return AdditionalFee
     */
    public function setGooglePlaceId($googlePlaceId): AdditionalFee {
        $this->googlePlaceId = $googlePlaceId;
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
     * @return AdditionalFee
     */
    public function setRackPrice($rackPrice): AdditionalFee {
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
     * @return AdditionalFee
     */
    public function setNetPrice($netPrice): AdditionalFee {
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
     * @return AdditionalFee
     */
    public function setTax(Tax $tax): AdditionalFee {
        $this->tax = $tax;
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
     * @return AdditionalFee
     */
    public function setDescription($description): AdditionalFee {
        $this->description = $description;
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
     * @return AdditionalFee
     */
    public function setCreatedAt(DateTime $createdAt): AdditionalFee {
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
     * @return AdditionalFee
     */
    public function setModifiedAt(DateTime $modifiedAt): Additionalfee {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $args) {
        $this->setModifiedAt(new DateTime());
    }

}
