<?php

namespace App\Wicrew\ProductBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Area
 *
 * @ORM\Table(name="DepartureTime", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity
 */
class DepartureTime extends BaseEntity {
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
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return DepartureTime
     */
    public function setId(int $id): DepartureTime {
        $this->id = $id;
        return $this;
    }

    /**
     * Product
     *
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Product", inversedBy="departureTimes", cascade={"persist"})
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     */
    private $product;

    /**
     * @return Product
     */
    public function getProduct(): Product {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return DepartureTime
     */
    public function setProduct(Product $product): DepartureTime {
        $this->product = $product;
        return $this;
    }

    /**
     * Departure time
     *
     * @var DateTime|null
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="time", type="time", nullable=false)
     */
    private $time;

    /**
     * @return DateTime|null
     */
    public function getTime(): ?DateTime {
        return $this->time;
    }

    /**
     * @param DateTime|null $departureTime
     *
     * @return DepartureTime
     */
    public function setTime(?DateTime $departureTime): DepartureTime {
        $this->time = $departureTime;
        return $this;
    }
}
