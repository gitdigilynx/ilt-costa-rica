<?php

namespace App\Wicrew\PageBundle\Validator\Constraints;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\PageBundle\Entity\PageContent;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * UniqueSlugValidator
 */
class UniqueSlugValidator extends ConstraintValidator {

    /**
     * Utils
     *
     * @var Utils
     */
    private $utils;

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
     * @return UniqueSlugValidator
     */
    public function setUtils(Utils $utils): UniqueSlugValidator {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($entity, Constraint $constraint) {
        if (!$constraint instanceof UniqueSlug || !$entity instanceof PageContent) {
            throw new UnexpectedTypeException($constraint, Constraint::class);
        }

        // Custom constraints should ignore null and empty values to allow
        // Other constraints (NotBlank, NotNull, etc.) take care of that
        if (!$entity->getSlug()) {
            return;
        }

        if (!is_string($entity->getSlug())) {
            // Throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($entity->getSlug(), 'string');

            // Separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }

        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->andX(
            Criteria::expr()->neq('id', $entity->getId()),
            Criteria::expr()->eq('slug', $entity->getSlug())
        ));

        if ($this->getUtils()->getEntityManager()->getRepository(PageContent::class)->matching($criteria)->count() > 0) {
            $this->context->buildViolation($constraint->message, ['{{ slug }}' => $entity->getSlug()])
                ->atPath('slug')
                ->addViolation();
        }
    }

}
