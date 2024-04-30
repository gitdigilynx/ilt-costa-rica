<?php

namespace App\Wicrew\PageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Page
 *
 * @ORM\Table(name="Page", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={@ORM\Index(name="fk_Page_Page_idx", columns={"parent_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Page extends PageContent {

    /**
     * Page
     *
     * @var Page
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\PageBundle\Entity\Page", inversedBy="childs", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

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
     * Enabled
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false, options={"default"="1","comment"="0 = No, 1 = Yes"})
     */
    private $enabled = true;

    /**
     * hide_header
     *
     * @var bool
     *
     * @ORM\Column(name="hide_header", type="boolean", nullable=false, options={"default"="0","comment"="0 = No, 1 = Yes"})
     */
    private $hideHeader = true;

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
     * Childs
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\PageBundle\Entity\Page", mappedBy="parent", cascade={"persist"}, orphanRemoval=false, fetch="EXTRA_LAZY")
     */
    private $childs;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setCreatedAt(new \DateTime());

        $this->setChilds(new ArrayCollection());
        $this->childs = new ArrayCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string {
        return self::PAGE_CONTENT_TYPE_PAGE;
    }

    /**
     * Get parent
     *
     * @return Page|null
     */
    public function getParent(): ?Page {
        return $this->parent;
    }

    /**
     * Set page
     *
     * @param Page|null $parent
     *
     * @return Page
     */
    public function setParent(?Page $parent): Page {
        $this->parent = $parent;
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
     * @return Page
     */
    public function setTitle($title): Page {
        $this->title = $title;
        return $this;
    }

    /**
     * Get enabled
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * Set enabled
     *
     * @param bool $enabled
     *
     * @return Page
     */
    public function setEnabled($enabled): Page {
        $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
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
     * @return Page
     */
    public function setCreatedAt(\DateTime $createdAt): Page {
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
     * @return Page
     */
    public function setModifiedAt(\DateTime $modifiedAt): Page {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Get childs
     *
     * @return ArrayCollection|array
     */
    public function getChilds() {
        return $this->childs;
    }

    /**
     * Set childs
     *
     * @param ArrayCollection|array $childs
     *
     * @return Page
     */
    public function setChilds($childs): Page {
        if (is_iterable($childs)) {
            $tmpCollection = $childs instanceof ArrayCollection ? $childs : new ArrayCollection($childs);
            foreach ($childs as &$child) {
                if ($child instanceof Page) {
                    $child->setParent($this);
                } else {
                    throw new \Exception('Invalid collection item');
                }
            }

            $this->childs = $tmpCollection;
        } else {
            throw new \Exception('Invalid collection');
        }

        return $this;
    }

    /**
     * Add child
     *
     * @param Page $child
     *
     * @return Page
     */
    public function addChild(Page $child): Page {
        $child->setParent($this);
        $this->getChilds()->add($child);

        return $this;
    }

    /**
     * Remove child
     *
     * @param Page $child
     *
     * @return Page
     */
    public function removeChild(Page $child): Page {
        foreach ($this->getChilds() as $k => $o) {
            if ($o->getId() == $child->getId()) {
                //                $child->setParent(null);
                $this->getChilds()->removeElement($child);
            }
        }

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

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function getHideHeader(): ?bool
    {
        return $this->hideHeader;
    }

    public function setHideHeader(bool $hideHeader): self
    {
        $this->hideHeader = $hideHeader;

        return $this;
    }

}
