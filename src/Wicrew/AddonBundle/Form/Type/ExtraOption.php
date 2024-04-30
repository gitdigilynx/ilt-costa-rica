<?php

namespace App\Wicrew\AddonBundle\Form\Type;

use App\Wicrew\AddonBundle\Entity\ExtraOption as ExtraOptionEntity;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\Tax;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ExtraOption
 */
class ExtraOption extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('label', null, [
                'label' => 'addon.option.label',
                'required' => true
            ])
            ->add('rackPrice', null, [
                'label' => 'addon.option.rack_price',
                'required' => true
            ])
            ->add('netPrice', null, [
                'label' => 'addon.option.net_price',
                'required' => true
            ])
            ->add('tax', EntityType::class, [
                'label' => 'addon.option.tax',
                'placeholder' => 'core.option.please_select',
                'required' => true,
                'class' => Tax::class,
                'choice_label' => function (Tax $entity) {
                    return $entity->getLabel();
                }
            ])
            ->add('priceType', ChoiceType::class, [
                'label' => 'addon.option.price_type',
                'required' => true,
                'placeholder' => 'core.option.please_select',
                'choices' => [
                    'addon.option.price_type.per_person' => ExtraOptionEntity::PRICE_TYPE_PER_PERSON,
                    'addon.option.price_type.for_the_trip' => ExtraOptionEntity::PRICE_TYPE_FOR_THE_TRIP
                ]
            ])
            ->add('supplier', EntityType::class, [
                'label' => 'addon.option.supplier',
                'placeholder' => 'core.option.please_select',
                'required' => true,
                'class' => Partner::class,
                'choice_label' => function (Partner $entity) {
                    return $entity->__toString();
                }
            ])
            ->add('position', HiddenType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'App\Wicrew\AddonBundle\Entity\ExtraOption',
            'cascade_validation' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'addon_option_item';
    }

}
