<?php

namespace App\Wicrew\CoreBundle\Twig;

use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\CoreBundle\Service\Utils;
use DateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * CoreExtension
 */
class CoreExtension extends AbstractExtension {

    /**
     * Core utility class
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
     * @return CoreExtension
     */
    public function setUtils(Utils $utils): CoreExtension {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('getService', [$this, 'getService']),
            new TwigFunction('json_decode', [$this, 'jsonDecode']),
            new TwigFunction('getTranslateLabel', [$this, 'getTranslateLabel']),
            new TwigFunction('validateUrl', [$this, 'validateUrl']),
            new TwigFunction('isDenied', [$this, 'isDenied']),
            new TwigFunction('getContainer', [$this, 'getContainer']),
            new TwigFunction('getParameterValue', [$this, 'getParameterValue']),
            new TwigFunction('getSession', [$this, 'getSession']),
            new TwigFunction('pricesIncludeTax', [$this, 'pricesIncludeTax']),
            new TwigFunction('getCurrencyTranslationKey', [$this, 'getCurrencyTranslationKey']),
            new TwigFunction('moneyGreaterThan', [$this, 'moneyGreaterThan']),
            new TwigFunction('newMoney', [$this, 'newMoney']),
            new TwigFunction('stringsEqualIgnoreCase', [$this, 'stringsEqualIgnoreCase']),
            new TwigFunction('checkForOrderItemQueryString', [$this, 'checkForOrderItemQueryString']),
            new TwigFunction('imageToBase64', [$this, 'imageToBase64']),
        ];
    }

    /*
     * {@inheritdoc}
     */
    public function getFilters() {
        return [
            new TwigFilter('intVal', [$this, 'stringToInteger']),
            new TwigFilter('defaultOnEmpty', [$this, 'defaultOnEmpty']),
            new TwigFilter('formatNoNull', [$this, 'formatNoNull']),
        ];
    }

    /**
     * Get service
     *
     * @param string $name
     *
     * @return object
     */
    public function getService($name) {
        return $this->getUtils()->getContainer()->get($name);
    }

    /**
     * Get container
     *
     * @return ContainerInterface
     */
    public function getContainer() {
        return $this->getUtils()->getContainer();
    }


    /**
     * Get array of json
     *
     * @param string $value
     *
     * @return object
     */
    public function jsonDecode($value) {
        if (!is_array($value)) {
            $value = json_decode($value, true);
        }
        return $value;
    }

    /**
     * Get translate label
     *
     * @param string $data
     * @param string $entity
     * @param string $key
     *
     * @return string
     */
    public function getTranslateLabel($data, $entity, $key) {
        $text = $this->utils->checkTranslateLabel($data, $entity, $key);
        return $text;
    }

    /**
     * Get validate Url
     *
     * @param string $string
     *
     * @return string
     */
    public function validateUrl($string) {
        $regex = "((https?|ftp)\:\/\/)?"; // SCHEME
        $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
        $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
        $regex .= "(\:[0-9]{2,5})?"; // Port
        $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
        $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
        $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

        if (preg_match("/^$regex$/i", trim($string))) { // `i` flag for case-insensitive
            if (strpos($string, 'http://') === false && strpos($string, 'https://') === false) {
                $string = 'http://' . $string;
            }
            $string = "<a href='$string' target='_blank'>" . $string . "</a>";
        }

        return $string;
    }

    /**
     * Is denied
     *
     * @param array|string $userRoles
     * @param array|string $permissions
     * @param string|null $action
     *
     * @return bool
     */
    public function isDenied($userRoles, $permissions, $action = null) {
        $isDenied = false;

        if ($permissions) {
            $isDenied = true;

            $userRoles = is_array($userRoles) ? $userRoles : [$userRoles];

            if ($action && isset($permissions[$action])) {
                $permissions = $permissions[$action];
            }
            $permissions = is_array($permissions) ? $permissions : [$permissions];

            $matchedRoles = array_intersect($userRoles, $permissions);
            if (count($matchedRoles) > 0) {
                $isDenied = false;
            }
        }

        return $isDenied;
    }

    /**
     * Get Parameter value
     *
     * @param string $parameterName
     *
     * @return mixed
     */
    public function getParameterValue($parameterName) {
        return $this->getUtils()->getParameterValue($parameterName);
    }

    /**
     * Get Session
     *
     * @param string $key
     *
     * @return array
     */
    public function getSession($key) {
        return $this->getUtils()->getSession()->get($key);
    }

    public function pricesIncludeTax(): bool {
        return $this->utils->pricesIncludeTax();
    }

    public function getCurrencyTranslationKey() {
        if ($this->pricesIncludeTax()) {
            return $this->utils->getTranslator()->trans('currency.text.tax');
        }
        return $this->utils->getTranslator()->trans('currency.text');
    }

    /**
     * @param string $key
     *
     * @return int
     */
    public function stringToInteger(string $key): int {
        return (int)$key;
    }

    /**
     *
     * @param string|null $str
     * @param string $default
     *
     * @return string
     */
    public function defaultOnEmpty(?string $str, string $default): string {
        if ($str !== null && !empty($str)) {
            return $str;
        }
        return $default;
    }

    /**
     *
     * @param DateTime|null $dt
     * @param string $format
     *
     * @return string
     */
    public function formatNoNull(?DateTime $dt, string $format): string {
        if ($dt === null) {
            return '';
        }
        return $dt->format($format);
    }

    /**
     *
     * @param string $mon
     * @param string $comp
     *
     * @return bool
     */
    public function moneyGreaterThan(string $mon, string $comp): bool {
        $money = new Money($mon);

        return $money->greaterThanStr($comp);
    }

    /**
     *
     * @param string $value
     *
     * @return Money
     */
    public function newMoney(string $value = '0.00'): Money {
        return new Money($value);
    }

    /**
     * @param string $str1
     * @param string $str2
     *
     * @return bool
     */
    public function stringsEqualIgnoreCase(string $str1, string $str2): bool {
        return strcasecmp($str1, $str2) === 0;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function checkForOrderItemQueryString(Request $request): array {
        $utils = $this->getContainer()->get('wicrew.core.utils');
        $utils->checkForOrderEditSession($request);

        if ($request->getSession()->has('orderID')) {
            return [ 'orderID' => $request->query->get('orderID') ];
        }

        return [];
    }

    public function imageToBase64($image_path) {
        $path = $this->getParameterValue('kernel.project_dir') . '/public/' . $image_path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return $base64;
    }
}
