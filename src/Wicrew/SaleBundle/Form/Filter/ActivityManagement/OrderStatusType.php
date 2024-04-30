<?php

namespace App\Wicrew\SaleBundle\Form\Filter\ActivityManagement;

use App\Wicrew\CoreBundle\Service\Utils;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as BaseChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\Order;

/**
 * OrderStatusType
 */
class OrderStatusType extends FilterType {
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
    public function setUtils(Utils $utils): OrderStatusType {
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
        $trans = $this->getUtils()->getTranslator();
        $transArray = [
            $trans->trans('sale.order.status.pending')      => Order::STATUS_PENDING,
            $trans->trans('sale.order.status.paid')         => Order::STATUS_PAID,
            $trans->trans('sale.order.status.cancelled')    => Order::STATUS_CANCELLED
        ];
        $resolver->setDefaults([
            'choices' => $transArray,
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
            if (!in_array('entity_order', $aliases)) $queryBuilder->join('entity.order', 'entity_order');

            $where = [];

            foreach ($values as $idx => $value) {
                $paramName = $propertyName . $idx;

                $where[] = 'entity_order.status' . ' = :' . $paramName;

                $queryBuilder->setParameter($paramName, $value);
            }

            $queryBuilder->andWhere(
                '(' . implode(' OR ', $where) . ')'
            );

            return true;
        } else {
            return false;
        }
    }

}
