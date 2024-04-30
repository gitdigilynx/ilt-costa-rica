<?php

namespace App\Wicrew\ReportBundle\Form\Filter;

use App\Wicrew\CoreBundle\Form\Filter\ChoiceType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;

/**
 * PaymentType
 */
class PaymentType extends ChoiceType {

    /**
     * {@inheritDoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metaData) {
        $data = $form->getData();
        if ($data) {
            $alias = '';
            if ($queryBuilder->getDQLParts()['join']) {
                foreach ($queryBuilder->getDQLParts()['join']['entity'] as $join) {
                    if ($join->getJoin() == 'entity.order') {
                        $alias = $join->getAlias();
                    }
                }
            }
            if (!$alias) {
                $alias = 'o';
                $queryBuilder->join('entity.order', $alias);
            }

            $propertyName = $metaData['property'];
            $values = is_array($data) ? $data : [$data];

            $where = [];
            foreach ($values as $idx => $value) {
                $paramName = $propertyName . $idx;
                $where[] = $alias . '.' . $propertyName . ' = :' . $paramName;

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
