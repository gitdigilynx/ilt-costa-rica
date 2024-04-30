<?php

namespace App\Wicrew\CoreBundle\Form\Type;

use DateTime;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TimepickerType
 */
class TimePickerType extends AbstractType implements DataTransformerInterface {
    private const TIMEPICKER_CLASS = 'form_timepicker';
    private const TIMEPICKER_FORMAT = 'H:i';

    /**
     * {@inheritDoc}
     */
    public function getParent() {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->addModelTransformer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'attr' => [ 'class' => self::TIMEPICKER_CLASS ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'timepicker';
    }

    public function transform($timeAsDT) {
        /* @var DateTime|null $timeAsDT */
        return $timeAsDT === null ? '' : $timeAsDT->format(self::TIMEPICKER_FORMAT);
    }

    public function reverseTransform($timeAsString) {
        try {
            return strlen($timeAsString) <= 0 ? null : new DateTime($timeAsString);
        } catch (Exception $e) {
            throw new TransformationFailedException("Invalid time specified.");
        }
    }
}
