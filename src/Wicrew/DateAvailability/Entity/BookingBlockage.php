<?php

namespace App\Wicrew\DateAvailability\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ProductBundle\Entity\Area;
use Symfony\Component\Validator\Constraints as Assert;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


/**
 * BookingBlockage
 *
 * @ORM\Table(name="BookingBlockage", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})} )
 * @ORM\Entity(repositoryClass="App\Wicrew\DateAvailability\Repository\BookingBlockageRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */

class BookingBlockage extends BaseEntity {
      /**
     * Constructor
     */
    public function __construct() {
        $this->vehicleTypes = new ArrayCollection();
        $this->areasFrom    = new ArrayCollection();
        $this->areasTo      = new ArrayCollection();
        $this->activities   = new ArrayCollection();
    }


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
     * Get ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return BookingBlockage
     */
    public function setId($id): BookingBlockage
    {
        $this->id = $id;
        return $this;
    }

    /**
     * date
     *
     * @var date|null
     *
     * @ORM\Column(name="date", type="date", nullable=true)
     */
    private $date;


    /**
     * Get date
     *
     * @return date|null|string
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set Date
     *
     * @param date|null $date
     *
     * @return BookingBlockage
     */
    public function setDate($date): BookingBlockage {
        if ($date !== null) {
            $this->date = new DateTime($date);
        } else {
            $this->date = null;
        }
        return $this;
    }

   
    /**
     * Time From
     *
     * @var Time|null
     *
     * @ORM\Column(name="time_from", type="time", nullable=true)
     */
    private $timeFrom;


    /**
     * Get Time from
     *
     * @return Time|null
     */
    public function getTimeFrom() {
        return $this->timeFrom;
    }

    /**
     * Set Time from
     *
     * @param Time|null $timeFrom
     *
     * @return BookingBlockage
     */
    public function setTimeFrom($timeFrom): BookingBlockage {
        $this->timeFrom = $timeFrom;
        return $this;
    }

     /**
     * Time To
     *
     * @var Time|null
     *
     * @ORM\Column(name="time_to", type="time", nullable=true)
     */
    private $timeTo;


    /**
     * Get Time to
     *
     * @return Time|null
     */
    public function getTimeTo() {
        return $this->timeTo;
    }

    /**
     * Set Time to
     *
     * @param Time|null $timeTo
     *
     * @return BookingBlockage
     */
    public function setTimeTo($timeTo): BookingBlockage {
        $this->timeTo = $timeTo;
        return $this;
    }

    /**
     * Vehicle Type
     *
     * @var Collection|VehicleType[]
     *
     * @ORM\ManyToMany(targetEntity="App\Wicrew\VehicleTypeBundle\Entity\VehicleType", inversedBy="BookingBlockage")
     * @ORM\JoinTable(name="BlockageHasVehicle",
     *     joinColumns={@ORM\JoinColumn(name="blockage_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="vehicle_id", referencedColumnName="id")}
     * )
     * 
     */
    private $vehicleTypes;


    /**
     * @return Collection|VehicleType[]
     */
    public function getVehicleTypes(): Collection
    {
        return $this->vehicleTypes;
    }

    public function addVehicleType(VehicleType $vehicleType): self
    {
        if (!$this->vehicleTypes->contains($vehicleType)) {
            $this->vehicleTypes[] = $vehicleType;
        }
        return $this;
    }

    public function removeVehicleType(VehicleType $vehicleType): self
    {
        $this->vehicleTypes->removeElement($vehicleType);

        return $this;
    }


    /**
     * Areas From
     *
     * @var Collection|VehicleType[]
     *
     * @ORM\ManyToMany(targetEntity="App\Wicrew\ProductBundle\Entity\Area", inversedBy="BookingBlockage")
     * @ORM\JoinTable(name="BlockageHasAreaFrom",
     *     joinColumns={@ORM\JoinColumn(name="blockage_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="area_from_id", referencedColumnName="id")}
     * )
     * 
     */
    private $areasFrom;


    /**
     * @return Collection|VehicleType[]
     */
    public function getAreasFrom(): Collection
    {
        return $this->areasFrom;
    }

    public function addAreaFrom(Area $areaFrom): self
    {
        if (!$this->areasFrom->contains($areaFrom)) {
            $this->areasFrom[] = $areaFrom;
        }
        return $this;
    }

    public function removeAreaFrom(Area $AreaFrom): self
    {
        $this->areasFrom->removeElement($AreaFrom);

        return $this;
    }


    /**
     * Areas To
     *
     * @var Collection|VehicleType[]
     *
     * @ORM\ManyToMany(targetEntity="App\Wicrew\ProductBundle\Entity\Area", inversedBy="BookingBlockage")
     * @ORM\JoinTable(name="BlockageHasAreaTo",
     *     joinColumns={@ORM\JoinColumn(name="blockage_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="area_to_id", referencedColumnName="id")}
     * )
     * 
     */
    private $areasTo;


    /**
     * @return Collection|VehicleType[]
     */
    public function getAreasTo(): Collection
    {
        return $this->areasTo;
    }

    public function addAreaTo(Area $areaTo): self
    {
        if (!$this->areasTo->contains($areaTo)) {
            $this->areasTo[] = $areaTo;
        }
        return $this;
    }

    public function removeAreaTo(Area $AreaTo): self
    {
        $this->areasTo->removeElement($AreaTo);

        return $this;
    }

    /**
     * Activities
     *
     * @var Collection|Activity[]
     *
     * @ORM\ManyToMany(targetEntity="App\Wicrew\ActivityBundle\Entity\Activity", inversedBy="BookingBlockage")
     * @ORM\JoinTable(name="BlockageHasActivity",
     *     joinColumns={@ORM\JoinColumn(name="blockage_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="activity_id", referencedColumnName="id")}
     * )
     * 
     */
    private $activities;


    /**
     * @return Collection|Activity[]
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): self
    {
        if (!$this->activities->contains($activity)) {
            $this->activities[] = $activity;
        }
        return $this;
    }

    public function removeActivity(Activity $activity): self
    {
        $this->activities->removeElement($activity);

        return $this;
    }

    /**
     * JBJ Type
     *
     * @var string|null
     *
     * @ORM\Column(name="jbj_type", type="string", nullable=true)
     */
    private $jbjType;

    /**
     * Get JBJ Type
     *
     * @return string|null
     */
    public function getJbjType()
    {
        return $this->jbjType;
    }

    /**
     * Set JBJ Type
     *
     * @param string|null $jbjType
     *
     * @return BookingBlockage
     */
    public function setJbjType($jbjType): BookingBlockage
    {
        $this->jbjType = $jbjType;
        return $this;
    }


    /**
     * Activity Type
     *
     * @var string|null
     *
     * @ORM\Column(name="activity_type", type="string", nullable=true)
     */
    private $activityType;

    /**
     * Get Activity Type
     *
     * @return string|null
     */
    public function getActivityType()
    {
        return $this->activityType;
    }

    /**
     * Set Activity Type
     *
     * @param string|null $activityType
     *
     * @return BookingBlockage
     */
    public function setActivityType($activityType): BookingBlockage
    {
        $this->activityType = $activityType;
        return $this;
    }


}
