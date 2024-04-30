<?php

namespace App\Wicrew\DriverBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Driver
 *
 * @ORM\Table(name="Driver", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Driver extends BaseEntity
{

    /**
     * Transportation types
     */
    public const TRANSPORTATION_TYPE_PRIVATE_SHUTTLE = 1;
    public const TRANSPORTATION_TYPE_SHARED_SHUTTLE = 2;
    public const TRANSPORTATION_TYPE_PRIVATE_FLIGHT = 3;
    public const TRANSPORTATION_TYPE_WATER_TAXI_BOAT = 4;
    public const TRANSPORTATION_TYPE_JEEP_BOAT_JEEP = 5;
    public const TRANSPORTATION_TYPE_ACTIVITY = 6;

    /**
     * Types
     */
    public const TYPE_DRIVER = 1;
    public const TYPE_AFFILIATE = 2;
    public const TYPE_SUPPLIER = 3;
    public const TYPE_TRAVEL_AGENT = 4;
    public const TYPE_PARTNER = 5;

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
     * First name
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=false)
     */
    private $firstname;

    /**
     * Last name
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=false)
     */
    private $lastname;

    /**
     * Telephone
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="tel", type="string", length=32, nullable=false)
     */
    private $tel;

    /**
     * Telephone ext.
     *
     * @var string|null
     *
     * @Assert\Length(max = 8)
     *
     * @ORM\Column(name="tel_ext", type="string", length=8, nullable=true)
     */
    private $telExt;

    /**
     * Email
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="email", type="string", length=32, nullable=false)
     */
    private $email;

    /**
     * Leal business name
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="biz_name", type="string", length=255, nullable=false)
     */
    private $bizName;

    /**
     * Business address
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="biz_address", type="text", length=65535, nullable=false)
     */
    private $bizAddress;

    /**
     * Business city
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="biz_city", type="string", length=255, nullable=false)
     */
    private $bizCity;

    /**
     * Business province
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="biz_province", type="string", length=255, nullable=false)
     */
    private $bizProvince;

    /**
     * Business country
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="biz_country", type="string", length=255, nullable=false)
     */
    private $bizCountry;

    /**
     * Business telephone
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="biz_tel", type="string", length=32, nullable=false)
     */
    private $bizTel;

    /**
     * Transportation types
     *
     * @var string|null
     *
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="transportation_types", type="string", length=32, nullable=true)
     */
    private $transportationTypes = '[]';

    /**
     * Type
     *
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="type", type="integer", nullable=false, options={"comment"="1 = Driver, 2 = Affiliate, 3 = Supplier, 4 = Travel agent, 5 = Partner"})
     */
    private $type;

    /**
     * Commission
     *
     * @var float
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="commission", type="float", precision=4, scale=2, nullable=false, options={"default"="0.00","comment"="Percentage"})
     */
    private $commission = 0.00;

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
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * Modified at
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * Get ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id): Driver
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set first name
     *
     * @param string $firstname
     * @return Driver
     */
    public function setFirstname($firstname): Driver
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set last name
     *
     * @param string $lastname
     * @return Driver
     */
    public function setLastname($lastname): Driver
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTel()
    {
        return $this->tel;
    }

    /**
     * Set telephone
     *
     * @param string $tel
     * @return Driver
     */
    public function setTel($tel): Driver
    {
        $this->tel = $tel;
        return $this;
    }

    /**
     * Get telephone ext.
     *
     * @return string
     */
    public function getTelExt()
    {
        return $this->telExt;
    }

    /**
     * Set telephone ext.
     *
     * @param string $telExt
     * @return Driver
     */
    public function setTelExt($telExt): Driver
    {
        $this->telExt = $telExt;
        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Driver
     */
    public function setEmail($email): Driver
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get legal business name
     *
     * @return string
     */
    public function getBizName()
    {
        return $this->bizName;
    }

    /**
     * Set legal business name
     *
     * @param string $bizName
     * @return Driver
     */
    public function setBizName($bizName): Driver
    {
        $this->bizName = $bizName;
        return $this;
    }

    /**
     * Get business address
     *
     * @return string
     */
    public function getBizAddress()
    {
        return $this->bizAddress;
    }

    /**
     * Set business address
     *
     * @param string $bizAddress
     * @return Driver
     */
    public function setBizAddress($bizAddress): Driver
    {
        $this->bizAddress = $bizAddress;
        return $this;
    }

    /**
     * Get business city
     *
     * @return string
     */
    public function getBizCity()
    {
        return $this->bizCity;
    }

    /**
     * Set business city
     *
     * @param string $bizCity
     * @return Driver
     */
    public function setBizCity($bizCity): Driver
    {
        $this->bizCity = $bizCity;
        return $this;
    }

    /**
     * Get business province
     *
     * @return string
     */
    public function getBizProvince()
    {
        return $this->bizProvince;
    }

    /**
     * Set business province
     *
     * @param string $bizProvince
     * @return Driver
     */
    public function setBizProvince($bizProvince): Driver
    {
        $this->bizProvince = $bizProvince;
        return $this;
    }

    /**
     * Get business country
     *
     * @return string
     */
    public function getBizCountry()
    {
        return $this->bizCountry;
    }

    /**
     * Set business country
     *
     * @param string $bizCountry
     * @return Driver
     */
    public function setBizCountry($bizCountry): Driver
    {
        $this->bizCountry = $bizCountry;
        return $this;
    }

    /**
     * Get business telephone
     *
     * @return string
     */
    public function getBizTel()
    {
        return $this->bizTel;
    }

    /**
     * Set business telephone
     *
     * @param string $bizTel
     * @return Driver
     */
    public function setBizTel($bizTel): Driver
    {
        $this->bizTel = $bizTel;
        return $this;
    }

    /**
     * Get transportation types
     *
     * @return string
     */
    public function getTransportationTypes()
    {
        return $this->decrypt($this->transportationTypes, self::ENCRYPT_TYPE_JSON, []);
    }

    /**
     * Set transportation types
     *
     * @param string $transportationTypes
     * @return Driver
     */
    public function setTransportationTypes($transportationTypes): Driver
    {
        $this->transportationTypes = $this->encrypt(is_array($transportationTypes) ? $transportationTypes : []);
        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param int $type
     * @return Driver
     */
    public function setType($type): Driver
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get commission
     *
     * @return float
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * Set commission
     *
     * @param float $commission
     * @return Driver
     */
    public function setCommission($commission): Driver
    {
        $this->commission = $commission;
        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return Driver
     */
    public function setNotes($notes): Driver
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param \DateTime $createdAt
     * @return Driver
     */
    public function setCreatedAt(\DateTime $createdAt): Driver
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return \DateTime|null
     */
    public function getModifiedAt(): ?\DateTime
    {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param \DateTime $modifiedAt
     * @return Driver
     */
    public function setModifiedAt(\DateTime $modifiedAt): Driver
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->setModifiedAt(new \DateTime());
    }

    /**
     * Validate dynamic required fields
     *
     * @param ExecutionContextInterface $context
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (count($this->getTransportationTypes()) == 0) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('transportationTypes')
                ->addViolation();
        }

        if ($this->getType() && $this->getType() != self::TYPE_DRIVER) {
            if ($this->getCommission() <= 0) {
                $context->buildViolation('This value should greater than 0.')
                    ->atPath('commission')
                    ->addViolation();
            } elseif ($this->getCommission() > 100) {
                $context->buildViolation('This value should less than or equal 100.')
                    ->atPath('commission')
                    ->addViolation();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

}
