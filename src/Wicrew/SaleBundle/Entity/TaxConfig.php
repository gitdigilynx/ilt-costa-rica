<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tax
 *
 * @ORM\Table(name="TaxConfiguration", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TaxConfig extends BaseEntity {

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
     * jan_to_may
     *
     * @var int|null
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="jan_to_may", type="integer", nullable=true, options={"comment"="tax rate (%)"})
     */
    private $jan_to_may;


    /**
     * jun_to_dec
     *
     * @var int|null
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="jun_to_dec", type="integer", nullable=true, options={"comment"="tax rate (%)"})
     */
    private $jun_to_dec;

   
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
     * @return TaxConfig
     */
    public function setId($id): TaxConfig {
        $this->id = $id;
        return $this;
    }

    /**
     * Get label
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
     * @return TaxConfig
     */
    public function setLabel($label): TaxConfig {
        $this->label = $label;
        return $this;
    }

    /**
     * Get jan_to_may
     *
     * @return int
     */
    public function getJanMayRate() {
        return $this->jan_to_may;
    }

    /**
     * Set jan_to_may
     *
     * @param int $amount
     *
     * @return TaxConfig
     */
    public function setJanMayRate($jan_to_may): TaxConfig {
        $this->jan_to_may = $jan_to_may;
        return $this;
    }

    /**
     * Get jun_to_dec
     *
     * @return int
     */
    public function getJunDecRate() {
        return $this->jun_to_dec;
    }

    /**
     * Set jun_to_dec
     *
     * @param int $jun_to_dec
     *
     * @return TaxConfig
     */
    public function setJunDecRate($jun_to_dec): TaxConfig {
        $this->jun_to_dec = $jun_to_dec;
        return $this;
    }

}
