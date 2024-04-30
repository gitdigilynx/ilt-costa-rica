<?php

namespace App\Wicrew\SystemConfigurationBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SystemConfigurationRepository
 */
class SystemConfigurationRepository extends EntityRepository {

    /**
     * Find value by key
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getConfigValue($key) {
        try {
            return $this->createQueryBuilder('s')
                ->select('s.value')
                ->where('s.key = :key')
                ->setParameter('key', $key)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get values by group path
     *
     * @param string $groupPath
     * @param bool $asDimension
     *
     * @return array
     */
    public function getValuesByGroupPath($groupPath, $asDimension = false) {
        $configs = $this->createQueryBuilder('s')
            ->select('s.key, s.value')
            ->andWhere('s.key LIKE :key')
            ->setParameter('key', $groupPath . '/%')
            ->getQuery()
            ->getArrayResult();

        if ($asDimension) {
            $dimensionValues = [];
            foreach ($configs as $config) {
                $keys = explode('/', $config['key']);

                if (!isset($dimensionValues[$keys[0]])) {
                    $dimensionValues[$keys[0]] = [];
                }
                $tmp = &$dimensionValues[$keys[0]];

                $count = 1;
                do {
                    if (($count + 1) == count($keys)) {
                        $tmp[$keys[$count]] = $config['value'];
                    } else {
                        if (!isset($tmp[$keys[$count]])) {
                            $tmp[$keys[$count]] = [];
                        }
                        $tmp = &$tmp[$keys[$count]];
                    }
                } while (++$count < count($keys));
            }

            return $dimensionValues;
        } else {
            $cleanValues = [];
            foreach ($configs as $config) {
                $cleanValues[$config['key']] = $config['value'];
            }

            return $cleanValues;
        }
    }

}
