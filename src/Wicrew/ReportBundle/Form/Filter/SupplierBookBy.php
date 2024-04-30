<?php

namespace App\Wicrew\ReportBundle\Form\Filter;

use App\Wicrew\PartnerBundle\Entity\Partner;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * SupplierBookBy
 */
class SupplierBookBy extends FilterType {

    /**
     * {@inheritDoc}
     */
    public function getParent() {
        return EntityType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'placeholder' => '',
            'class' => Partner::class,
            'choice_label' => 'bizName',
            //            'choices' => [],
            'expanded' => false,
            'multiple' => false
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metaData) {
        $data = $form->getData();
        if ($data instanceof Partner) {
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

            $queryBuilder->andWhere($alias . '.supplier = :supplierId')
                ->setParameter('supplierId', $data->getId());

            return true;
        } else {
            return false;
        }
    }

}
