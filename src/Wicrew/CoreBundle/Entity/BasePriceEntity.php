<?php


namespace App\Wicrew\CoreBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * BasePriceEntity
 *
 * @ORM\MappedSuperclass
 */
abstract class BasePriceEntity extends BaseEntity implements IBasePriceEntity {
    use BasePriceEntityImplementation;
}