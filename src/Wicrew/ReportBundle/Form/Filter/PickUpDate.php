<?php

namespace App\Wicrew\ReportBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\FormInterface;

/**
 * PickUpDate
 */
class PickUpDate extends FilterType {

    /**
     * {@inheritDoc}
     */
    public function getParent() {
        return DateTimeFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata) {
        $form->setData([
            'comparison' => 'between',
            'value' => '2019-11-01',
            'value2' => '2019-11-30'
        ]);

        return parent::filter($queryBuilder, $form, $metadata);
    }

}
