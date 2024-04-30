<?php

namespace App\Wicrew\ReportBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterTypeTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class Refund extends FilterType
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
        $alias = current($queryBuilder->getRootAliases());
        $em = $queryBuilder->getEntityManager();
        $property = $metadata['property'];
        $data = $form->getData();   

        if (ComparisonType::BETWEEN === $data['comparison']) {
            $paramName1 = static::createAlias($property);
            $paramName2 = static::createAlias($property);
            $qb2  = $em->createQueryBuilder(); 
            $qb2->select('e.id')
                ->from('App\Wicrew\SaleBundle\Entity\OrderItem', 'e') 
                ->leftJoin('e.history', 'h') ;
            $qb2->having(sprintf('(SUM(h.amount)) BETWEEN :%s and :%s', $paramName1, $paramName2));
            $queryBuilder
                ->setParameter($paramName1, $data['value'])
                ->setParameter($paramName2, $data['value2']); 
            
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('entity.id', $qb2->getDQL())
            );
        } else {
            $paramName = static::createAlias($property); 
            $qb2  = $em->createQueryBuilder(); 
            $qb2->select('e.id')
                ->from('App\Wicrew\SaleBundle\Entity\OrderItem', 'e') 
                ->leftJoin('e.history', 'h') 
                ->groupBy('e.id') 
            ;
            $qb2->having(sprintf('(SUM(h.amount)) %s :%s', $data['comparison'], $paramName));
            $queryBuilder->setParameter($paramName, $data['value']); 
            
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('entity.id', $qb2->getDQL())
            ); 
        }
    }
}
