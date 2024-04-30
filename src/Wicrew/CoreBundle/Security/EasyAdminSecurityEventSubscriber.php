<?php

namespace App\Wicrew\CoreBundle\Security;

use App\Wicrew\CoreBundle\Controller\Admin\AdminController;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * EasyAdminSecurityEventSubscriber
 */
class EasyAdminSecurityEventSubscriber implements EventSubscriberInterface {

    /**
     * AccessDecisionManagerInterface
     *
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * Get AccessDecisionManagerInterface
     *
     * @return AccessDecisionManagerInterface
     */
    public function getDecisionManager(): AccessDecisionManagerInterface {
        return $this->decisionManager;
    }

    /**
     * Set AccessDecisionManagerInterface
     *
     * @param AccessDecisionManagerInterface $decisionManager
     *
     * @return EasyAdminSecurityEventSubscriber
     */
    public function setDecisionManager(AccessDecisionManagerInterface $decisionManager): EasyAdminSecurityEventSubscriber {
        $this->decisionManager = $decisionManager;
        return $this;
    }

    /**
     * TokenStorageInterface
     *
     * @var TokenStorageInterface
     */
    private $token;

    /**
     * Get TokenStorageInterface
     *
     * @return TokenStorageInterface
     */
    public function getToken(): TokenStorageInterface {
        return $this->token;
    }

    /**
     * Set TokenStorageInterface
     *
     * @param TokenStorageInterface $token
     *
     * @return EasyAdminSecurityEventSubscriber
     */
    public function setToken(TokenStorageInterface $token): EasyAdminSecurityEventSubscriber {
        $this->token = $token;
        return $this;
    }

    /**
     * Constructor
     *
     * @param AccessDecisionManagerInterface $decisionManager
     * @param TokenStorageInterface $token
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, TokenStorageInterface $token) {
        $this->setDecisionManager($decisionManager);
        $this->setToken($token);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array {
        return [
            EasyAdminEvents::PRE_LIST => ['isAuthorized'],
            EasyAdminEvents::PRE_EDIT => ['isAuthorized'],
            EasyAdminEvents::PRE_DELETE => ['isAuthorized'],
            EasyAdminEvents::PRE_NEW => ['isAuthorized'],
            AdminController::PRE_DUPLICATE => ['isAuthorized'],
            EasyAdminEvents::PRE_SHOW => ['isAuthorized'],
        ];
    }

    /**
     * Is authorized
     *
     * @param GenericEvent $event
     *
     * @throws AccessDeniedException
     */
    public function isAuthorized(GenericEvent $event) {
        $entityConfig = $event['entity'];
        $action = $event->getArgument('request')->query->get('action');

        if (!$this->hasRolePermission($entityConfig, $action)) {
            throw new AccessDeniedException();
        }
    }

    public function hasRolePermission($entityConfig, $action): bool {
        if (isset($entityConfig['permissions'])) {
            if (isset($entityConfig['permissions'][$action])) {
                $authorizedRoles = is_array($entityConfig['permissions'][$action]) ? $entityConfig['permissions'][$action] : [$entityConfig['permissions'][$action]];
            } else {
                $authorizedRoles = is_array($entityConfig['permissions']) ? $entityConfig['permissions'] : [$entityConfig['permissions']];
            }

            if ($authorizedRoles && !$this->getDecisionManager()->decide($this->getToken()->getToken(), $authorizedRoles)) {
                return false;
            }
        }

        return true;
    }

}
