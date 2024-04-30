<?php

namespace App\Wicrew\PartnerBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Partner
 *
 * @ORM\Table(name="Partner", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Partner extends BaseEntity {

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
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    private $firstname;

    /**
     * Last name
     *
     * @var string
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * Telephone
     *
     * @var string
     *
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="tel", type="string", length=32, nullable=true)
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
     * @Assert\Email()
     * @Assert\Length(max = 50)
     *
     * @ORM\Column(name="email", type="string", length=50, nullable=true)
     */
    private $email;

    /**
     * Legal business name
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
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="biz_address", type="text", length=65535, nullable=true)
     */
    private $bizAddress;

    /**
     * Business city
     *
     * @var string
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="biz_city", type="string", length=255, nullable=true)
     */
    private $bizCity;

    /**
     * Business province
     *
     * @var string
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="biz_province", type="string", length=255, nullable=true)
     */
    private $bizProvince;

    /**
     * Business country
     *
     * @var string
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="biz_country", type="string", length=255, nullable=true)
     */
    private $bizCountry;

    /**
     * Business telephone
     *
     * @var string
     *
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="biz_tel", type="string", length=32, nullable=true)
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
     * @ORM\Column(name="type", type="integer", nullable=false, options={"comment"="0 = None, 1 = Driver, 2 = Affiliate, 3 = Supplier, 4 = Travel agent, 5 = Partner"})
     */
    private $type;

    /**
     * Commission
     *
     * @var string
     *
     * @Assert\PositiveOrZero()
     * @Assert\LessThanOrEqual(100)
     *
     * @ORM\Column(name="commission", type="decimal", precision=4, scale=2, nullable=false, options={"default"="0.00","comment"="Percentage"})
     */
    private $commission = '0.00';

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
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * Modified at
     *
     * @var DateTime|null
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());
    }

    /**
     * Get ID
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    public function setId($id): Partner {
        $this->id = $id;
        return $this;
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Set first name
     *
     * @param string $firstname
     *
     * @return Partner
     */
    public function setFirstname($firstname): Partner {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Set last name
     *
     * @param string $lastname
     *
     * @return Partner
     */
    public function setLastname($lastname): Partner {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTel() {
        return $this->tel;
    }

    /**
     * Set telephone
     *
     * @param string $tel
     *
     * @return Partner
     */
    public function setTel($tel): Partner {
        $this->tel = $tel;
        return $this;
    }

    /**
     * Get telephone ext.
     *
     * @return string
     */
    public function getTelExt() {
        return $this->telExt;
    }

    /**
     * Set telephone ext.
     *
     * @param string $telExt
     *
     * @return Partner
     */
    public function setTelExt($telExt): Partner {
        $this->telExt = $telExt;
        return $this;
    }

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Partner
     */
    public function setEmail($email): Partner {
        $this->email = $email;
        return $this;
    }

    /**
     * Get legal business name
     *
     * @return string
     */
    public function getBizName() {
        return $this->bizName;
    }

    /**
     * Set legal business name
     *
     * @param string $bizName
     *
     * @return Partner
     */
    public function setBizName($bizName): Partner {
        $this->bizName = $bizName;
        return $this;
    }

    /**
     * Get business address
     *
     * @return string
     */
    public function getBizAddress() {
        return $this->bizAddress;
    }

    /**
     * Set business address
     *
     * @param string $bizAddress
     *
     * @return Partner
     */
    public function setBizAddress($bizAddress): Partner {
        $this->bizAddress = $bizAddress;
        return $this;
    }

    /**
     * Get business city
     *
     * @return string
     */
    public function getBizCity() {
        return $this->bizCity;
    }

    /**
     * Set business city
     *
     * @param string $bizCity
     *
     * @return Partner
     */
    public function setBizCity($bizCity): Partner {
        $this->bizCity = $bizCity;
        return $this;
    }

    /**
     * Get business province
     *
     * @return string
     */
    public function getBizProvince() {
        return $this->bizProvince;
    }

    /**
     * Set business province
     *
     * @param string $bizProvince
     *
     * @return Partner
     */
    public function setBizProvince($bizProvince): Partner {
        $this->bizProvince = $bizProvince;
        return $this;
    }

    /**
     * Get business country
     *
     * @return string
     */
    public function getBizCountry() {
        return $this->bizCountry;
    }

    /**
     * Set business country
     *
     * @param string $bizCountry
     *
     * @return Partner
     */
    public function setBizCountry($bizCountry): Partner {
        $this->bizCountry = $bizCountry;
        return $this;
    }

    /**
     * Get business telephone
     *
     * @return string
     */
    public function getBizTel() {
        return $this->bizTel;
    }

    /**
     * Set business telephone
     *
     * @param string $bizTel
     *
     * @return Partner
     */
    public function setBizTel($bizTel): Partner {
        $this->bizTel = $bizTel;
        return $this;
    }

    /**
     * Get transportation types
     *
     * @return string
     */
    public function getTransportationTypes() {
        return $this->decrypt($this->transportationTypes, self::ENCRYPT_TYPE_JSON, []);
    }

    /**
     * Set transportation types
     *
     * @param string $transportationTypes
     *
     * @return Partner
     */
    public function setTransportationTypes($transportationTypes): Partner {
        $this->transportationTypes = $this->encrypt(is_array($transportationTypes) ? $transportationTypes : []);
        return $this;
    }

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
     * @return Partner
     */
    public function setType($type): Partner {
        $this->type = $type;
        return $this;
    }

    /**
     * Get commission
     *
     * @return string
     */
    public function getCommission(): string {
        return $this->commission;
    }

    /**
     * Set commission
     *
     * @param string $commission
     *
     * @return Partner
     */
    public function setCommission(string $commission): Partner {
        $this->commission = $commission;
        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes() {
        return $this->notes;
    }

    /**
     * Set notes
     *
     * @param string $notes
     *
     * @return Partner
     */
    public function setNotes($notes): Partner {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Get created at
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param DateTime $createdAt
     *
     * @return Partner
     */
    public function setCreatedAt(DateTime $createdAt): Partner {
        $this->createdAt = $createdAt;
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
     * @param DateTime $modifiedAt
     *
     * @return Partner
     */
    public function setModifiedAt(DateTime $modifiedAt): Partner {
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

    public function __toString() {
        return $this->getBizName();
    }

}
