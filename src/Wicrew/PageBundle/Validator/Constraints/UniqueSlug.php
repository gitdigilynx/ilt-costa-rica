<?php

namespace App\Wicrew\PageBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * UniqueSlug
 *
 * @Annotation
 */
class UniqueSlug extends Constraint {

    /**
     * Error message
     *
     * @var string
     */
    public $message = 'The slug "{{ slug }}" is already used.';

    /**
     * {@inheritDoc}
     */
    public function validatedBy() {
        return get_class($this) . 'Validator';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }

}
