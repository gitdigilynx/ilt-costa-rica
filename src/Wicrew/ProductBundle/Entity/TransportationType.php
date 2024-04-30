<?php

namespace App\Wicrew\ProductBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * TransportationType
 *
 * @ORM\Table(name="TransportationType",
 *     uniqueConstraints={
 *     @ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})
 * }
 *     )
 * @ORM\Entity
 */
class TransportationType extends BaseEntity {
    const TYPE_PRIVATE_SHUTTLE = 1;
    const TYPE_SHARED_SHUTTLE = 2;
    const TYPE_JEEP_BOAT_JEEP_SHARED = 3;
    const TYPE_JEEP_BOAT_JEEP_PRIVATE = 4;
    const TYPE_JEEP_BOAT_JEEP_HORSEBACK = 5;
    const TYPE_WATER_TAXI = 6;
    const TYPE_WATER_TAXI_PRIVATE = 7;
    const TYPE_AIRPLANE = 8;

    const TYPE_PRIVATE_SHUTTLE_URL = 'private-shuttles';
    const TYPE_SHARED_SHUTTLE_URL = 'shared-shuttles';
    const TYPE_AIRPLANE_URL = 'private-flight';
    const TYPE_JEEP_BOAT_JEEP_URL = 'jeep-boat-jeep';
    const TYPE_JEEP_BOAT_JEEP_SHARED_URL = 'jeep-boat-jeep-shared';
    const TYPE_JEEP_BOAT_JEEP_PRIVATE_URL = 'jeep-boat-jeep-private';
    const TYPE_JEEP_BOAT_JEEP_HORSEBACK_URL = 'jeep-boat-jeep-riding';
    const TYPE_WATER_TAXI_URL = 'water-taxi';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * Products transportation type
     *
     * @var Collection|Product[]
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\ProductBundle\Entity\Product", mappedBy="transportationType", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $products;

    /**
     * URL path
     *
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $urlPath;

    /**
     * @return string
     */
    public function getUrlPath(): string {
        return $this->urlPath;
    }

    /**
     * @param string $urlPath
     *
     * @return TransportationType
     */
    public function setUrlPath(string $urlPath): TransportationType {
        $this->urlPath = $urlPath;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct() {
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
     * @return TransportationType
     */
    public function setId($id): TransportationType {
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
     * @return TransportationType
     */
    public function setName($name): TransportationType {
        $this->name = $name;
        return $this;
    }

    /**
     * Get products
     *
     * @return Collection|Product[]
     */
    public function getProducts() {
        return $this->products;
    }

    /**
     * Set products
     *
     * @param Collection|Product[] $products
     *
     * @return TransportationType
     */
    public function setProducts($products): TransportationType {
        $this->products = $products;
        return $this;
    }

    /**
     * Add product
     *
     * @param Product $product
     *
     * @return TransportationType
     */
    public function addProduct(Product $product): TransportationType {
        $product->setTransportationType($this);
        $this->getProducts()->add($product);

        return $this;
    }

    /**
     * Remove product
     *
     * @param Product $product
     *
     * @return TransportationType
     */
    public function removeProduct(Product $product): TransportationType {
        foreach ($this->getProducts() as $k => $o) {
            if ($o->getId() == $product->getId()) {
                $this->getProducts()->removeElement($product);
            }
        }

        return $this;
    }

    public function isPrivateType(): bool {
        return $this->getId() == TransportationType::TYPE_PRIVATE_SHUTTLE ||
            $this->getId() == TransportationType::TYPE_JEEP_BOAT_JEEP_PRIVATE ||
            $this->getId() == TransportationType::TYPE_WATER_TAXI_PRIVATE ||
            $this->getId() == TransportationType::TYPE_AIRPLANE;
    }

    public function isMainTransportationType(): bool {
        return $this->getId() == TransportationType::TYPE_PRIVATE_SHUTTLE ||
            $this->getId() == TransportationType::TYPE_SHARED_SHUTTLE ||
            $this->getId() == TransportationType::TYPE_AIRPLANE;
    }

    public function isJeepBoatJeepType(): bool {
        return $this->getId() == TransportationType::TYPE_JEEP_BOAT_JEEP_SHARED ||
            $this->getId() == TransportationType::TYPE_JEEP_BOAT_JEEP_PRIVATE ||
            $this->getId() == TransportationType::TYPE_JEEP_BOAT_JEEP_HORSEBACK;
    }

    public function getJBJName() {
        switch ($this->getId()) {
            case self::TYPE_JEEP_BOAT_JEEP_SHARED:
                {
                    return self::TYPE_JEEP_BOAT_JEEP_SHARED_URL;
                }
                break;
            case self::TYPE_JEEP_BOAT_JEEP_PRIVATE:
                {
                    return self::TYPE_JEEP_BOAT_JEEP_PRIVATE_URL;
                }
                break;
            case self::TYPE_JEEP_BOAT_JEEP_HORSEBACK:
                {
                    return self::TYPE_JEEP_BOAT_JEEP_HORSEBACK_URL;
                }
                break;
        }
    }

    public function getHeaderPath(): string {
        return self::getStaticHeaderPath($this->getId());
    }

    public static function getStaticHeaderPath(int $id): string {
        switch ($id) {
            case self::TYPE_PRIVATE_SHUTTLE:
                {
                    return '@WicrewProduct/Product/Headers/header.private_shuttle.html.twig';
                }
            case self::TYPE_SHARED_SHUTTLE:
                {
                    return '@WicrewProduct/Product/Headers/header.shared_shuttle.html.twig';
                }
            case self::TYPE_AIRPLANE:
                {
                    return '@WicrewProduct/Product/Headers/header.flight.html.twig';
                }
            case self::TYPE_JEEP_BOAT_JEEP_SHARED:
            case self::TYPE_JEEP_BOAT_JEEP_PRIVATE:
            case self::TYPE_JEEP_BOAT_JEEP_HORSEBACK:
                {
                    return '@WicrewProduct/Product/Headers/header.jbj_volcano.html.twig';
                }
            case self::TYPE_WATER_TAXI:
            case self::TYPE_WATER_TAXI_PRIVATE:
                {
                    return '@WicrewProduct/Product/Headers/header.water_taxi.html.twig';
                }
        }
    }

    public function __toString() {
        return $this->getName();
    }
}
