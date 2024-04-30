<?php

namespace App\Controller;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController as BaseAdminController;
use FOS\UserBundle\Model\UserManagerInterface;

class AdminController extends BaseAdminController {

    public static function getSubscribedServices(): array {
        return parent::getSubscribedServices() + [
               'fos_user.user_manager' => UserManagerInterface::class
            ];
    }

    /**
     * Create new user entity
     *
     * @return User
     */
    public function createNewUserEntity() {
        return $this->get('fos_user.user_manager')->createUser();
    }

    /**
     * Persist user entity
     *
     * @param User $user
     */
    public function persistUserEntity(User $user) {
        $this->get('fos_user.user_manager')->updateUser($user, false);
        parent::persistEntity($user);
    }

    /**
     * Update user entity
     *
     * @param User $user
     */
    public function updateUserEntity(User $user) {
        $this->get('fos_user.user_manager')->updateUser($user, false);
        parent::updateEntity($user);
    }

}
