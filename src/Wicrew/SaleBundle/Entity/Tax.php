<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tax
 *
 * @ORM\Table(name="Tax", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Tax extends BaseEntity {

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
     * Amount
     *
     * @var string|null
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="amount", type="decimal", precision=4, scale=2, nullable=true, options={"comment"="Percentage"})
     */
    private $amount;

    /**
     * Created at
     *
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new \DateTime());
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
     * @return Tax
     */
    public function setId($id): Tax {
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
     * @return Tax
     */
    public function setLabel($label): Tax {
        $this->label = $label;
        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * Set amoun
     *
     * @param float $amount
     *
     * @return Tax
     */
    public function setAmount($amount): Tax {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param \DateTime $createdAt
     *
     * @return Tax
     */
    public function setCreatedAt(\DateTime $createdAt): Tax {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return \DateTime|null
     */
    public function getModifiedAt(): ?\DateTime {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param \DateTime $modifiedAt
     *
     * @return Tax
     */
    public function setModifiedAt(\DateTime $modifiedAt): Tax {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $args) {
        $this->setModifiedAt(new \DateTime());
    }

    /**
     * {@inheritDoc}
     */
    public function __toString() {
        return $this->getLabel();
    }

}
