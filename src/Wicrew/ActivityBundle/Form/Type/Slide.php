<?php

namespace App\Wicrew\ActivityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

//use Symfony\Component\Form\FormEvents;
//use Symfony\Component\Form\FormEvent;

/**
 * Slide
 */
class Slide extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $defaultPositionAttr = [];
        if (isset($options['auto_initialize']) && $options['auto_initialize']) {
            $defaultPositionAttr['value'] = 1;
        }

        $builder
            ->add('imageFile', VichImageType::class, [
                'label' => 'activity.slide.image',
                'required' => false,
                'allow_delete' => false,
                'attr' => ['data-validate' => 'filetype:image/jpeg|image/jpg|image/png|image/bmp,filesize:8000000']
            ])
            ->add('position', IntegerType::class, [
                'label' => 'activity.slide.position',
                'attr' => $defaultPositionAttr
            ])
            //            ->add('alt', null, [
            //                'label' => 'activity.slide.activity_slide_alt',
            //                'required' => false
            //            ])
        ;

        //        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($builder) {
        //            $entity = $event->getData();
        //            if (!$entity || !$entity->getId()) {
        //                $field = $event->getForm()->get('imageFile');
        //                $options = $field->getConfig()->getOptions();
        //                $options['required'] = true;
        //                $event->getForm()->add('imageFile', VichImageType::class, $options);
        //            }
        //        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'App\Wicrew\ActivityBundle\Entity\ActivitySlide',
            'cascade_validation' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'activity_slide_item';
    }

}
