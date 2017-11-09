<?php
/**
 * Created by PhpStorm.
 * User: over
 * Date: 09/11/2017
 * Time: 21:41
 */

use Overdesign\PsrCache\CacheItem;

class CacheItemTest extends PHPUnit_Framework_TestCase
{

    public function testCacheItemExpireAfter()
    {
        $item = new CacheItem('key', 'data', true);
        $item->expiresAfter(2);

        $this->assertTrue($item->isHit());
        $this->assertEquals('data', $item->get());

        sleep(3);

        $this->assertFalse($item->isHit());
        $this->assertNull($item->get());

    }

    public function testCacheItemExpiresAt()
    {
        $item = new CacheItem('key', 'data', true);

        $expiration = new DateTime();
        $expiration->add(new DateInterval('PT2S'));

        $item->expiresAt($expiration);

        $this->assertTrue($item->isHit());
        $this->assertEquals('data', $item->get());

        sleep(5);

        $this->assertFalse($item->isHit());
        $this->assertNull($item->get());
    }

}
