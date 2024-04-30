<?php

namespace App\Wicrew\ActivityBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * ActivitySlide
 *
 * @ORM\Table(name="ActivitySlide", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={@ORM\Index(name="fk_ActivitySlide_Activity_idx", columns={"activity_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class ActivitySlide extends BaseEntity {

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
     * Activity
     *
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ActivityBundle\Entity\Activity", inversedBy="slides", cascade={"persist"})
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id")
     */
    private $activity;

    /**
     * Image
     *
     * @var string|null
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
     * Alt
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="alt", type="string", length=255, nullable=true)
     */
    private $alt;

    /**
     * Alt
     *
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false, options={ "default" = 1 })
     */
    private $position = 1;

    /**
     * @return int
     */
    public function getPosition(): int {
        return $this->position;
    }

    /**
     * @param int $position
     *
     * @return ActivitySlide
     */
    public function setPosition(int $position): ActivitySlide {
        $this->position = $position;
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

    public function __clone() {
        $this->setCreatedAt(new DateTime());
        $this->setModifiedAt($this->getCreatedAt());

        parent::__clone();
    }

    public function getId() {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return ActivitySlide
     */
    public function setId($id): ActivitySlide {
        $this->id = $id;
        return $this;
    }

    /**
     * Get activity
     *
     * @return Activity
     */
    public function getActivity(): Activity {
        return $this->activity;
    }

    /**
     * Set activity
     *
     * @param Activity $activity
     *
     * @return ActivitySlide
     */
    public function setActivity(?Activity $activity): ActivitySlide {
        $this->activity = $activity;
        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return ActivitySlide
     */
    public function setImage($image): ActivitySlide {
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
     * @return ActivitySlide
     */
    public function setImageFile($imageFile): ActivitySlide {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->setModifiedAt(new DateTime());
        }
        return $this;
    }

    /**
     * Get alt
     *
     * @return string|null
     */
    public function getAlt() {
        return $this->alt;
    }

    /**
     * Set alt
     *
     * @param string|null $alt
     *
     * @return ActivitySlide
     */
    public function setAlt($alt): ActivitySlide {
        $this->alt = $alt;
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
     * @return ActivitySlide
     */
    public function setCreatedAt(DateTime $createdAt): ActivitySlide {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return DateTime
     */
    public function getModifiedAt(): DateTime {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param DateTime $modifiedAt
     *
     * @return ActivitySlide
     */
    public function setModifiedAt(DateTime $modifiedAt): ActivitySlide {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Gets triggered only on update
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $args) {
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
        if ($this->getImage() === null && $this->getImageFile() === null) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('imageFile')
                ->addViolation();
        }
    }

}
