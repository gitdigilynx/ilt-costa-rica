<?php

namespace App\Wicrew\VehicleTypeBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * VehicleType
 *
 * @ORM\Table(name="VehicleType", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"}), @ORM\UniqueConstraint(name="name_UNIQUE", columns={"name"})})
 * @ORM\Entity(repositoryClass="App\Wicrew\VehicleTypeBundle\Repository\VehicleTypeRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class VehicleType extends BaseEntity {
    /**
     * VEHICLE TYPES
     */
    public const HYUNDAI_H1 = 6;

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
     * Name
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
     * @Assert\File(maxSize = "8m")
     * @Assert\Image(
     *     mimeTypesMessage = "error.invalid.image",
     *     detectCorrupted = true,
     *     corruptedMessage = "error.image.corrupted"
     * )
     *
     * @Vich\UploadableField(mapping="vehicletype.image", fileNameProperty="image")
     */
    protected $imageFile;

    /**
     * Air condition
     *
     * @var bool
     *
     * @ORM\Column(name="air_conditioning", type="boolean", nullable=false, options={"default"="1","comment"="0 = No, 1 = Yes"})
     */
    private $airConditioning = true;

    /**
     * Catchy sentence
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="catchy_sentence", type="string", length=255, nullable=true)
     */
    private $catchySentence;

    /**
     * Amount of passenger max
     *
     * @var int
     *
     * @Assert\Length(max = 2)
     *
     * @ORM\Column(name="max_passenger_number", type="integer", length=2, nullable=false, options={"unsigned"=true})
     */
    private $maxPassengerNumber;

    /**
     * Amount of passenger min
     *
     * @var int
     *
     * @Assert\Length(max = 2)
     *
     * @ORM\Column(name="min_passenger_number", type="integer", length=2, nullable=false, options={"unsigned"=true})
     */
    private $minPassengerNumber;

    /**
     * Amount of accepted luggages
     *
     * @var float|null
     *
     * @Assert\Type(type = "float")
     *
     * @ORM\Column(name="max_luggage_weight", type="float", precision=4, scale=2, nullable=false, options={"default"="0.00","unsigned"=true})
     */
    private $maxLuggageWeight = 0.00;

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
     * Enabled status
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false, options={"default"="1","comment"="0 = No, 1 = Yes"})
     */
    private $enabled = true;

    /**
     * Created at
     *
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * Modified at
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Products
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\ProductBundle\Entity\Product", mappedBy="vehicleType", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $products;

    /**
     * Vehicles
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\VehicleBundle\Entity\Vehicle", mappedBy="vehicleType", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $vehicles;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new \DateTime());
        $this->setProducts(new ArrayCollection());
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
     * @return VehicleType
     */
    public function setId($id): VehicleType {
        $this->id = $id;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return VehicleType
     */
    public function setName($name): VehicleType {
        $this->name = $name;
        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return VehicleType
     */
    public function setImage($image): VehicleType {
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
     * @return VehicleType
     */
    public function setImageFile($imageFile): VehicleType {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->setModifiedAt(new \DateTime());
        }
        return $this;
    }

    /**
     * Get air conditioning
     *
     * @return bool
     */
    public function isAirConditioning() {
        return $this->airConditioning;
    }

    /**
     * Set air conditioning
     *
     * @param bool $airConditioning
     *
     * @return VehicleType
     */
    public function setAirConditioning($airConditioning): VehicleType {
        $this->airConditioning = filter_var($airConditioning, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * Get Catchy Sentence
     *
     * @return string
     */
    public function getCatchySentence() {
        return $this->catchySentence;
    }

    /**
     * Set Catchy Sentence
     *
     * @param string $catchySentence
     *
     * @return VehicleType
     */
    public function setCatchySentence($catchySentence): VehicleType {
        $this->catchySentence = $catchySentence;
        return $this;
    }

    /**
     * Get Amount Of Passengers Max
     *
     * @return string
     */
    public function getMaxPassengerNumber() {
        return $this->maxPassengerNumber;
    }

    /**
     * Set Amount Of Passengers Max
     *
     * @param string $maxPassengerNumber
     *
     * @return VehicleType
     */
    public function setMaxPassengerNumber($maxPassengerNumber): VehicleType {
        $this->maxPassengerNumber = $maxPassengerNumber;
        return $this;
    }

    /**
     * Get Amount Of Passengers Min
     *
     * @return string
     */
    public function getMinPassengerNumber() {
        return $this->minPassengerNumber;
    }

    /**
     * Set Amount Of Passengers Min
     *
     * @param string $minPassengerNumber
     *
     * @return VehicleType
     */
    public function setMinPassengerNumber($minPassengerNumber): VehicleType {
        $this->minPassengerNumber = $minPassengerNumber;
        return $this;
    }

    /**
     * Get Amount Of Accepted Luggages
     *
     * @return float
     */
    public function getMaxLuggageWeight() {
        return $this->maxLuggageWeight;
    }

    /**
     * Set Amount Of Accepted Luggages
     *
     * @param float $maxLuggageWeight
     *
     * @return VehicleType
     */
    public function setMaxLuggageWeight($maxLuggageWeight): VehicleType {
        $this->maxLuggageWeight = $maxLuggageWeight;
        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes() {
        return $this->notes;
    }

    /**
     * Set notes
     *
     * @param string $notes
     *
     * @return VehicleType
     */
    public function setNotes($notes): VehicleType {
        $this->notes = $notes;
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
     * @return VehicleType
     */
    public function setEnabled($enabled): VehicleType {
        $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param \DateTime $createdAt
     *
     * @return VehicleType
     */
    public function setCreatedAt(\DateTime $createdAt): VehicleType {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return \DateTime|null
     */
    public function getModifiedAt() {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param \DateTime|null $modifiedAt
     *
     * @return VehicleType
     */
    public function setModifiedAt(?\DateTime $modifiedAt): VehicleType {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Get products
     *
     * @return ArrayCollection
     */
    public function getProducts() {
        return $this->products;
    }

    /**
     * Set products
     *
     * @param ArrayCollection $products
     *
     * @return Entity\VehicleType
     */
    public function setProducts($products): VehicleType {
        $this->products = $products;
        return $this;
    }

    /**
     * Add product
     *
     * @param Product $product
     *
     * @return VehicleType
     */
    public function addProduct(Product $product): VehicleType {
        $product->setVehicleType($this);
        $this->getProducts()->add($product);

        return $this;
    }

    /**
     * Remove product
     *
     * @param Product $product
     *
     * @return VehicleType
     */
    public function removeProduct(Product $product): VehicleType {
        foreach ($this->getProducts() as $k => $o) {
            if ($o->getId() == $product->getId()) {
                $this->getProducts()->removeElement($product);
            }
        }

        return $this;
    }

    /**
     * Get vehicles
     *
     * @return ArrayCollection
     */
    public function getVehicles() {
        return $this->vehicles;
    }

    /**
     * Set vehicles
     *
     * @param ArrayCollection $vehicles
     *
     * @return Entity\VehicleType
     */
    public function setVehicles($vehicles): VehicleType {
        $this->vehicles = $vehicles;
        return $this;
    }

    /**
     * Add vehicle
     *
     * @param Vehicle $vehicle
     *
     * @return VehicleType
     */
    public function addVehicle(Vehicle $vehicle): VehicleType {
        $vehicle->setVehicleType($this);
        $this->getVehicles()->add($vehicle);

        return $this;
    }

    /**
     * Remove vehicle
     *
     * @param Product $vehicle
     *
     * @return VehicleType
     */
    public function removeVehicle(Vehicle $vehicle): VehicleType {
        foreach ($this->getVehicles() as $k => $o) {
            if ($o->getId() == $vehicle->getId()) {
                $this->getVehicles()->removeElement($vehicle);
            }
        }

        return $this;
    }

    /**
     * Gets triggered only on insert
     *
     * @ORM\PrePersist
     */
    public function prePersist() {
    }

    /**
     * Gets triggered only on insert
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $args) {
        $this->setModifiedAt(new \DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() {
        return $this->getName();
    }
}
