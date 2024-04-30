<?php

namespace App\Wicrew\SystemConfigurationBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SystemConfiguration
 *
 * @ORM\Table(name="SystemConfiguration", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity(repositoryClass="App\Wicrew\SystemConfigurationBundle\Repository\SystemConfigurationRepository")
 * @UniqueEntity("key")
 */
class SystemConfiguration extends BaseEntity {

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
     * Key
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 64)
     *
     * @var string
     *
     * @ORM\Column(name="key", type="string", length=64, nullable=false)
     */
    private $key;

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
     * Value
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="value", type="text", length=65535, nullable=true)
     */
    private $value;

    /**
     * Modified at
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

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
     * @return SystemConfiguration
     */
    public function setId($id): SystemConfiguration {
        $this->id = $id;
        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * Set key
     *
     * @param string $key
     *
     * @return SystemConfiguration
     */
    public function setKey($key): SystemConfiguration {
        $this->key = $key;
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
     * @return SystemConfiguration
     */
    public function setLabel($label): SystemConfiguration {
        $this->label = $label;
        return $this;
    }

    /**
     * Get value
     *
     * @return string|null
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string|null $value
     *
     * @return SystemConfiguration
     */
    public function setValue($value): SystemConfiguration {
        $this->value = $value;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return DateTime|null
     */
    public function getModifiedAt(): ?DateTime {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param DateTime|null $modifiedAt
     *
     * @return SystemConfiguration
     */
    public function setModifiedAt(?DateTime $modifiedAt): SystemConfiguration {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate() {
        $this->setModifiedAt(new DateTime());
    }

}
