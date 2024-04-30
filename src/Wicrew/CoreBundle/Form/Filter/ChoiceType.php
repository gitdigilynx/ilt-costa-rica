<?php

namespace App\Wicrew\CoreBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as BaseChoiceType;
use Symfony\Component\Form\FormInterface;

/**
 * ChoiceType
 */
class ChoiceType extends FilterType {

    /**
     * {@inheritDoc}
     */
    public function getParent() {
        return BaseChoiceType::class;
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
            $propertyName = $metaData['property'];
            $values = is_array($data) ? $data : [$data];

            $isFindInSet = isset($metaData['findInSet']) && $metaData['findInSet'] ?: false;

            $where = [];
            foreach ($values as $idx => $value) {
                $paramName = $propertyName . $idx;

                if ($isFindInSet) {
                    $where[] = "FIND_IN_SET(:" . $paramName . ", REPLACE(REPLACE(entity." . $propertyName . ", '[', ''), ']', '')) > 0";
                } else {
                    $where[] = 'entity.' . $propertyName . ' = :' . $paramName;
                }

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
