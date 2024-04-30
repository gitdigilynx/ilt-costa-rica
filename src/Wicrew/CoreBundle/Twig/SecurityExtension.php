<?php

namespace App\Wicrew\CoreBundle\Twig;

use App\Wicrew\CoreBundle\Security\EasyAdminSecurityEventSubscriber;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SecurityExtension extends AbstractExtension {

    /**
     * Core utility class
     *
     * @var EasyAdminSecurityEventSubscriber
     */
    private $securityUtils;

    /**
     * Constructor
     *
     * @param EasyAdminSecurityEventSubscriber $utils
     */
    public function __construct(EasyAdminSecurityEventSubscriber $utils) {
        $this->setSecurityUtils($utils);
    }

    /**
     * Get utils
     *
     * @return EasyAdminSecurityEventSubscriber
     */
    public function getSecurityUtils(): EasyAdminSecurityEventSubscriber {
        return $this->securityUtils;
    }

    /**
     * Set utils
     *
     * @param EasyAdminSecurityEventSubscriber $securityUtils
     *
     * @return SecurityExtension
     */
    public function setSecurityUtils(EasyAdminSecurityEventSubscriber $securityUtils): SecurityExtension {
        $this->securityUtils = $securityUtils;
        return $this;
    }

    /*
    * {@inheritdoc}
    */
    public function getFilters() {
        return [
            new TwigFilter('filter_admin_actions', [$this, 'filterActions'])
        ];
    }

    public function filterActions(array $itemActions, $entityConfig, bool $replaceEditWithShowAction = false) {
        $keys = array_keys($itemActions);

        foreach ($keys as $key) {
            $action = $itemActions[$key];
            $actionName = $action['name'];
            if (!$this->getSecurityUtils()->hasRolePermission($entityConfig, $actionName)) {
                unset($itemActions[$key]);
            }
        }

        return $itemActions;
    }
}