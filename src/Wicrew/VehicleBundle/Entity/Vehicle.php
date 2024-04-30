<?php

namespace App\Wicrew\VehicleBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * VehicleType
 *
 * @ORM\Table(name="Vehicle", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 * @ORM\Index(name="fk_Vehicle_VehicleType_idx", columns={"vehicle_type_id"})})
 * @ORM\Entity(repositoryClass="App\Wicrew\VehicleBundle\Repository\VehicleRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Vehicle extends BaseEntity {

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
     * Vehicle type
     *
     * @var VehicleType
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\VehicleTypeBundle\Entity\VehicleType", inversedBy="vehicles", cascade={"persist"})
     * @ORM\JoinColumn(name="vehicle_type_id", referencedColumnName="id")
     */
    private $vehicleType;

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
     * Plate
     *
     * @var string|null
     *
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="plate", type="string", length=32, nullable=true)
     */
    private $plate;

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
     * @return Vehicle
     */
    public function setId($id): Vehicle {
        $this->id = $id;
        return $this;
    }

    /**
     * Get vehicle type
     *
     * @return VehicleType
     */
    public function getVehicleType() {
        return $this->vehicleType;
    }

    /**
     * Set vehicle type
     *
     * @param VehicleType $vehicleType
     *
     * @return Vehicle
     */
    public function setVehicleType(VehicleType $vehicleType): Vehicle {
        $this->vehicleType = $vehicleType;
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
     * @return Vehicle
     */
    public function setName($name): Vehicle {
        $this->name = $name;
        return $this;
    }

    /**
     * Get plate
     *
     * @return string
     */
    public function getPlate() {
        return $this->plate;
    }

    /**
     * Set plate
     *
     * @param string $plate
     *
     * @return Vehicle
     */
    public function setPlate($plate): Vehicle {
        $this->plate = $plate;
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
     * @return Vehicle
     */
    public function setNotes($notes): Vehicle {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Get created at
     *
     * @return DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param DateTime $createdAt
     *
     * @return Vehicle
     */
    public function setCreatedAt(DateTime $createdAt): Vehicle {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return DateTime|null
     */
    public function getModifiedAt() {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param DateTime|null $modifiedAt
     *
     * @return Vehicle
     */
    public function setModifiedAt(?DateTime $modifiedAt): Vehicle {
        $this->modifiedAt = $modifiedAt;
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
        $this->setModifiedAt(new DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() {
        return $this->getName();
    }

}
