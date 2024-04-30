<?php

namespace App\Wicrew\CronBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CronRepository
 */
class CronRepository extends EntityRepository {

    /**
     * Get executable crons
     *
     * @param bool $asArray
     *
     * @return array
     */
    public function getExecutableCrons($asArray = false) {
        $now = new \DateTime();
        $crons = $this->createQueryBuilder('c')
            ->where('c.active = :active AND (c.executedAt IS NULL OR c.executedAt <= :executedAt)')
            ->setParameter('active', true)
            ->setParameter('executedAt', $now->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult($asArray ? \Doctrine\ORM\Query::HYDRATE_ARRAY : null);

        return $crons;
    }

}
