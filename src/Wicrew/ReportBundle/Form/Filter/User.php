<?php

namespace App\Wicrew\ReportBundle\Form\Filter;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Entity\User as UserEntity;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as BaseChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Wicrew\PartnerBundle\Entity\Partner;

/**
 * User
 */
class User extends FilterType {
    /**
     * utils
     *
     * @var Utils
     */
    protected $utils;

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
     * @return Summary
     */
    public function setUtils(Utils $utils): User {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent() {
        return BaseChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $em = $this->getUtils()->getEntityManager();
        $transArray = [];  
        $resolver->setDefaults([
            'choices' => [ 'Staff' => UserEntity::TYPE_USER_STAFF, 'Website' => UserEntity::TYPE_USER_WEBSITE ]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metaData) {
        $data = $form->getData();
        if ($data) {
            $propertyName = $metaData['property'];
            $value = $data;
            $aliases = $queryBuilder->getAllAliases();
            if (!in_array('entity_order', $aliases)) $queryBuilder->join('entity.order', 'entity_order');
 
            if ($value == UserEntity::TYPE_USER_STAFF) {
                $queryBuilder->andWhere('entity_order.user is not null');
            } else {
                $queryBuilder->andWhere('entity_order.user is null');
            }

            return true;
        } else {
            return false;
        }
    }

}
