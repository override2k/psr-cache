<?php
/**
 * Created by Fernando Robledo <fernando.robledo@opinno.com>.
 *
 * Date: 9/10/17, Time: 13:07
 */

namespace Overdesign\PsrCache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;


class FileCacheDriver implements CacheItemPoolInterface
{
    /** @var string */
    protected $path;
    /** @var CacheItem[] */
    protected $cache;

    /**
     * FileCacheDriver constructor.
     *
     * @param string $path
     */
    public function __construct($path = __DIR__)
    {
        $this->path = $path;
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function checkKey($key)
    {
        if (!preg_match('/^[a-zA-Z\d\.\_]+$/', $key)) {
            throw new InvalidArgumentException(sprintf('The given key %s contains invalid characters.', $key));
        }

        return $key;
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws \Overdesign\PsrCache\InvalidArgumentException
     */
    private function getFilename($key)
    {
        return $this->path . "/cachepool-{$this->checkKey($key)}.php";
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {
        $file = $this->getFilename($key);

        if (is_readable($file) && is_file($file)) {

            $data = file_get_contents($file);
            $data = $data === false ? false : unserialize($data);

            if ($data !== false && $data instanceof CacheItem) {
                if ($data->isHit() === false) {// Expired
                    $this->deleteItem($key);
                }

                return $data;
            }

        }

        return new CacheItem($key);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = array())
    {
        $collection = array();

        foreach ($keys as $key) {
            $collection[] = $this->getItem($key);
        }

        return $collection;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        return file_exists($this->getFilename($key));
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        $files  = glob($this->path . '/cachepool-*.php');
        $result = true;

        foreach ($files as $file) {

            if (is_file($file))
                $result = @unlink($file) && $result;
        }

        return $result;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        $file = $this->getFilename($key);

        return @unlink($file);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        $result = true;

        foreach ($keys as $key) {
            $result = $this->deleteItem($key) && $result;
        }

        return $result;
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool True if the item was successfully persisted. False if there was an error.
     * True if the item was successfully persisted. False if there was an error.
     *
     * @throws CacheException
     */
    public function save(CacheItemInterface $item)
    {
        $file = $this->getFilename($item->getKey());

        if (false === file_put_contents($file, serialize($item)))
            throw new CacheException(sprintf('Cant write to cache file %s', $file), CacheException::ERROR_CANT_WRITE);

        return true;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->cache[$item->getKey()] = $item;

        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     * True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     *
     * @throws CacheException
     */
    public function commit()
    {
        $allSaved = true;

        foreach ($this->cache as $item) {
            $allSaved = $this->save($item) && $allSaved;
        }

        return $allSaved;
    }

}
