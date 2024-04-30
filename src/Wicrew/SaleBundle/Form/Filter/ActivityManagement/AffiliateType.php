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
 * AffiliateType
 */
class AffiliateType extends FilterType {
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
    public function setUtils(Utils $utils): AffiliateType {
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
        $types = [ Partner::TYPE_PARTNER, Partner::TYPE_AFFILIATE, Partner::TYPE_TRAVEL_AGENT ];
        $transportations = $em->getRepository(Partner::class)->findBy(['type' => $types], ['bizName' => 'ASC']);
        foreach ($transportations as $transportation) {
            $transArray[$transportation->getFirstname() . ' ' . $transportation->getLastname()] = $transportation->getId();
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
            $aliases = $queryBuilder->getAllAliases();
            if (!in_array('entity_activity', $aliases)) $queryBuilder->join('entity.activity', 'entity_activity');
            if (!in_array('activity_supplier', $aliases)) $queryBuilder->join('entity_activity.supplier', 'activity_supplier');

            $where = [];

            foreach ($values as $idx => $value) {
                $paramName = $propertyName . $idx;

                $where[] = 'order_supplier.id' . ' = :' . $paramName;

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
