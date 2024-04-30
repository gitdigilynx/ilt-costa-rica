<?php

namespace App\Wicrew\BlockBundle\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Block
 *
 * @ORM\Table(name="Block", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"}), @ORM\UniqueConstraint(name="identifier_UNIQUE", columns={"identifier"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity("identifier")
 */
class Block {

    /**
     * Types
     */
    public const TYPE_CONTENT = 1;
    public const TYPE_TEMPLATE = 2;

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
     * Identifier
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     * @Assert\Regex("/^[a-zA-Z0-9\-\_]+$/")
     *
     * @ORM\Column(name="identifier", type="string", length=255, nullable=false)
     */
    private $identifier;

    /**
     * Type
     *
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 1)
     *
     * @ORM\Column(name="type", type="integer", length=1, nullable=false, options={"default"="1","comment"="1 = Content, 2 = Template"})
     */
    private $type = self::TYPE_CONTENT;

    /**
     * Title
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * Content
     *
     * @var string|null
     *
     * @Assert\Length(max = 16777215)
     *
     * @ORM\Column(name="content", type="text", length=16777215, nullable=true)
     */
    private $content;

    /**
     * Custom template
     *
     * @var string
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="custom_template", type="string", length=255, nullable=true)
     */
    private $customTemplate;

    /**
     * Created at
     *
     * @var \DateTime
     *
     * @Assert\NotBlank()
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
     * @return Block
     */
    public function setId($id): Block {
        $this->id = $id;
        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return Block
     */
    public function setIdentifier($identifier): Block {
        $this->identifier = strtolower($identifier);
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
     * @return Block
     */
    public function setType($type): Block {
        $this->type = $type;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Block
     */
    public function setTitle($title): Block {
        $this->title = $title;
        return $this;
    }

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Set content
     *
     * @param string|null $content
     *
     * @return Block
     */
    public function setContent($content): Block {
        $this->content = $content;
        return $this;
    }

    /**
     * Get custom template
     *
     * @return string|null
     */
    public function getCustomTemplate() {
        return $this->customTemplate;
    }

    /**
     * Set custom template
     *
     * @param string|null $customTemplate
     *
     * @return Block
     */
    public function setCustomTemplate($customTemplate): Block {
        $this->customTemplate = $customTemplate;
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
     * @return Block
     */
    public function setCreatedAt(\DateTime $createdAt): Block {
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
     * @return Block
     */
    public function setModifiedAt(\DateTime $modifiedAt): Block {
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
     * Validate dynamic required fields
     *
     * @param ExecutionContextInterface $context
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context) {
        if ($this->getType() == self::TYPE_CONTENT && !$this->getContent()) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('content')
                ->addViolation();
        } else if ($this->getType() == self::TYPE_TEMPLATE && !$this->getCustomTemplate()) {
            $context->buildViolation('This value should not be blank.')
                ->atPath('customTemplate')
                ->addViolation();
        }
    }

}
