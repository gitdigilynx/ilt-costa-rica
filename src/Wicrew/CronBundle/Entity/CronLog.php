<?php

namespace App\Wicrew\CronBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CronLog
 *
 * @ORM\Table(name="CronLog", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})})
 * @ORM\Entity
 */
class CronLog extends BaseEntity {

    /**
     * Statuses
     */
    const STATUS_PROCESSING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_ERROR = 2;

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
     * Cron
     *
     * @var Cron
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\CronBundle\Entity\Cron", inversedBy="logs", cascade={"persist"})
     * @ORM\JoinColumn(name="code", referencedColumnName="code")
     */
    private $cron;

    /**
     * Executed at
     *
     * @var \DateTime
     *
     * @ORM\Column(name="executed_at", type="datetime", nullable=false)
     */
    private $executedAt;

    /**
     * Finished at
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    private $finishedAt;

    /**
     * Status
     *
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default"="0","comment"="0 = Processing, 1 = Success, 2 = Error"})
     */
    private $status = self::STATUS_PROCESSING;

    /**
     * Message
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="message", type="text", length=65535, nullable=true)
     */
    private $message;

    /**
     * Email sent
     *
     * @var bool
     *
     * @ORM\Column(name="email_sent", type="boolean", nullable=false, options={"default"="0","comment"="0 = No, 1 = Yes"})
     */
    private $emailSent = false;

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
     * @return CronLog
     */
    public function setId($id): CronLog {
        $this->id = $id;
        return $this;
    }

    /**
     * Get cron
     *
     * @return Cron
     */
    public function getCron(): Cron {
        return $this->cron;
    }

    /**
     * Set cron
     *
     * @param Cron $cron
     *
     * @return CronLog
     */
    public function setCron(Cron $cron): CronLog {
        $this->cron = $cron;
        return $this;
    }

    /**
     * Get executed at
     *
     * @return \DateTime
     */
    public function getExecutedAt(): \DateTime {
        return $this->executedAt;
    }

    /**
     * Set executed at
     *
     * @param \DateTime $executedAt
     *
     * @return CronLog
     */
    public function setExecutedAt(\DateTime $executedAt): CronLog {
        $this->executedAt = $executedAt;
        return $this;
    }

    /**
     * Get finished at
     *
     * @return \DateTime|null
     */
    public function getFinishedAt(): ?\DateTime {
        return $this->finishedAt;
    }

    /**
     * Set finished at
     *
     * @param \DateTime $finishedAt
     *
     * @return $this
     */
    public function setFinishedAt(\DateTime $finishedAt): CronLog {
        $this->finishedAt = $finishedAt;
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
     * Set status
     *
     * @param int $status
     *
     * @return CronLog
     */
    public function setStatus($status): CronLog {
        $this->status = $status;
        return $this;
    }

    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return CronLog
     */
    public function setMessage($message): CronLog {
        $this->message = $message;
        return $this;
    }

    /**
     * Get email sent
     *
     * @return bool
     */
    public function isEmailSent() {
        return $this->emailSent;
    }

    /**
     * Set email sent
     *
     * @param bool $emailSent
     *
     * @return CronLog
     */
    public function setEmailSent($emailSent): CronLog {
        $this->emailSent = (bool)$emailSent;
        return $this;
    }

}
