<?php

namespace App\Wicrew\PageBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\PageBundle\Validator\Constraints as WicrewPageAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PageContent
 *
 * @ORM\Table(name="PageContent")
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="page_type", type="string")
 * @ORM\DiscriminatorMap({
 *     "page" = "App\Wicrew\PageBundle\Entity\Page",
 *     "activity_location" = "App\Wicrew\ActivityBundle\Entity\ActivityLocation",
 *     "activity" = "App\Wicrew\ActivityBundle\Entity\Activity"
 * })
 * @UniqueEntity("slug")
 * @WicrewPageAssert\UniqueSlug
 */
abstract class PageContent extends BaseEntity {

    /**
     * Types
     */
    public const PAGE_CONTENT_TYPE_PAGE = 'page';
    public const PAGE_CONTENT_TYPE_ACTIVITY_LOCATION = 'activity_location';
    public const PAGE_CONTENT_TYPE_ACTIVITY = 'activity';

    /**
     * Get type
     *
     * @return string
     */
    abstract public function getType(): string;

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
     * Page title
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="page_title", type="string", length=255, nullable=false)
     */
    protected $pageTitle;

    /**
     * Page content
     *
     * @var string
     *
     * @Assert\Length(max = 16777215)
     *
     * @ORM\Column(name="page_content", type="text", length=16777215, nullable=true)
     */
    protected $pageContent;

    /**
     * Slug
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     * @Assert\Regex("/^[a-zA-Z0-9\-\_]+$/")
     *
     * @ORM\Column(name="slug", type="string", length=255, nullable=false, unique=true)
     */
    protected $slug;

    /**
     * Meta title
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="meta_title", type="string", length=255, nullable=true)
     */
    protected $metaTitle;

    /**
     * Meta description
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="meta_description", type="string", length=255, nullable=true)
     */
    protected $metaDescription;

    /**
     * Meta keywords
     *
     * @var string|null
     *
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="meta_keywords", type="string", length=255, nullable=true)
     */
    protected $metaKeywords;

    /**
     * CSS
     *
     * @var string|null
     *
     * @Assert\Length(max = 16777215)
     *
     * @ORM\Column(name="css", type="text", length=16777215, nullable=true)
     */
    protected $css;

    /**
     * JS
     *
     * @var string|null
     *
     * @Assert\Length(max = 16777215)
     *
     * @ORM\Column(name="js", type="text", length=16777215, nullable=true)
     */
    protected $js;

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
     * @return PageContent
     */
    public function setId($id): PageContent {
        $this->id = $id;
        return $this;
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getPageTitle() {
        return $this->pageTitle;
    }

    /**
     * Set page title
     *
     * @param string $pageTitle
     *
     * @return PageContent
     */
    public function setPageTitle($pageTitle): PageContent {
        $this->pageTitle = $pageTitle;
        return $this;
    }

    /**
     * Get page content
     *
     * @return string|null
     */
    public function getPageContent() {
        return $this->pageContent;
    }

    /**
     * Set page content
     *
     * @param string|null $pageContent
     *
     * @return PageContent
     */
    public function setPageContent($pageContent): PageContent {
        $this->pageContent = $pageContent;
        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return PageContent
     */
    public function setSlug($slug): PageContent {
        $this->slug = strtolower($slug);
        return $this;
    }

    /**
     * Get meta title
     *
     * @return string|null
     */
    public function getMetaTitle() {
        return $this->metaTitle;
    }

    /**
     * Set meta title
     *
     * @param string|null $metaTitle
     *
     * @return PageContent
     */
    public function setMetaTitle($metaTitle): PageContent {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    /**
     * Get meta description
     *
     * @return string
     */
    public function getMetaDescription() {
        return $this->metaDescription;
    }

    /**
     * Set meta description
     *
     * @param string $metaDescription
     *
     * @return PageContent
     */
    public function setMetaDescription($metaDescription): PageContent {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    /**
     * Get meta keywords
     *
     * @return string
     */
    public function getMetaKeywords() {
        return $this->metaKeywords;
    }

    /**
     * Set meta keyword
     *
     * @param string $metaKeywords
     *
     * @return PageContent
     */
    public function setMetaKeywords($metaKeywords): PageContent {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    /**
     * Get CSS
     *
     * @return string|null
     */
    public function getCss() {
        return $this->css;
    }

    /**
     * Set CSS
     *
     * @param string|null $css
     *
     * @return PageContent
     */
    public function setCss($css): PageContent {
        $this->css = $css;
        return $this;
    }

    /**
     * Get JS
     *
     * @return string|null
     */
    public function getJs() {
        return $this->js;
    }

    /**
     * Set JS
     *
     * @param string|null $js
     *
     * @return PageContent
     */
    public function setJs($js): PageContent {
        $this->js = $js;
        return $this;
    }

}
