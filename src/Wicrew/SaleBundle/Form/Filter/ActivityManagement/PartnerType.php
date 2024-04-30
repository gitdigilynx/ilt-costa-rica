<?php

namespace App\Wicrew\SaleBundle\Form\Filter\ActivityManagement;

use App\Wicrew\CoreBundle\Service\Utils;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as BaseChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Wicrew\PartnerBundle\Entity\Partner;

/**
 * PartnerType
 */
class PartnerType extends FilterType {
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
    public function setUtils(Utils $utils): PartnerType {
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
        $partners = $em->getRepository(Partner::class)->findBy(['type' => [ Partner::TYPE_DRIVER, Partner::TYPE_AFFILIATE, Partner::TYPE_SUPPLIER, Partner::TYPE_TRAVEL_AGENT, Partner::TYPE_PARTNER ]], ['bizName' => 'ASC']);

        $drivers = array();
        foreach ($partners as $driver) {
            $drivers[$driver->getBizName()] = $driver->getId();
        }

        $resolver->setDefaults([
            'choices' => $drivers,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metaData) {
        $data = $form->getData();
        if ($data) {
            $propertyName = $metaData['property'];
            $values = is_array($data) ? $data : [$data];

            $isFindInSet = isset($metaData['findInSet']) && $metaData['findInSet'] ?: false;

            $aliases = $queryBuilder->getAllAliases();
            if (!in_array('entity_order', $aliases)) $queryBuilder->join('entity.supplier', 'order_supplier'); 

            $where = [];
            $whereAdDriver = [];
            foreach ($values as $idx => $value) {
                $paramName = $propertyName . $idx;

                $where[] = 'order_supplier.id' . ' = :' . $paramName;

                $queryBuilder->setParameter($paramName, $value);

                $queryBuilder->leftJoin('App\Wicrew\SaleBundle\Entity\OrderItemHasDriver', 'OrderItemHasDriver', 'WITH',  'OrderItemHasDriver.orderItem' . ' = entity.id'); 
                $whereAdDriver[] = 'OrderItemHasDriver.driver' . ' = ' . $value;
            }

            $queryBuilder->andWhere(
                '(' . implode(' OR ', $where) . ' OR ' . implode(' OR ', $whereAdDriver) . ')'
            );

            return true;
        } else {
            return false;
        }
    }

}
