<?php

namespace App\Wicrew\ActivityBundle\Entity;

use App\Wicrew\PageBundle\Entity\PageContent;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * ActivityLocation
 *
 * @ORM\Table(name="ActivityLocation", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity(repositoryClass="App\Wicrew\ActivityBundle\Repository\ActivityLocationRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class ActivityLocation extends PageContent {

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
     *     mimeTypesMessage = "error.invalid.image",
     *     detectCorrupted = true,
     *     corruptedMessage = "error.image.corrupted"
     * )
     *
     * @Vich\UploadableField(mapping="activity_location.image", fileNameProperty="image")
     */
    protected $imageFile;

    /**
     * Custom order
     *
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="custom_order", type="integer", nullable=false)
     */
    private $customOrder;

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
    public function __construct() {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string {
        return self::PAGE_CONTENT_TYPE_ACTIVITY_LOCATION;
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
     * @return ActivityLocation
     */
    public function setName($name): ActivityLocation {
        $this->name = $name;

        $this->setPageTitle($name);

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
     * @return ActivityLocation
     */
    public function setImage($image): ActivityLocation {
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
     * @return ActivityLocation
     */
    public function setImageFile($imageFile): ActivityLocation {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->setModifiedAt(new \DateTime());
        }
        return $this;
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getPageTitle() {
        return parent::getPageTitle() ?: $this->getName();
    }

    /**
     * Get custom order
     *
     * @return int
     */
    public function getCustomOrder() {
        return $this->customOrder;
    }

    /**
     * Set custom order
     *
     * @param int $customOrder
     *
     * @return ActivityLocation
     */
    public function setCustomOrder($customOrder): ActivityLocation {
        $this->customOrder = $customOrder;
        return $this;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param \DateTime $createdAt
     *
     * @return ActivityLocation
     */
    public function setCreatedAt(\DateTime $createdAt): ActivityLocation {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get modified at
     *
     * @return \DateTime|null
     */
    public function getModifiedAt() {
        return $this->modifiedAt;
    }

    /**
     * Set modified at
     *
     * @param \DateTime|null $modifiedAt
     *
     * @return ActivityLocation
     */
    public function setModifiedAt(?\DateTime $modifiedAt): ActivityLocation {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Gets triggered only on insert
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $args) {
        $this->setModifiedAt(new \DateTime());
    }

}
