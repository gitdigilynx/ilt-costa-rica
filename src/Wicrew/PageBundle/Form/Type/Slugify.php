<?php

namespace App\Wicrew\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Slugify
 */
class Slugify extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('slug', null, [
                'label' => false,
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'App\Wicrew\PageBundle\Entity\Slugify',
            'cascade_validation' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    //    public function getBlockPrefix()
    //    {
    //        return 'slugify_item';
    //    }

}
