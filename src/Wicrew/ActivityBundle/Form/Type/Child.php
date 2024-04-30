<?php

namespace App\Wicrew\ActivityBundle\Form\Type;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ActivityBundle\Entity\ActivityHasChild;
use App\Wicrew\CoreBundle\Entity\BasePriceEntity;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\Tax;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Child
 */
class Child extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('regular', EntityType::class, [
                'label' => 'activity.child.regular',
                'placeholder' => 'core.option.please_select',
                'required' => true,
                'class' => Activity::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('a')
                        // ->where('a.combo = :isCombo')
                        ->orderBy('a.name', 'ASC')
                        // ->setParameter('isCombo', false)
                    ;
                },
                'choice_label' => 'name'
            ])
            ->add('supplier', EntityType::class, [
                'label' => 'activity.child.supplier',
                'placeholder' => 'core.option.please_select',
                'required' => true,
                'class' => Partner::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->orderBy('d.bizName', 'ASC');
                },
                'choice_label' => 'bizName'
            ])
            ->add('priceType', ChoiceType::class, [
                'label' => 'core.price_type',
                'required' => true,
                'choices' => [
                    'per.person' => BasePriceEntity::PRICE_TYPE_PER_PERSON,
                    'core.fixed' => BasePriceEntity::PRICE_TYPE_FOR_THE_TRIP
                ]
            ]) 
            ->add('adultNetPrice', null, [
                'label' => 'core.net.adult',
                'required' => true
            ]) 
            ->add('childNetPrice', null, [
                'label' => 'core.net.child',
                'required' => true
            ]) 
            ->add('fixedNetPrice', null, [
                'label' => 'core.net.fixed',
                'required' => true
            ])
            ->add('tax', EntityType::class, [
                'label' => 'Tax',
                'placeholder' => 'core.option.please_select',
                'required' => true,
                'class' => Tax::class,
                'choice_label' => function (Tax $entity) {
                    return $entity->getLabel();
                }
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => ActivityHasChild::class,
            'cascade_validation' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'activity_child_item';
    }

}
