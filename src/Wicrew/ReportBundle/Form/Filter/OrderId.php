<?php

namespace App\Wicrew\ReportBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use Symfony\Component\Form\FormInterface;

/**
 * OrderId
 */
class OrderId extends FilterType {

    /**
     * {@inheritDoc}
     */
    public function getParent() {
        return TextFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    //    public function configureOptions(OptionsResolver $resolver)
    //    {
    //        $resolver->setDefaults([
    //            'choices' => [],
    //            'expanded' => true,
    //            'multiple' => true
    //        ]);
    //    }

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

            $comparison = isset($data['comparison']) ? $data['comparison'] : 'LIKE';
            $value = isset($data['value']) ? $data['value'] : $data;

            $queryBuilder->andWhere($alias . '.id ' . $comparison . ' :' . $propertyName)
                ->setParameter($propertyName, $value);

            return true;
        } else {
            return false;
        }
    }

}
