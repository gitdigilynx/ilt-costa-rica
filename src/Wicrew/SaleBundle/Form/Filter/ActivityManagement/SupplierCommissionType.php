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
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

/**
 * SupplierCommissionType
 */
class SupplierCommissionType extends FilterType
{
    use FilterTypeTrait;

    private $valueType;
    private $valueTypeOptions;

    public function __construct(string $valueType = null, array $valueTypeOptions = [])
    {
        $this->valueType = $valueType ?: NumberType::class;
        $this->valueTypeOptions = $valueTypeOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value2', FormTypeHelper::getTypeClass($options['value_type']), $options['value_type_options'] + [
            'label' => false,
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) {
                if (ComparisonType::BETWEEN === $data['comparison']) {
                    if (null === $data['value'] || '' === $data['value'] || null === $data['value2'] || '' === $data['value2']) {
                        throw new TransformationFailedException('Two values must be provided when "BETWEEN" comparison is selected.');
                    }

                    // make sure value 2 is greater than value 1
                    if ($data['value'] > $data['value2']) {
                        [$data['value'], $data['value2']] = [$data['value2'], $data['value']];
                    }
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
            'value_type' => $this->valueType,
            'value_type_options' => $this->valueTypeOptions,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'easyadmin_numeric_filter';
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
        $aliases = $queryBuilder->getAllAliases();
        if (!in_array('entity_activity', $aliases)) $queryBuilder->join('entity.activity', 'entity_activity');
        if (!in_array('entity_driver', $aliases)) $queryBuilder->join('entity_activity.driver', 'entity_driver');

        $alias = 'entity_driver';
        $property = 'commission';
        $data = $form->getData();

        if (ComparisonType::BETWEEN === $data['comparison']) {
            $paramName1 = static::createAlias($property);
            $paramName2 = static::createAlias($property);
            $queryBuilder->andWhere(sprintf('%s.%s BETWEEN :%s and :%s', $alias, $property, $paramName1, $paramName2))
                ->setParameter($paramName1, $data['value'])
                ->setParameter($paramName2, $data['value2']);
        } else {
            $paramName = static::createAlias($property);
            $queryBuilder->andWhere(sprintf('%s.%s %s :%s', $alias, $property, $data['comparison'], $paramName))
                ->setParameter($paramName, $data['value']);
        }
    }
}