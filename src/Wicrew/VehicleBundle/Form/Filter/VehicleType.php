<?php

namespace App\Wicrew\VehicleBundle\Form\Filter;

use App\Wicrew\CoreBundle\Service\Utils;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as BaseChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * VehicleType
 */
class VehicleType extends FilterType {
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
    public function setUtils(Utils $utils): VehicleType {
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
        $transportations = $em->getRepository('\App\Wicrew\VehicleTypeBundle\Entity\VehicleType')->findAll();
        foreach ($transportations as $transportation) {
            $transArray[$transportation->getName()] = $transportation->getId();
        }
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
