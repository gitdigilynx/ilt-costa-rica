<?php


namespace App\Wicrew\CoreBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * ProductBasePriceEntity
 *
 * @ORM\MappedSuperclass
 */
abstract class ProductBasePriceEntity extends BaseEntity implements IBasePriceEntity {
    use ProductBasePriceEntityImplementation;
}