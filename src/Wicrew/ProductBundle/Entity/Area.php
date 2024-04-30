<?php

namespace App\Wicrew\ProductBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Area
 *
 * @ORM\Table(name="Area", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 */
class Area extends BaseEntity {

    /**
     * Types
     */
    const TYPE_AIRPORT = 1;
    const TYPE_AREA = 2;

    /**
    * Has Airport
    */
    const HAS_AIRPORT_NO    = 0;
    const HAS_AIRPORT_YES   = 1;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());
        $this->setFromProducts(new ArrayCollection());
        $this->setToProducts(new ArrayCollection());
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
    public function getId() {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return Area
     */
    public function setId($id): Area {
        $this->id = $id;
        return $this;
    }



    /**
     * Type
     *
     * @var int
     *
     * @ORM\Column(name="type", type="integer", length=1, nullable=false, options={"comment"="1 = Airport, 2 = Hotel"})
     */
    private $type;

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
     * @return Area
     */
    public function setType(int $type): Area {
        $this->type = $type;
        return $this;
    }


    /**
     * Name
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
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
     * @return Area
     */
    public function setName(string $name): Area {
        $this->name = $name;
        return $this;
    }



    /**
     * Created at
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

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
     * @return Area
     */
    public function setCreatedAt(DateTime $createdAt): Area {
        $this->createdAt = $createdAt;
        return $this;
    }

    
    /**
     * Modified at
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

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
     * @param DateTime $modifiedAt
     *
     * @return Area
     */
    public function setModifiedAt(DateTime $modifiedAt): Area {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }


    /**
     * Products From
     *
     * @var Collection|Product[]
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\ProductBundle\Entity\Product", mappedBy="areaFrom", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $fromProducts;

    /**
     * Get form products
     *
     * @return Collection
     */
    public function getFromProducts() {
        return $this->fromProducts;
    }

    /**
     * Set form products
     *
     * @param Collection $fromProducts
     *
     * @return Area
     */
    public function setFromProducts(Collection $fromProducts): Area {
        $this->fromProducts = $fromProducts;
        return $this;
    }

    /**
     * Add form products
     *
     * @param Product $product
     *
     * @return Area
     */
    public function addFormProduct(Product $product): Area {
        $product->setAreaFrom($this);
        $this->getFromProducts()->add($product);

        return $this;
    }

    /**
     * Remove form products
     *
     * @param Product $product
     *
     * @return Area
     */
    public function removeFormProduct(Product $product): Area {
        foreach ($this->getFromProducts() as $k => $o) {
            if ($o->getId() == $product->getId()) {
                $this->getFromProducts()->removeElement($product);
            }
        }

        return $this;
    }


    /**
     * Products To
     *
     * @var Collection|Product[]
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\ProductBundle\Entity\Product", mappedBy="areaTo", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $toProducts;

    /**
     * Get to products
     *
     * @return Collection
     */
    public function getToProducts() {
        return $this->toProducts;
    }

    /**
     * Set to products
     *
     * @param Collection $toProducts
     *
     * @return Area
     */
    public function setToProducts(Collection $toProducts): Area {
        $this->toProducts = $toProducts;
        return $this;
    }

    /**
     * Add to products
     *
     * @param Product $product
     *
     * @return Area
     */
    public function addToProduct(Product $product): Area {
        $product->setAreaTo($this);
        $this->getToProducts()->add($product);

        return $this;
    }

    /**
     * Remove to products
     *
     * @param Product $product
     *
     * @return Area
     */
    public function removeToProduct(Product $product): Area {
        foreach ($this->getToProducts() as $k => $o) {
            if ($o->getId() == $product->getId()) {
                $this->getToProducts()->removeElement($product);
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
    public function preUpdate() {
        $this->setModifiedAt(new DateTime());
    }

    public function __toString() {
        return $this->getName();
    }



    /**
     * hasAirport
     *
     * @var int
     *
     * @ORM\Column(name="has_airport", type="integer", nullable=true)
     */
    private $hasAirport;

    /**
     * Get Has Airport
     *
     * @return int
     */
    public function getHasAirport() {
        return $this->hasAirport;
    }

    /**
     * Set Has Airport
     *
     * @param int $hasAirport
     *
     * @return Area
     */
    public function setHasAirport($hasAirport): Area {
        $this->hasAirport = $hasAirport;
        return $this;
    }


     /**
     * Airport
     *
     * @var Area
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area")
     * @ORM\JoinColumn(name="airport", referencedColumnName="id")
     */
    private $airport;

     /**
     * Get airport
     *
     * @return Area
     */
    public function getAirport() {
        return $this->airport;
    }

    /**
     * Set Area from
     *
     * @param Area $airport
     *
     * @return Area
     */
    public function setAirport(Area $airport): Area {
        $this->airport = $airport;
        return $this;
    }

}
