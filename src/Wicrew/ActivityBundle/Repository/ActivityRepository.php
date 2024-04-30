<?php

namespace App\Wicrew\ActivityBundle\Repository;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ActivityBundle\Entity\ActivityLocation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * ActivityRepository
 */
class ActivityRepository extends EntityRepository {

    /**
     * Get active activities
     *
     * @param array $where
     * @param array $order
     *
     * @return QueryBuilder
     */
    public function getActiveActivities(array $where = [], array $order = ['field' => 'sort_order', 'dir' => 'asc']) {
        $qb = $this->createQueryBuilder('a')
            ->where('a.visibility = :visibility AND a.status = :status')
            ->andWhere('a.archived = :archived')
            ->setParameter('archived', false)
            ->setParameter('visibility', true)
            ->setParameter('status', Activity::STATUS_ONLINE);

        if (isset($where['location']) && $where['location']) {
            $qb->join('a.location', 'l')
                ->andWhere('l.id = :locationId')
                ->setParameter('locationId', $where['location'] instanceof ActivityLocation ? $where['location']->getId() : (int)$where['location']);
        }

        if (isset($where['types']) && $where['types']) {
            $types = explode(',', $where['types']);
            $conditions = [];
            foreach ($types as $idx => $typeNumber) {
                $paramName = 'type' . $idx;
                $conditions[] = "FIND_IN_SET(:" . $paramName . ", REPLACE(REPLACE(a.types, '[', ''), ']', '')) > 0";

                $qb->setParameter($paramName, $typeNumber);
            }

            $qb->andWhere(
                '(' . implode(' OR ', $conditions) . ')'
            );
        }

        if (isset($where['durations']) && $where['durations']) {
            $durations = explode(',', $where['durations']);
            $conditions = [];
            foreach ($durations as $idx => $duration) {
                $paramName = 'duration' . $idx;
                $conditions[] = 'a.duration LIKE :' . $paramName;

                $qb->setParameter($paramName, $duration);
            }

            $qb->andWhere(
                '(' . implode(' OR ', $conditions) . ')'
            );
        }

        if (isset($where['difficulties']) && $where['difficulties']) {
            $difficulties = explode(',', $where['difficulties']);
            $conditions = [];
            foreach ($difficulties as $idx => $difficulty) {
                $paramName = 'diff' . $idx;
                $conditions[] = "FIND_IN_SET(:" . $paramName . ", REPLACE(REPLACE(a.difficultyLevels, '[', ''), ']', '')) > 0";

                $qb->setParameter($paramName, $difficulty);
            }

            $qb->andWhere(
                '(' . implode(' OR ', $conditions) . ')'
            );
        }

        $order['field'] = isset($order['field']) && in_array($order['field'], ['id']) ? $order['field'] : 'id';
        $order['dir'] = isset($order['dir']) && in_array($order['dir'], ['asc', 'desc']) ? $order['dir'] : 'desc';

        //        if ($order['field'] == 'price') {
        //            if ($order['dir'] == 'asc') {
        //                $order['field'] == 'minPrice';
        //            } else {
        //                $order['field'] == 'maxPrice';
        //            }
        //        }

        $qb->orderBy('a.' . $order['field'], strtoupper($order['dir']));

        return $qb;
    }

    /**
     * Get active activities by location
     *
     * @param ActivityLocation|int $location
     * @param array|int $ignoreActivityIds
     * @param int $limit
     * @param array $order
     *
     * @return array
     */
    public function getActiveActivitiesByLocation($location, $ignoreActivityIds = [], $limit = 4, array $order = ['field' => 'sort_order', 'dir' => 'asc']) {
        $qb = $this->createQueryBuilder('a')
            ->join('a.location', 'l')
            ->andWhere('l.id = :locationId')
            ->where('l.id = :locationId AND a.visibility = :visibility AND a.status = :status')
            ->setParameter('locationId', $location instanceof ActivityLocation ? $location->getId() : (int)$location)
            ->setParameter('visibility', true)
            ->setParameter('status', Activity::STATUS_ONLINE)
            ->setMaxResults($limit);

        if ($ignoreActivityIds) {
            $ignoreActivityIds = is_array($ignoreActivityIds) ? $ignoreActivityIds : [$ignoreActivityIds];
            $qb->andWhere($qb->expr()->notIn('a.id', ':ignoreActivityIds'))
                ->setParameter('ignoreActivityIds', $ignoreActivityIds);
        }

        if ($order) {
            $order['field'] = isset($order['field']) && in_array($order['field'], ['id']) ? $order['field'] : 'id';
            $order['dir'] = isset($order['dir']) && in_array($order['dir'], ['asc', 'desc']) ? $order['dir'] : 'desc';
            $qb->orderBy('a.' . $order['field'], strtoupper($order['dir']));
        } else {
            $qb->orderBy('RAND()');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get active activity by slug
     *
     * @param string $slug
     *
     * @return Activity|null
     */
    public function getActiveActivityBySlug($slug) {
        return $this->createQueryBuilder('a')
            ->where('a.visibility = :visibility AND a.status = :status AND a.slug = :slug AND a.archived = :archived')
            ->setParameter('archived', false)
            ->setParameter('visibility', true)
            ->setParameter('status', Activity::STATUS_ONLINE)
            ->setParameter('slug', $slug)
            ->orderBy('a.sortOrder', "ASC")
            ->getQuery()
            ->getOneOrNullResult();
    }

}