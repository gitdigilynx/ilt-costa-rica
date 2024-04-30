<?php

namespace App\Wicrew\DateAvailability\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use DateTime;


/**
 * HistoryLog
 *
 * @ORM\Table(name="HistoryLog", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity(repositoryClass="App\Wicrew\DateAvailability\Repository\HistoryLogRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class HistoryLog extends BaseEntity
{

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
     * Get ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return HistoryLog
     */
    public function setId($id): HistoryLog
    {
        $this->id = $id;
        return $this;
    }



    /**
     * Created at
     *
     * @var DateTime
     *
     * @ORM\Column(name="date_time", type="datetime", nullable=false)
     */
    private $createdAt;

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
     * @return HistoryLog
     */
    public function setCreatedAt(DateTime $createdAt): HistoryLog {
        $this->createdAt = $createdAt;
        return $this;
    }


    /**
     * User
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="fos_user", referencedColumnName="id")
     */
    private $user;

    /**
     * Get user
     *
     * @return User|null
     */
    public function getUser(): ?User {
        return $this->user;
    }

    /**
     * Set User
     *
     * @param User|null $user
     *
     * @return $this
     */
    public function setUser(?User $user): HistoryLog {
        $this->user = $user;
        return $this;
    }


    /**
     * Modifications
     *
     * @var string|null
     *
     * @ORM\Column(name="modifications", type="text", nullable=true)
     */
    private $modifications;


    /**
     * Get Modifications
     *
     * @return string|null
     */
    public function getModifications() {
        return $this->modifications;
    }

    /**
     * Set Modifications
     *
     * @param string|null $modifications
     *
     * @return HistoryLog
     */
    public function setModifications($modifications): HistoryLog {
        $this->modifications = $modifications;
        return $this;
    }



}
