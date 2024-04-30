<?php

namespace App\Wicrew\CoreBundle\Service;

use App\Wicrew\CoreBundle\Service\Reminder as CustomReminder;
use App\Wicrew\CoreBundle\Service\Reminder\ReminderAbstract;

/**
 * Reminder
 */
class Reminder {

    /**
     * Types
     */
    const TYPE_ORDER = 1;

    /**
     * Reminder instances
     *
     * @var array
     */
    private $reminderInstances = [];

    /**
     * Utils
     *
     * @var Utils
     */
    private $utils;

    /**
     * Constructor
     *
     * @param Utils $utils
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);
    }

    /**
     * Get utils
     *
     * @return Utils
     */
    public function getUtils(): Utils {
        return $this->utils;
    }

    /**
     * Set utils
     *
     * @param Utils $utils
     *
     * @return Reminder
     */
    public function setUtils(Utils $utils): Reminder {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get reminder instances
     *
     * @return array
     */
    public function getReminderInstances() {
        return $this->reminderInstances;
    }

    /**
     * Get reminder instance
     *
     * @param int $type
     *
     * @return ReminderAbstract|null
     */
    public function getReminderInstance($type): ?ReminderAbstract {
        $reminderInstances = $this->getReminderInstances();
        if (!isset($reminderInstances[$type])) {
            switch ($type) {
                case self::TYPE_ORDER:
                    $reminderInstances[$type] = new CustomReminder\OrderReminder($this->getUtils());
                    break;
            }
        }

        if (isset($reminderInstances[$type])) {
            return $reminderInstances[$type];
        } else {
            return null;
        }
    }

    /**
     * Trigger a reminder
     *
     * @param int $type
     * @param string $action
     * @param array $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function trigger($type, $action, array $options = []) {
        $result = [
            'status' => 'success',
            'message' => ''
        ];

        $reminder = $this->getReminderInstance($type);
        if ($reminder && method_exists($reminder, $action)) {
            if ($reminder->$action($options)) {
                $result['status'] = 'success';
            } else {
                $result['status'] = 'failed';
            }

            return $result;
        } else {
            throw new \Exception('Service reminder not found or failed triggering non-exist action.');
        }
    }

}
