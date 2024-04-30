<?php

namespace App\Wicrew\CoreBundle\Service;

//use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

//use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * Cache
 */
class Cache {

    /**
     * Default lifetime (seconds)
     *
     * @var int
     */
    const LIFETIME = 60 * 60 * 24 * 7;

    /**
     * TagAwareAdapter
     *
     * @var TagAwareAdapter
     */
    private $cache;

    /**
     * Namespace
     *
     * @var string
     */
    private $namespace = '';

    /**
     * Lifetime (seconds)
     *
     * @var int
     */
    private $lifetime = self::LIFETIME;

    /**
     * Directory
     *
     * @var string
     */
    private $directory = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->initCacheAdapter();
    }

    /**
     * Initialize cache adapter
     */
    protected function initCacheAdapter(): Cache {
        $this->setCache($this->getCacheAdapter());
        return $this;
    }

    /**
     * Get cache adapter
     *
     * @return TagAwareAdapter
     */
    public function getCacheAdapter(): TagAwareAdapter {
        return new TagAwareAdapter(
            new FilesystemAdapter($this->getNamespace(), $this->getLifetime(), $this->getDirectory())
        );
    }

    /**
     * Get TagAwareAdapter
     *
     * @return TagAwareAdapter
     */
    public function getCache(): TagAwareAdapter {
        return $this->cache;
    }

    /**
     * Set TagAwareAdapter
     *
     * @param TagAwareAdapter $cache
     *
     * @return Cache
     */
    public function setCache(TagAwareAdapter $cache): Cache {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Get namespace
     *
     * @return string
     */
    public function getNamespace() {
        return $this->namespace;
    }

    /**
     * Set namespace
     *
     * @param string $namespace
     *
     * @return Cache
     */
    public function setNamespace($namespace): Cache {
        $this->namespace = $namespace;
        $this->initCacheAdapter();
        return $this;
    }

    /**
     * Get lifetime
     *
     * @return int
     */
    public function getLifetime() {
        return $this->lifetime;
    }

    /**
     * Set lifetime
     *
     * @param int $lifetime
     *
     * @return Cache
     */
    public function setLifetime($lifetime): Cache {
        $this->lifetime = ((int)$lifetime) >= 0 ? (int)$lifetime : 0;
        $this->initCacheAdapter();
        return $this;
    }

    /**
     * Get directory
     *
     * @return string
     */
    public function getDirectory() {
        return $this->directory;
    }

    /**
     * Set directory
     *
     * @param string $directory
     *
     * @return Cache
     */
    public function setDirectory($directory): Cache {
        $this->directory = $directory;
        $this->initCacheAdapter();
        return $this;
    }

    /**
     * Save cache
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $tag
     * @param int $lifetime
     *
     * @return Cache
     */
    public function save($key, $value, $tag = null, $lifetime = null): Cache {
        //        $this->saveMultiple([$key => $value]);
        $item = $this->getCache()->getItem($key);
        $item->set($value);
        if ($tag) {
            $item->tag($tag);
        }
        if (is_numeric($lifetime)) {
            $item->expiresAfter((int)$lifetime);
        }
        $this->getCache()->save($item);
        return $this;
    }

    /**
     * Save multiple cache
     *
     * @param array $values
     *
     * @return Cache
     */
    //    public function saveMultiple(array $values): Cache
    //    {
    //        $this->getCache()->setMultiple($values);
    //        return $this;
    //    }

    /**
     * Has cache
     *
     * @param string $key
     * @param bool $withValidValue
     *
     * @return bool
     */
    public function has($key, $withValidValue = false): bool {
        return (
            $this->getCache()->hasItem($key)
            && (
                !$withValidValue
                || $this->getCache()->getItem($key)->isHit()
            )
        );
    }

    /**
     * Get cache
     *
     * @param string $key
     * @param bool $asItem
     *
     * @return mixed
     */
    public function get($key, $asItem = false) {
        if ($this->has($key)) {
            $item = $this->getCache()->getItem($key);
            return $asItem ? $item : $item->get();
        } else {
            return null;
        }
    }

    /**
     * Get multiple cache
     *
     * @param array $keys
     * @param bool $asItem
     *
     * @return array
     */
    public function getMultiple(array $keys, $asItem = false) {
        $returnItems = [];
        $items = $this->getCache()->getItems($keys);
        foreach ($items as $keys => $item) {
            if ($asItem) {
                $returnItems[$keys] = $item;
            } else {
                $returnItems[$keys] = $item && $item->isHit() ? $item->get() : null;
            }
        }
        return $returnItems;
    }

    /**
     * Delete cache
     *
     * @param string $key
     *
     * @return Cache
     */
    public function delete($key): Cache {
        $this->getCache()->deleteItem($key);
        return $this;
    }

    /**
     * Delete multiple cache
     *
     * @param array $keys
     *
     * @return Cache
     */
    public function deleteMultiple(array $keys): Cache {
        $this->getCache()->deleteItems($keys);
        return $this;
    }

    /**
     * Delete cache by tag
     *
     * @param mixed $tag
     *
     * @return Cache
     */
    public function deleteByTag($tag): Cache {
        $tag = is_array($tag) ? $tag : [$tag];
        $this->getCache()->invalidateTags($tag);
        return $this;
    }

    /**
     * Clear cache
     *
     * @return Cache
     */
    public function clear(): Cache {
        $this->getCache()->clear();
        return $this;
    }

}
