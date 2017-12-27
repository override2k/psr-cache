<?php
/**
 * Created by Fernando Robledo <overdesign@gmail.com>.
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
    protected $deferred = array();

    /**
     * FileCacheDriver constructor.
     *
     * @param string $path
     */
    public function __construct($path = __DIR__)
    {
        $this->path = substr($path, strlen($path) - 1, 1) === DIRECTORY_SEPARATOR ? $path : $path . DIRECTORY_SEPARATOR;
    }

    /**
     * Returns current cache folder path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function checkKey($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('The given key must be a string.');
        } elseif (strlen($key) > 64) {
            throw new InvalidArgumentException('The key length must not exceed 64 characters.');
        } elseif (!preg_match('/^[a-zA-Z\d\.\_]+$/', $key)) {
            throw new InvalidArgumentException(sprintf('The given key %s contains invalid characters.', $key));
        }

        return $key;
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function getFilename($key)
    {
        return $this->path . "cachepool-{$this->checkKey($key)}.php";
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
        if (array_key_exists($this->checkKey($key), $this->deferred))
            return clone $this->deferred[$key];

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
            $collection[$key] = $this->getItem($key);
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
        return $this->getItem($key)->isHit();
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        $this->deferred = array();

        $files  = glob($this->path . 'cachepool-*.php', GLOB_NOSORT);
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
        if (array_key_exists($this->checkKey($key), $this->deferred))
            unset($this->deferred[$key]);

        $file = $this->getFilename($key);

        return file_exists($file) ? @unlink($file) : true;
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
        $this->deferred[$item->getKey()] = $item;

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

        foreach ($this->deferred as $item) {
            $allSaved = $this->save($item) && $allSaved;
            unset($this->deferred[$item->getKey()]);
        }

        return $allSaved;
    }

    /**
     * Deletes all the expired items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clearExpired()
    {
        $regex  = '/cachepool-(?P<key>[a-zA-Z\d\.\_]+)\.php$/';
        $files  = glob($this->path . 'cachepool-*.php', GLOB_NOSORT);
        $result = true;

        foreach ($files as $file) {

            if (preg_match($regex, $file, $key) === false) {
                continue;
            }

            try {

                $key = $this->checkKey($key['key']);

                is_file($file) && !$this->getItem($key); // Get item auto clears expired items

            } catch (InvalidArgumentException $e) {

                $result = false;

            }

        }

        return $result;
    }

    /**
     * Cache pool garbage collector, deletes all cache files and optional empty directories in the current cache path
     * It can do a recursive search over the main directory, the maximum deep for the recursive can be specified
     *
     * @param bool $checkExpired if true only deletes the items that are expired else deletes all cached files
     * @param bool $recursive true for doing a recursive gc over the main path
     * @param bool $deleteEmpty true for deleting empty directories inside the path
     * @param int $depth maximum search depth
     *
     * @return bool True if all the items where deleted
     *
     * @throws InvalidArgumentException
     */
    public function gc($checkExpired = true, $recursive = false, $deleteEmpty = true, $depth = 1)
    {
        $recursive = $recursive && $depth > 0;
        $result    = $checkExpired ? $this->clearExpired() : $this->clear();

        if (!$recursive) {
            return $result;
        }

        $dirs = glob($this->path . '*', GLOB_ONLYDIR | GLOB_NOSORT);

        foreach ($dirs as $dir) {

            $pool = new self($dir);
            $result = $result && $pool->gc($recursive, $deleteEmpty, --$depth);

            if ($deleteEmpty && count(glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT)) === 0) {
                rmdir($dir);
            }
        }

        return $result;
    }

    /**
     * Save deferred items before destruct
     */
    public function __destruct()
    {
        $this->commit();
    }
}
