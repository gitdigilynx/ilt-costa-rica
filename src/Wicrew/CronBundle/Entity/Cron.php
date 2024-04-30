<?php

namespace App\Wicrew\CronBundle\Entity;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Cron
 *
 * @ORM\Table(name="Cron", uniqueConstraints={@ORM\UniqueConstraint(name="code_UNIQUE", columns={"code"})})
 * @ORM\Entity(repositoryClass="App\Wicrew\CronBundle\Repository\CronRepository")
 */
class Cron extends BaseEntity {

    /**
     * Code
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 64)
     *
     * @ORM\Id
     * @ORM\Column(name="code", type="string", length=64, nullable=false)
     */
    private $code;

    /**
     * Expression
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="expression", type="string", length=32, nullable=false)
     */
    private $expression;

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
     * Service
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 32)
     *
     * @ORM\Column(name="service", type="string", length=32, nullable=false)
     */
    private $service;

    /**
     * Action
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 128)
     *
     * @ORM\Column(name="action", type="string", length=128, nullable=false)
     */
    private $action;

    /**
     * Parameters
     *
     * @var string|null
     *
     * @Assert\Length(max = 65535)
     *
     * @ORM\Column(name="parameters", type="text", length=65535, nullable=true)
     */
    private $parameters;

    /**
     * Executed at
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="executed_at", type="datetime", nullable=true)
     */
    private $executedAt;

    /**
     * Keep executing when processing
     *
     * @var bool
     *
     * @ORM\Column(name="keep_executing_when_processing", type="boolean", nullable=false, options={"default"="0","comment"="0 = No, 1 = Yes"})
     */
    private $keepExecutingWhenProcessing = false;

    /**
     * Keep executing when error
     *
     * @var bool
     *
     * @ORM\Column(name="keep_executing_when_error", type="boolean", nullable=false, options={"default"="0","comment"="0 = No, 1 = Yes"})
     */
    private $keepExecutingWhenError = false;

    /**
     * Active
     *
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false, options={"default"="1","comment"="0 = No, 1 = Yes"})
     */
    private $active = true;

    /**
     * Logs
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Wicrew\CronBundle\Entity\CronLog", mappedBy="cron", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"executedAt" = "DESC"})
     */
    private $logs;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setLogs(new ArrayCollection());
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Cron
     */
    public function setCode($code): Cron {
        $this->code = $code;
        return $this;
    }

    /**
     * Get expression
     *
     * @return string
     */
    public function getExpression() {
        return $this->expression;
    }

    /**
     * Set expression
     *
     * @param string $expression
     *
     * @return Cron
     */
    public function setExpression($expression): Cron {
        $this->expression = $expression;
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
     * @return Cron
     */
    public function setTitle($title): Cron {
        $this->title = $title;
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
     * @param string $description
     *
     * @return Cron
     */
    public function setDescription($description): Cron {
        $this->description = $description;
        return $this;
    }

    /**
     * Get service
     *
     * @return string
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Set service
     *
     * @param string $service
     *
     * @return Cron
     */
    public function setService($service): Cron {
        $this->service = $service;
        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return Cron
     */
    public function setAction($action): Cron {
        $this->action = $action;
        return $this;
    }

    /**
     * Get parameters
     *
     * @return mixed
     */
    public function getParameters() {
        return $this->decrypt($this->parameters, self::ENCRYPT_TYPE_JSON, []);
    }

    /**
     * Set parameters
     *
     * @param mixed $parameters
     *
     * @return Cron
     */
    public function setParameters($parameters): Cron {
        $this->parameters = $this->encrypt($parameters);
        return $this;
    }

    /**
     * Get executed at
     *
     * @return \DateTime|null
     */
    public function getExecutedAt() {
        return $this->executedAt;
    }

    /**
     * Set executed at
     *
     * @param \DateTime $executedAt
     *
     * @return Cron
     */
    public function setExecutedAt(\DateTime $executedAt): Cron {
        $this->executedAt = $executedAt;
        return $this;
    }

    /**
     * Get keep executing when processing
     *
     * @return bool
     */
    public function isKeepExecutingWhenProcessing() {
        return $this->keepExecutingWhenProcessing;
    }

    /**
     * Set keep executing when processing
     *
     * @param bool $keepExecutingWhenProcessing
     *
     * @return Cron
     */
    public function setKeepExecutingWhenProcessing($keepExecutingWhenProcessing): Cron {
        $this->keepExecutingWhenProcessing = (bool)$keepExecutingWhenProcessing;
        return $this;
    }

    /**
     * Get keep executing when error
     *
     * @return bool
     */
    public function isKeepExecutingWhenError() {
        return $this->keepExecutingWhenError;
    }

    /**
     * Set keep executing when error
     *
     * @param bool $keepExecutingWhenError
     *
     * @return Cron
     */
    public function setKeepExecutingWhenError($keepExecutingWhenError): Cron {
        $this->keepExecutingWhenError = (bool)$keepExecutingWhenError;
        return $this;
    }

    /**
     * Get active
     *
     * @return bool
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * Set active
     *
     * @param bool $active
     *
     * @return Cron
     */
    public function setActive($active): Cron {
        $this->active = (bool)$active;
        return $this;
    }

    /**
     * Get logs
     *
     * @return ArrayCollection
     */
    public function getLogs() {
        return $this->logs;
    }

    /**
     * Set logs
     *
     * @param ArrayCollection $logs
     *
     * @return Cron
     */
    public function setLogs($logs): Cron {
        $this->logs = $logs;
        return $this;
    }

    /**
     * Add log
     *
     * @param CronLog $log
     *
     * @return Cron
     */
    public function addLog(CronLog $log): Cron {
        $log->setLead($this);
        $this->getReminders()->add($log);

        return $this;
    }

    /**
     * Remove log
     *
     * @param CronLog $log
     *
     * @return Cron
     */
    public function removeLog(CronLog $log): Cron {
        foreach ($this->getLogs() as $k => $o) {
            if ($o->getId() == $log->getId()) {
                $log->setCron(null);
                $this->getLogs()->removeElement($log);
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString() {
        return $this->getTitle() . ' - (' . $this->getCode() . ')';
    }

}
