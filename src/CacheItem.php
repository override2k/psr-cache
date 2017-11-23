<?php
/**
 * Created by Fernando Robledo <fernando.robledo@opinno.com>
 */

namespace Overdesign\PsrCache;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;


class CacheItem implements CacheItemInterface
{

    /** @var string */
    protected $key;
    /** @var bool */
    protected $isHit;
    /** @var mixed */
    protected $data;
    /** @var DateTime */
    protected $expiresAt;


    public function __construct($key, $data = null, $isHit = false)
    {
        $this->key       = $key;
        $this->data      = $data;
        $this->isHit     = $isHit;
        $this->expiresAt = null;
    }

    /**
     * @return bool
     */
    private function isExpired()
    {
        if ($this->expiresAt === null)
            return false;

        $now = new DateTime();

        return $now > $this->expiresAt;
    }

    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get()
    {
        return $this->isHit() ? $this->data : null;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit()
    {
        return $this->isHit && !$this->isExpired();
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value)
    {
        $this->data = $value;

        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param DateTimeInterface|null $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof DateTime || // php < 5.5
            $expiration instanceof DateTimeInterface) {

            $this->expiresAt = $expiration;

        }

        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval|null $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time)
    {
        if ($time === null) {
            $this->expiresAt = $time;
            return $this;
        }

        $now  = new DateTime();
        $time = ($time instanceof DateInterval) ? $time : new DateInterval('PT' . $time . 'S');

        $this->expiresAt = $now->add($time);

        return $this;
    }

    /**
     * Updates isHit status when unserializing
     */
    public function __wakeup()
    {
        $this->isHit = !$this->isExpired();
    }

}
