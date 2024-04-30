<?php

namespace App\Wicrew\ActivityBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BasePriceEntity;
use App\Wicrew\PartnerBundle\Entity\Partner;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ActivityHasChild
 *
 * @ORM\Table(name="ActivityHasChild", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})},
 *     indexes={
 *     @ORM\Index(name="fk_ActivityHasChild_Activity_Regular_idx", columns={"activity_combo_id"}),
 *     @ORM\Index(name="fk_ActivityHasChild_Activity_Combo_idx", columns={"activity_regular_id"}),
 *     @ORM\Index(name="fk_ActivityHasChild_Partner_idx", columns={"supplier_id"})
 * }
 *     )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ActivityHasChild extends BasePriceEntity {

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
     * Combo activity
     *
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ActivityBundle\Entity\Activity", inversedBy="childs", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="activity_combo_id", referencedColumnName="id")
     * })
     */
    private $combo;

    /**
     * Regular activity
     *
     * @var Activity
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ActivityBundle\Entity\Activity")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="activity_regular_id", referencedColumnName="id")
     * })
     */
    private $regular;

    /**
     * Supplier
     *
     * @var Partner
     *
     * @Assert\NotBlank()
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\PartnerBundle\Entity\Partner")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="supplier_id", referencedColumnName="id")
     * })
     */
    private $supplier;

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
     * @return ActivityHasChild
     */
    public function setId($id): ActivityHasChild {
        $this->id = $id;
        return $this;
    }

    /**
     * Get combo activity
     *
     * @return Activity
     */
    public function getCombo() {
        return $this->combo;
    }

    /**
     * Set combo activity
     *
     * @param Activity $combo
     *
     * @return ActivityHasChild
     */
    public function setCombo(?Activity $combo): ActivityHasChild {
        $this->combo = $combo;
        return $this;
    }

    /**
     * Get regular activity
     *
     * @return Activity
     */
    public function getRegular() {
        return $this->regular;
    }

    /**
     * Set regular activity
     *
     * @param Activity $regular
     *
     * @return ActivityHasChild
     */
    public function setRegular($regular): ActivityHasChild {
        $this->regular = $regular;
        return $this;
    }

    /**
     * Get supplier
     *
     * @return Partner
     */
    public function getSupplier() {
        return $this->supplier;
    }

    /**
     * Set supplier
     *
     * @param Partner $supplier
     *
     * @return ActivityHasChild
     */
    public function setSupplier($supplier): ActivityHasChild {
        $this->supplier = $supplier;
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
     * @return ActivityHasChild
     */
    public function setCreatedAt(DateTime $createdAt): ActivityHasChild {
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
     * @return ActivityHasChild
     */
    public function setModifiedAt(DateTime $modifiedAt): ActivityHasChild {
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
