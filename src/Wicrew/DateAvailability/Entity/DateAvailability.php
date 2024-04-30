<?php

namespace App\Wicrew\DateAvailability\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * DateAvailability
 *
 * @ORM\Table(name="DateAvailability", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity(repositoryClass="App\Wicrew\DateAvailability\Repository\DateAvailabilityRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class DateAvailability extends BaseEntity
{

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
     * Date
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="date", type="string", length=255, nullable=false)
     */
    private $date;



    /**
     * Availability
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 10)
     *
     * @ORM\Column(name="availability", type="integer", length=10, nullable=false)
     */
    private $availability;

    
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
     * @return DateAvailability
     */
    public function setId($id): DateAvailability
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get Date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set Date
     *
     * @param string $date
     *
     * @return DateAvailability
     */
    public function setDate($date): DateAvailability
    {
        $this->date = $date;
        return $this;
    }


     /**
     * Get Availability
     *
     * @return int
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * Set Availability
     *
     * @param string $availability
     *
     * @return DateAvailability
     */
    public function setAvailability($availability): DateAvailability
    {
        $this->availability = $availability;
        return $this;
    }

}
