<?php

namespace App\Wicrew\ActivityBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ActivityLocationRepository
 */
class ActivityLocationRepository extends EntityRepository {

    /**
     * Get active activities by location
     *
     * @param array|int $ignoreLocationIds
     * @param array $order
     *
     * @return array
     */
    public function getAllLocations($ignoreLocationIds = [], array $order = []) {
        $qb = $this->createQueryBuilder('l');

        if ($ignoreLocationIds) {
            $ignoreLocationIds = is_array($ignoreLocationIds) ? $ignoreLocationIds : [$ignoreLocationIds];
            $qb->andWhere($qb->expr()->notIn('l.id', ':ignoreLocationIds'))
                ->setParameter('ignoreLocationIds', $ignoreLocationIds);
        }

        if ($order) {
            $order['field'] = isset($order['field']) && in_array($order['field'], ['name']) ? $order['field'] : 'name';
            $order['dir'] = isset($order['dir']) && in_array($order['dir'], ['asc', 'desc']) ? $order['dir'] : 'asc';
            $qb->orderBy('l.' . $order['field'], strtoupper($order['dir']));
        } else {
            $qb->orderBy('RAND()');
        }

        return $qb->getQuery()->getResult();
    }

}