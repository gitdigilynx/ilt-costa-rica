<?php

namespace App\Wicrew\CoreBundle\Service;

use MailMotor\Bundle\MailMotorBundle\Exception\NotImplementedException;
use Oneup\MailChimp\Client;

/**
 * Mailchimp
 */
class Mailchimp extends Client {

    /**
     * Config key path
     */
    const CONFIG_KEY = 'general/mailchimp';

    /**
     * Default list ID
     *
     * @var string
     */
    private $defaultListId;

    /**
     * Container interface
     *
     * @var Utils
     */
    protected $utils;

    /**
     * Constructor
     *
     * @param Utils $container
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);

        $configValues = $utils->getSystemConfigValues(self::CONFIG_KEY);
        if (
            $configValues
            && isset($configValues[self::CONFIG_KEY . '/api_key'])
        ) {
            parent::__construct($configValues[self::CONFIG_KEY . '/api_key']);

            if ($configValues[self::CONFIG_KEY . '/default_list_id']) {
                $this->setDefaultListId($configValues[self::CONFIG_KEY . '/default_list_id']);
            }
        } else {
            parent::__construct('');
        }
    }

    /**
     * Get Utils
     *
     * @return Utils
     */
    public function getUtils() {
        return $this->utils;
    }

    /**
     * Set Utils
     *
     * @param Utils $utils
     *
     * @return Mailchimp
     */
    public function setUtils(Utils $utils): Mailchimp {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get default list ID
     *
     * @return string
     */
    public function getDefaultListId() {
        return $this->defaultListId;
    }

    /**
     * Set default list ID
     *
     * @param string $defaultListId
     *
     * @return Mailchimp
     */
    public function setDefaultListId($defaultListId): Mailchimp {
        $this->defaultListId = $defaultListId;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function subscribeToList($listId, $email, $mergeVars = [], $doubleOptin = true, $interests = []) {
        $endpoint = sprintf('lists/%s/members', $listId);

        if (!$this->isSubscribed($listId, $email)) {
            $requestData = [
                'id' => $listId,
                'email_address' => $email,
                'status' => $doubleOptin ? 'pending' : 'subscribed',
            ];

            if (count($mergeVars) > 0) {
                $requestData['merge_fields'] = $mergeVars;
            }

            if (count($interests) > 0) {
                $requestData['interests'] = $interests;
            }

            $response = $this->post($endpoint, $requestData);
            $body = json_decode($response->getBody());

            if (400 === $response->getStatusCode() && 'Member Exists' === $body->title) {
                $endpoint = sprintf('lists/%s/members/%s', $listId, md5(strtolower($email)));
                $response = $this->put($endpoint, [
                    'status' => 'subscribed'
                ]);
            }

            return $response && 200 == $response->getStatusCode() ? true : false;
        }

        return false;
    }

    /**
     * Subscribe the user to the list
     *
     * @param string $email
     * @param string $listId
     * @param array $otherInfo
     *
     * @return bool
     */
    public function subscribe($email, $listId = '', array $otherInfo = []) {
        $success = false;

        if (!$listId) {
            $listId = $this->getDefaultListId();
        }

        $firstname = isset($otherInfo['firstname']) ? $otherInfo['firstname'] : '';
        $lastname = isset($otherInfo['lastname']) ? $otherInfo['lastname'] : '';

        try {
            if (!$this->isSubscribed($listId, $email)) {
                $success = $this->subscribeToList(
                    $listId,                    // List ID
                    $email,                     // E-Mail address
                    [                           // Array with first/lastname (MailChimp merge tags)
                        'firstName' => $firstname,
                        'lastName' => $lastname,
                    ],
                    true                        // Double opt-in true
                );
            }

            //            if (!$mailChimp->isSubscribed($email)) {
            //                // Subscribe the user to our default group
            //                $success = $mailChimp->subscribe(
            //                    $email,                                         // f.e.: 'info@info.info'
            //                    $locale,                                        // f.e.: 'en'
            //                    ['firstName' => $firstname, 'lastName' => $lastname],  // f.e.: ['firstName' => 'Jeroen', 'lastName' => 'Desloovere']
            //                    '',                                             // f.e.: ['9A28948d9' => true, '8998ASAA' => false]
            //                    true,                                           // OPTIONAL: DoubleOptin, default = true
            //                    $params['lists'][$locale]                       // OPTIONAL: Default listId is in your config parameters
            //                );
            //            }
        } catch (NotImplementedException $e) {
            $success = false;
        }

        return $success;
    }

    /**
     * Unsubscribe the email from the list
     *
     * @param string $email
     * @param string $listId
     *
     * @return bool
     */
    public function unsubscribe($email, $listId = '') {
        $success = true;

        if (!$listId) {
            $listId = $this->getDefaultListId();
        }

        try {
            if ($this->isSubscribed($listId, $email)) {
                $result = $this->unsubscribeFromList($listId, $email);
                if (!$result) {
                    $success = false;
                }
            }
            //            $mailChimp->unsubscribe(
            //                $email,
            //                $listId // OPTIONAL, default listId is in your config parameters
            //            );
        } catch (\Exception $e) {
            $success = false;
        }

        return $success;
    }

}
