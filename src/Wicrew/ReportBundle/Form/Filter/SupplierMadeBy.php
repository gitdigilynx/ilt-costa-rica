<?php

namespace App\Wicrew\ReportBundle\Form\Filter;

use App\Wicrew\PartnerBundle\Entity\Partner;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * SupplierMadeBy
 */
class SupplierMadeBy extends FilterType {

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
            $queryBuilder
                ->andWhere('entity.supplier = :supplierId')
                ->setParameter('supplierId', $data->getId());

            return true;
        } else {
            return false;
        }
    }

}
