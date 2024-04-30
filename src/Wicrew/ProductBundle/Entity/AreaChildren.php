<?php

namespace App\Wicrew\ProductBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Wicrew\ProductBundle\Entity\Area;


/**
 * AreaChildren
 *
 * @ORM\Table(name="AreaChildren", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 */
class AreaChildren extends BaseEntity {

 

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
    public function getId() {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return AreaChildren
     */
    public function setId($id): AreaChildren {
        $this->id = $id;
        return $this;
    }

    /**
     * Name
     *
     * @var string
     *
     * @ORM\Column(name="location_name", type="string", length=256, nullable=false)
     */
    private $name;

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
     * @return AreaChildren
     */
    public function setName(string $name): AreaChildren {
        $this->name = $name;
        return $this;
    }

    /**
     * Parent Area
     *
     * @var Area
     *
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_area", referencedColumnName="id")
     */
    private $parentArea;

    /**
     * Get Parent Area
     *
     * @return AreaChildren
     */
    public function getParentArea() {
        return $this->parentArea;
    }

    /**
     * Set Parent Area
     *
     * @param Area $parentArea
     *
     * @return AreaChildren
     */
    public function setParentArea(Area $parentArea): AreaChildren {
        $this->parentArea = $parentArea;
        return $this;
    }
}
