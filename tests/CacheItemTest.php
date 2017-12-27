<?php
/**
 * Created by Fernando Robledo <overdesign@gmail.com>.
 */

use Overdesign\PsrCache\CacheItem;

/**
 * @covers Overdesign\PsrCache\CacheItem
 */
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

    public function testCacheItemSet()
    {
        $item = new CacheItem('key', null, true);
        $item->set('my data')
            ->expiresAt(100)
            ->expiresAfter(null);

        $this->assertTrue($item->isHit());
        $this->assertEquals('key', $item->getKey());
        $this->assertEquals('my data', $item->get());
    }

}
