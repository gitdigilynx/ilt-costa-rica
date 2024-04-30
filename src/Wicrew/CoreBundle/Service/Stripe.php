<?php

namespace App\Wicrew\CoreBundle\Service;

use Exception;
use Stripe as StripeCore;

/**
 * Stripe
 */
class Stripe {

    /**
     * Default currency
     */
    const DEFAULT_CURRENCY = 'usd';

    /**
     * Charge statuses
     */
    const CHARGE_STATUS_SUCCESS = 200;

    /**
     * Utils
     *
     * @var Utils
     */
    private $utils;

    /**
     * Currency
     *
     * @var string
     */
    private $currency = self::DEFAULT_CURRENCY;

    /**
     * Throw error when get failed result
     *
     * @var bool
     */
    private $throwError = false;

    /**
     * Constructor
     *
     * @param Utils $utils
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);

        $stripeSettingValues = $utils->getSystemConfigValues('stripe/api', true);
        $stripeSettingValues = $stripeSettingValues['stripe']['api'];
        if (isset($stripeSettingValues['secret_key']) && $stripeSettingValues['secret_key']) {
            StripeCore\Stripe::setApiKey($stripeSettingValues['secret_key']);
        }
        if (isset($stripeSettingValues['default_currency']) && $stripeSettingValues['default_currency']) {
            $this->setCurrency($stripeSettingValues['default_currency']);
        }
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
     * @return Stripe
     */
    public function setUtils(Utils $utils): Stripe {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return Stripe
     */
    public function setCurrency($currency): Stripe {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Get throw error
     *
     * @return bool
     */
    public function isThrowError() {
        return $this->throwError;
    }

    /**
     * Set throw error
     *
     * @param bool $throwError
     *
     * @return Stripe
     */
    public function setThrowError($throwError): Stripe {
        $this->throwError = filter_var($throwError, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * Created customer
     *
     * @param string $token
     * @param string $email
     *
     * @return array
     * @throws Exception
     */
    public function createCustomer($token, $email = '') {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $result = StripeCore\Customer::create([
                'source' => $token,
                'email' => $email,
            ]);

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Update customer
     *
     * @param StripeCore\Customer|string $customer
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function updateCustomer($customer, array $data) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $result = StripeCore\Customer::update(
                $customer instanceof StripeCore\Customer ? $customer->id : $customer,
                $data
            );

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Get customer
     *
     * @param StripeCore\Customer|string $customer
     *
     * @return array
     * @throws Exception
     */
    public function getCustomer($customer) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $result = StripeCore\Customer::retrieve(
                $customer instanceof StripeCore\Customer ? $customer->id : $customer
            );

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Create source
     *
     * @param StripeCore\Customer|string $customer
     * @param string $token
     * @param bool $isDefault
     *
     * @return array
     * @throws Exception
     */
    public function createSource($customer, $token, $isDefault = false) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $result = StripeCore\Customer::createSource(
                $customer instanceof StripeCore\Customer ? $customer->id : $customer,
                ['source' => $token]
            );

            $response['data'] = $result;

            if ($result instanceof StripeCore\Card && $isDefault) {
                $result = $this->updateCustomer($customer, ['default_source' => $result->id]);

                $response['data'] = $result;
            }
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Get source
     *
     * @param StripeCore\Customer|string $customer
     * @param StripeCore\Card|string $card
     *
     * @return array
     * @throws Exception
     */
    public function getSource($customer, $card) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $result = StripeCore\Customer::retrieveSource(
                $customer instanceof StripeCore\Customer ? $customer->id : $customer,
                $card instanceof StripeCore\Card ? $card->id : $card
            );

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Create charge
     *
     * @param StripeCore\Customer|string $customer
     * @param Money $amount
     * @param string|null $description
     * @param StripeCore\Card|string|null $card
     * @param string|null $currency
     *
     * @return array
     * @throws Exception
     */
    public function createCharge($customer, Money $amount, $description = null, $card = null, $currency = null) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $amountInCents = $amount->toCents();

            $result = StripeCore\Charge::create([
                'customer' => $customer instanceof StripeCore\Customer ? $customer->id : $customer,
                'amount' => $amountInCents,
                'description' => $description,
                'source' => $card instanceof StripeCore\Card ? $card->id : $card,
                'currency' => $currency ?: $this->getCurrency()
            ]);

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Get charge
     *
     * @param StripeCore\Charge|string $charge
     *
     * @return array
     * @throws Exception
     */
    public function getCharge($charge) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $result = StripeCore\Charge::retrieve($charge instanceof StripeCore\Charge ? $charge->id : $charge);

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Create refund
     *
     * @param StripeCore\Charge|string $charge
     * @param Money $amount
     * @param string|null $reason
     *
     * @return array
     * @throws Exception
     */
    public function createRefund($charge, Money $amount, $reason = null) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $amountInCents = $amount->toCents();

            $result = StripeCore\Refund::create([
                'charge' => $charge instanceof StripeCore\Charge ? $charge->id : $charge,
                'amount' => $amountInCents,
                'reason' => $reason
            ]);

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Get refund
     *
     * @param StripeCore\Refund|string $refund
     *
     * @return array
     * @throws Exception
     */
    public function getRefund($refund) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        try {
            $result = StripeCore\Refund::retrieve($refund instanceof StripeCore\Refund ? $refund->id : $refund);

            $response['data'] = $result;
        } catch (Exception $ex) {
            if ($this->isThrowError()) {
                throw $ex;
            }

            $response['status'] = 'failed';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Charge customer
     *
     * @param $price
     * @param object $customer
     *
     * @return Object
     */
    public function chargeCustomer($price, $customer) {
        return StripeCore\Charge::create([
            'amount'        => $price,
            'currency'      => 'usd',
            'description'   => 'Charge order price $' . $price . ' USD',
            'customer'      => $customer->id,
        ]);
    }

}
