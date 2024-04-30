<?php

namespace App\Wicrew\ActivityBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BasePriceEntity;
use App\Wicrew\CoreBundle\Entity\BasePriceEntityImplementation;
use App\Wicrew\CoreBundle\Entity\IBasePriceEntity;
use App\Wicrew\PageBundle\Entity\PageContent;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\SaleBundle\Entity\Tax;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Activity
 *
 * @ORM\Table(name="Activity", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 *     @ORM\Index(name="fk_Activity_Area_idx", columns={"area_id"}),
 *     @ORM\Index(name="fk_Activity_Transportation_Tax_idx", columns={"transportation_tax_id"}),
 *     @ORM\Index(name="fk_Activity_ActivityLocation_idx", columns={"location_id"}),
 *     @ORM\Index(name="fk_Activity_Partner_idx", columns={"driver_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Wicrew\ActivityBundle\Repository\ActivityRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class Activity extends PageContent implements IBasePriceEntity {
    use BasePriceEntityImplementation;

    /**
     * Types
     */
    public const TYPE_GROUP = 1;
    public const TYPE_PRIVATE = 2;


    /**
     * Catagories
     */
    public const CATAGORY_ADVENTURE = 1;
    public const CATAGORY_NATURE = 2;
    public const CATAGORY_CULTURAL = 3;
    public const CATAGORY_WATER = 4;
   

    /**
     * Difficulty
     */
    public const DIFFICULTY_EASY = 1;
    public const DIFFICULTY_MODERATE = 2;
    public const DIFFICULTY_DIFFICULT = 3;

    /**
     * Durations
     */
    public const DURATION_1_2_HOURS = '1-2 hours';
    public const DURATION_HALF_DAY = 'Half day';
    public const DURATION_FULL_DAY = 'Full day';

    /**
     * Statuses
     */
    public const STATUS_OFFLINE = 0;
    public const STATUS_ONLINE = 1;

    public const PRICE_TYPE_MIXED = 3;

    /**
     * Name
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * Area
     *
     * @var Area
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ProductBundle\Entity\Area", cascade={"persist"})
     * @ORM\JoinColumn(name="area_id", referencedColumnName="id")
     */
    private $area;

    /**
     * Location
     *
     * @var ActivityLocation
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ActivityBundle\Entity\ActivityLocation", cascade={"persist"})
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     */
    private $location;

    /**
     * Difficulty levels
     *
     * @var string
     *
     * @ORM\Column(name="difficulty_levels", type="string", length=16, nullable=false, options={"comment"="Mixed with comma separator: 1 = Easy, 2 = Moderate, 3 = Difficult"})
     */
    private $difficultyLevels = '[]';

    /**
     * Age group
     *
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="age_group", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $ageGroup;

    /**
     * Tour time
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="tour_time", type="string", length=255, nullable=true)
     */
    private $tourTime = '[]';

    /**
     * Notes to display to the customer
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="notes_to_display_to_the_customer", type="text", length=65535, nullable=true)
     */
    private $notesToDisplayToTheCustomer;


    /**
     * Duration
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="duration", type="string", length=32, nullable=false)
     */
    private $duration;

    /**
     * Driver
     *
     * @var Partner
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\PartnerBundle\Entity\Partner", cascade={"persist"})
     * @ORM\JoinColumn(name="driver_id", referencedColumnName="id")
     */
    private $driver;

    /**
     * Transportation required
     *
     * @var bool
     *
     * @ORM\Column(name="transportation_required", type="boolean", nullable=false, options={"comment"="0 = No, 1 = Yes"})
     */
    private $transportationRequired = false;

    /**
     * Transport adult rack price
     *
     * @var float
     *
     * @ORM\Column(name="transport_adult_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $transportAdultRackPrice = 0.00;

    /**
     * Transport adult net price
     *
     * @var float
     *
     * @ORM\Column(name="transport_adult_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $transportAdultNetPrice = 0.00;

    /**
     * Transport kid rack price
     *
     * @var float
     *
     * @ORM\Column(name="transport_kid_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $transportKidRackPrice = 0.00;

    /**
     * Transport kid net price
     *
     * @var float
     *
     * @ORM\Column(name="transport_kid_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $transportKidNetPrice = 0.00;

    /**
     * Transportation tax
     *
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Tax", cascade={"persist"})
     * @ORM\JoinColumn(name="transportation_tax_id", referencedColumnName="id")
     */
    private $transportationTax;

    /**
     * Description
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * High light
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="highlight", type="text", length=65535, nullable=true)
     */
    private $highlight;

    /**
     * Included
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="included", type="text", length=65535, nullable=true)
     */
    private $included;

    /**
     * What to bring
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="what_to_bring", type="text", length=65535, nullable=true)
     */
    private $whatToBring;

    /**
     * Important notes
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="important_notes", type="text", length=65535, nullable=true)
     */
    private $importantNotes;

    /**
     * Cancellation policy
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="cancellation_policy", type="text", length=65535, nullable=true)
     */
    private $cancellationPolicy;

    /**
     * Image
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * Image binary
     *
     * @var File
     *
     * @Assert\File(maxSize = "8m")
     * @Assert\Image(
     *     mimeTypesMessage = "validate.error.invalid.image",
     *     detectCorrupted = true,
     *     corruptedMessage = "validate.error.image.corrupted"
     * )
     *
     * @Vich\UploadableField(mapping="activity.image", fileNameProperty="image")
     */
    protected $imageFile;

    /**
     * Combo
     *
     * @var bool
     *
     * @ORM\Column(name="combo", type="boolean", nullable=false, options={"comment"="0 = No, 1 = Yes"})
     */
    private $combo = false;

    /**
     * Visibility
     *
     * @var bool
     *
     * @ORM\Column(name="visibility", type="boolean", nullable=false, options={"default"="1","comment"="0 = Not visible, 1 = Visible"})
     */
    private $visibility = true;

    /**
     * Status
     *
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean", nullable=false, options={"default"="1","comment"="0 = Offline, 1 = Online"})
     */
    private $status = self::STATUS_ONLINE;

    /**
     * Archive status
     *
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean", nullable=false, options={"default"="0"})
     */
    private $archived = false;

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
     * Slides
     *
     * @var Collection|ActivitySlide[]
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\ActivityBundle\Entity\ActivitySlide", mappedBy="activity", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    private $slides;

    /**
     * Childs
     *
     * @var Collection|ActivityHasChild[]
     *
     * @Assert\Valid()
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\ActivityBundle\Entity\ActivityHasChild", mappedBy="combo", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $childs;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new DateTime());

        $this->setSlides(new ArrayCollection());
        $this->setChilds(new ArrayCollection());
        $this->slides = new ArrayCollection();
        $this->childs = new ArrayCollection();
    }

    public function __clone() {
        $this->setCreatedAt(new DateTime());
        $this->setModifiedAt($this->getCreatedAt());

        $slidesClone = new ArrayCollection();
        foreach ($this->getSlides() as $slide) {
            $slideClone = clone $slide;
            $slideClone->setActivity($this);
            $slidesClone->add($slideClone);
        }
        $this->setSlides($slidesClone);

        $childsClone = new ArrayCollection();
        foreach ($this->getChilds() as $child) {
            $childClone = clone $child;
            $childClone->setCombo($this);
            $childsClone->add($childClone);
        }
        $this->setChilds($childsClone);

        parent::__clone();
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string {
        return self::PAGE_CONTENT_TYPE_ACTIVITY;
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
     * @return Activity
     */
    public function setName($name): Activity {
        $this->name = $name;
        return $this;
    }

    /**
     * Get area
     *
     * @return Area
     */
    public function getArea() {
        return $this->area;
    }

    /**
     * Set area
     *
     * @param Area $area
     *
     * @return Activity
     */
    public function setArea(Area $area): Activity {
        $this->area = $area;
        return $this;
    }

    /**
     * Get location
     *
     * @return ActivityLocation
     */
    public function getLocation() {
        return $this->location;
    }

    /**
     * Set location
     *
     * @param ActivityLocation $location
     *
     * @return Activity
     */
    public function setLocation(ActivityLocation $location): Activity {
        $this->location = $location;
        return $this;
    }

    /**
     * Get difficulty levels
     *
     * @return array
     */
    public function getDifficultyLevels() {

        return $this->decrypt($this->difficultyLevels, self::ENCRYPT_TYPE_JSON, []);
    }

    /**
     * Set difficulty levels
     *
     * @param array $difficultyLevels
     *
     * @return Activity
     */
    public function setDifficultyLevels($difficultyLevels): Activity {
        $this->difficultyLevels = $this->encrypt(is_array($difficultyLevels) ? $difficultyLevels : []);
        return $this;
    }

    /**
     * Get age group
     *
     * @return int
     */
    public function getAgeGroup() {
        return $this->ageGroup;
    }

    /**
     * Set age group
     *
     * @param int $ageGroup
     *
     * @return Activity
     */
    public function setAgeGroup($ageGroup): Activity {
        $this->ageGroup = $ageGroup;
        return $this;
    }

    /**
     * Get tour time
     *
     * @return array
     */
    public function getTourTime() {
        return $this->decrypt($this->tourTime);
    }

    /**
     * Set tour time
     *
     * @param array $tourTime
     *
     * @return Activity
     */
    public function setTourTime($tourTime): Activity {
        $this->tourTime = $this->encrypt($tourTime);
        return $this;
    }

    /**
     * Get notes to display to the customer
     *
     * @return string|null
     */
    public function getNotesToDisplayToTheCustomer() {
        return $this->notesToDisplayToTheCustomer;
    }

    /**
     * Set notes to display to the customer
     *
     * @param string|null $notesToDisplayToTheCustomer
     *
     * @return Activity
     */
    public function setNotesToDisplayToTheCustomer($notesToDisplayToTheCustomer): Activity {
        $this->notesToDisplayToTheCustomer = $notesToDisplayToTheCustomer;
        return $this;
    }

    /**
     * Get duration
     *
     * @return string
     */
    public function getDuration() {
        return $this->duration;
    }

    /**
     * Set duration
     *
     * @param string $duration
     *
     * @return Activity
     */
    public function setDuration($duration): Activity {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Get driver
     *
     * @return Partner
     */
    public function getDriver() {
        return $this->driver;
    }


    /**
     * Set driver
     *
     * @param Partner $driver
     *
     * @return Activity
     */
    public function setDriver(Partner $driver): Activity {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get transportation required
     *
     * @return bool
     */
    public function isTransportationRequired() {
        return filter_var($this->transportationRequired, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Set transportation required
     *
     * @param bool $transportationRequired
     *
     * @return Activity
     */
    public function setTransportationRequired($transportationRequired): Activity {
        $this->transportationRequired = filter_var($transportationRequired, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * Get transport adult rack price
     *
     * @return float
     */
    public function getTransportAdultRackPrice() {
        return $this->transportAdultRackPrice;
    }

    /**
     * Set transport adult rack price
     *
     * @param float $transportAdultRackPrice
     *
     * @return Activity
     */
    public function setTransportAdultRackPrice($transportAdultRackPrice): Activity {
        $this->transportAdultRackPrice = $transportAdultRackPrice;
        return $this;
    }

    /**
     * Get transport adult net price
     *
     * @return float
     */
    public function getTransportAdultNetPrice() {
        return $this->transportAdultNetPrice;
    }

    /**
     * Set transport adult net price
     *
     * @param float $transportAdultNetPrice
     *
     * @return Activity
     */
    public function setTransportAdultNetPrice($transportAdultNetPrice): Activity {
        $this->transportAdultNetPrice = $transportAdultNetPrice;
        return $this;
    }

    /**
     * Get transport kid rack price
     *
     * @return float
     */
    public function getTransportKidRackPrice() {
        return $this->transportKidRackPrice;
    }

    /**
     * Set transport kid rack price
     *
     * @param float $transportKidRackPrice
     *
     * @return Activity
     */
    public function setTransportKidRackPrice($transportKidRackPrice): Activity {
        $this->transportKidRackPrice = $transportKidRackPrice;
        return $this;
    }

    /**
     * Get transport kid net price
     *
     * @return float
     */
    public function getTransportKidNetPrice() {
        return $this->transportKidNetPrice;
    }

    /**
     * Set transport kid net price
     *
     * @param float $transportKidNetPrice
     *
     * @return Activity
     */
    public function setTransportKidNetPrice($transportKidNetPrice): Activity {
        $this->transportKidNetPrice = $transportKidNetPrice;
        return $this;
    }

    /**
     * Get transportation tax
     *
     * @return Tax|null
     */
    public function getTransportationTax(): ?Tax {
        return $this->transportationTax;
    }

    /**
     * Set transportation tax
     *
     * @param Tax|null $transportationTax
     *
     * @return Activity
     */
    public function setTransportationTax(?Tax $transportationTax): Activity {
        $this->transportationTax = $transportationTax;
        return $this;
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string|null $description
     *
     * @return Activity
     */
    public function setDescription($description): Activity {
        $this->description = $description;
        return $this;
    }

    /**
     * Get highlight
     *
     * @return string|null
     */
    public function getHighlight() {
        return $this->highlight;
    }

    /**
     * Set highlight
     *
     * @param string|null $highlight
     *
     * @return Activity
     */
    public function setHighlight($highlight): Activity {
        $this->highlight = $highlight;
        return $this;
    }

    /**
     * Get included
     *
     * @return string|null
     */
    public function getIncluded() {
        return $this->included;
    }

    /**
     * Set included
     *
     * @param string|null $included
     *
     * @return Activity
     */
    public function setIncluded($included): Activity {
        $this->included = $included;
        return $this;
    }

    /**
     * Get what to bring
     *
     * @return string|null
     */
    public function getWhatToBring() {
        return $this->whatToBring;
    }

    /**
     * Set what to bring
     *
     * @param string|null $whatToBring
     *
     * @return Activity
     */
    public function setWhatToBring($whatToBring): Activity {
        $this->whatToBring = $whatToBring;
        return $this;
    }

    /**
     * Get important notes
     *
     * @return string|null
     */
    public function getImportantNotes() {
        return $this->importantNotes;
    }

    /**
     * Set important note
     *
     * @param string|null $importantNotes
     *
     * @return Activity
     */
    public function setImportantNotes($importantNotes): Activity {
        $this->importantNotes = $importantNotes;
        return $this;
    }

    /**
     * Get important notes
     *
     * @return string|null
     */
    public function getCancellationPolicy() {
        return $this->cancellationPolicy;
    }

    /**
     * Set important notes
     *
     * @param string|null $cancellationPolicy
     *
     * @return Activity
     */
    public function setCancellationPolicy($cancellationPolicy): Activity {
        $this->cancellationPolicy = $cancellationPolicy;
        return $this;
    }

    /**
     * Get image
     *
     * @return string|null
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set image
     *
     * @param string|null $image
     *
     * @return Activity
     */
    public function setImage($image): Activity {
        $this->image = $image;
        return $this;
    }

    /**
     * Get image file
     *
     * @return File
     */
    public function getImageFile() {
        return $this->imageFile;
    }

    /**
     * Set image file
     *
     * @param File $imageFile
     *
     * @return Activity
     */
    public function setImageFile($imageFile): Activity {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->setModifiedAt(new DateTime());
        }
        return $this;
    }

    /**
     * Get combo
     *
     * @return bool
     */
    public function isCombo() {
        return $this->combo;
    }

    /**
     * Set combo
     *
     * @param bool $combo
     *
     * @return Activity
     */
    public function setCombo($combo): Activity {
        $this->combo = filter_var($combo, FILTER_VALIDATE_BOOLEAN);

        if (!$this->combo) {
            $this->getChilds()->clear();
        }

        return $this;
    }

    /**
     * Get visibility
     *
     * @return int
     */
    public function isVisibility() {
        return $this->visibility;
    }

    /**
     * Set visibility
     *
     * @param int $visibility
     *
     * @return Activity
     */
    public function setVisibility($visibility): Activity {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Get status
     *
     * @param int $status
     *
     * @return Activity
     */
    public function setStatus($status): Activity {
        $this->status = $status;
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
     * @return Activity
     */
    public function setCreatedAt(DateTime $createdAt): Activity {
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
     * @return Activity
     */
    public function setModifiedAt(DateTime $modifiedAt): Activity {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Get slides
     *
     * @return Collection|ActivitySlide[]
     */
    public function getSlides() {
        return $this->slides;
    }

    /**
     * Set slides
     *
     * @param Collection|ActivitySlide[] $slides
     *
     * @return Activity
     */
    public function setSlides(Collection $slides): Activity {
        $this->slides = $slides;
        return $this;
    }

    /**
     * Add slide
     *
     * @param ActivitySlide $slide
     *
     * @return Activity
     */
    public function addSlide(ActivitySlide $slide): Activity {
        if (!$this->getSlides()->contains($slide)) {
            $slide->setActivity($this);
            $this->getSlides()->add($slide);
        }

        return $this;
    }

    /**
     * Remove slide
     *
     * @param ActivitySlide $slide
     *
     * @return Activity
     */
    public function removeSlide(ActivitySlide $slide): Activity {
        if ($this->getSlides()->contains($slide)) {
            if ($slide->getActivity() === $this) {
                $slide->setActivity(null);
            }
            $this->getSlides()->removeElement($slide);
        }

        return $this;
    }

    /**
     * Get childs
     *
     * @return Collection|ActivityHasChild[]
     */
    public function getChilds(): Collection {
        return $this->childs;
    }

    /**
     * Set childs
     *
     * @param Collection|ActivityHasChild[] $childs
     *
     * @return Activity
     */
    public function setChilds(Collection $childs): Activity {
        if (!$this->isCombo() && count($childs) > 0) {
            return $this;
        }

        $this->childs = $childs;
        return $this;
    }

    /**
     * Add child
     *
     * @param ActivityHasChild $child
     *
     * @return Activity
     */
    public function addChild(ActivityHasChild $child): Activity {
        if (!$this->isCombo()) {
            return $this;
        }

        if (!$this->getChilds()->contains($child)) {
            $child->setCombo($this);
            $this->getChilds()->add($child);
        }

        return $this;
    }

    /**
     * Remove child
     *
     * @param ActivityHasChild $child
     *
     * @return Activity
     */
    public function removeChild(ActivityHasChild $child): Activity {
        if ($this->getChilds()->contains($child)) {
            if ($child->getCombo() === $this) {
                $child->setCombo(null);
            }
            $this->getChilds()->removeElement($child);
        }

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

    /**
     * Validate dynamic required fields
     *
     * @param ExecutionContextInterface $context
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context) {
        if (count($this->getSlides()) == 0) {
            $context->buildViolation('Need at least one slide image.')
                ->atPath('slides')
                ->addViolation();
        }

        if ($this->isCombo() && count($this->getChilds()) == 0) {
            $context->buildViolation('Need at least one child activity.')
                ->atPath('combo')
                ->addViolation();
        }

        if (count($this->getTypes()) == 0) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('types')
                ->addViolation();
        }

        if (count($this->getDifficultyLevels()) == 0) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('difficultyLevels')
                ->addViolation();
        }

        if (count($this->getTourTime()) == 0) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('tourTime')
                ->addViolation();
        }

        if (!$this->isCombo() && !$this->getTax()) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('tax')
                ->addViolation();
        }
    }

    public function getTransportationRequired(): ?bool
    {
        return $this->transportationRequired;
    }

    public function getCombo(): ?bool
    {
        return $this->combo;
    }

    public function getVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function getArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Sorting order
     *
     * @var int
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=false, options={"default"="0"})
     */
    private $sortOrder = 0;

    /**
     * Get sort_order
     *
     * @return int
     */
    public function getSortOrder() {
        return $this->sortOrder;
    }

    /**
     * Set sort_order
     *
     * @param int $sortOrder
     *
     * @return Activity
     */
    public function setSortOrder($sortOrder): Activity {
        $this->sortOrder = $sortOrder;
        return $this;
    }


    /**
     * group adult rack price
     *
     * @var float
     *
     * @ORM\Column(name="group_adult_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $groupAdultRackPrice = 0.00;

    /**
     * group adult net price
     *
     * @var float
     *
     * @ORM\Column(name="group_adult_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $groupAdultNetPrice = 0.00;

    /**
     * group kid rack price
     *
     * @var float
     *
     * @ORM\Column(name="group_child_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $groupKidRackPrice = 0.00;

    /**
     * group kid net price
     *
     * @var float
     *
     * @ORM\Column(name="group_child_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $groupKidNetPrice = 0.00;

    /**
     * Get Group adult rack price
     *
     * @return float
     */
    public function getGroupAdultRackPrice() {
        return $this->groupAdultRackPrice;
    }

    /**
     * Set Group adult rack price
     *
     * @param float $groupAdultRackPrice
     *
     * @return Activity
     */
    public function setGroupAdultRackPrice($groupAdultRackPrice): Activity {
        $this->groupAdultRackPrice = $groupAdultRackPrice;
        return $this;
    }

    /**
     * Get Group adult net price
     *
     * @return float
     */
    public function getGroupAdultNetPrice() {
        return $this->groupAdultNetPrice;
    }

    /**
     * Set Group adult net price
     *
     * @param float $groupAdultNetPrice
     *
     * @return Activity
     */
    public function setGroupAdultNetPrice($groupAdultNetPrice): Activity {
        $this->groupAdultNetPrice = $groupAdultNetPrice;
        return $this;
    }

    /**
     * Get group kid rack price
     *
     * @return float
     */
    public function getGroupKidRackPrice() {
        return $this->groupKidRackPrice;
    }

    /**
     * Set group kid rack price
     *
     * @param float $groupKidRackPrice
     *
     * @return Activity
     */
    public function setGroupKidRackPrice($groupKidRackPrice): Activity {
        $this->groupKidRackPrice = $groupKidRackPrice;
        return $this;
    }

    /**
     * Get group kid net price
     *
     * @return float
     */
    public function getGroupKidNetPrice() {
        return $this->groupKidNetPrice;
    }

    /**
     * Set group kid net price
     *
     * @param float $groupKidNetPrice
     *
     * @return Activity
     */
    public function setGroupKidNetPrice($groupKidNetPrice): Activity {
        $this->groupKidNetPrice = $groupKidNetPrice;
        return $this;
    }


    /**
     * Catagories
     *
     * @var string
     *
     * @ORM\Column(name="catagories", type="string", length=16, nullable=false, options={"comment"="Mixed with comma separator: 1 = Adventure, 2 = Nature, 3 = Cultural, 4 = Water"})
     */
    private $catagories = '[]';


    /**
     * Get catagories
     *
     * @return array
     */
    public function getCatagories() {
        return $this->decrypt($this->catagories, self::ENCRYPT_TYPE_JSON, []);
    }

    /**
     * Set catagories
     *
     * @param array $catagories
     *
     * @return Activity
     */
    public function setCatagories($catagories): Activity {
        $this->catagories = $this->encrypt(is_array($catagories) ? $catagories : []);
        return $this;
    }
    


    /**
     * Types
     *
     * @var string
     *
     * @ORM\Column(name="types", type="string", length=16, nullable=false, options={"comment"="Mixed with comma separator: 1 = Group, 2 = Private"})
     */
    private $types = '[]';


    /**
     * Get types
     *
     * @return array
     */
    public function getTypes() {
        return $this->decrypt($this->types, self::ENCRYPT_TYPE_JSON, []);
    }

    /**
     * Set types
     *
     * @param array $types
     *
     * @return Activity
     */
    public function setTypes($types): Activity {
        $this->types = $this->encrypt(is_array($types) ? $types : []);
        return $this;
    }
    
    public function __toString() {
        return $this->getName();
    }


}
