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
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterTypeTrait;

/**
 * CustomerNameType
 */
class CustomerNameType extends FilterType
{
    use FilterTypeTrait;

    private $valueType;

    public function __construct(string $valueType = null)
    {
        $this->valueType = $valueType ?: TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) {
                switch ($data['comparison']) {
                    case ComparisonType::STARTS_WITH:
                        $data['comparison'] = ComparisonType::CONTAINS;
                        $data['value'] .= '%';
                        break;
                    case ComparisonType::ENDS_WITH:
                        $data['comparison'] = ComparisonType::CONTAINS;
                        $data['value'] = '%'.$data['value'];
                        break;
                    case ComparisonType::CONTAINS:
                    case ComparisonType::NOT_CONTAINS:
                        $data['value'] = '%'.$data['value'].'%';
                }

                return $data;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'text'],
            'value_type' => $this->valueType,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ComparisonFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $data = $form->getData();

        if ($data) {
            $propertyName = $metadata['property'];
            $value = $data['value'];

            $comparison = $data['comparison'];

            $aliases = $queryBuilder->getAllAliases();
            if (!in_array('entity_order', $aliases)) $queryBuilder->join('entity.order', 'entity_order');

            $paramName = 'customer_name';

            $where = 'CONCAT(entity_order.firstName, \' \', entity_order.lastName) ' . $comparison .' :' . $paramName;

            $queryBuilder->setParameter($paramName, $value);

            $queryBuilder->andWhere(
                $where
            );

            return true;
        } else {
            return false;
        }
    }
}