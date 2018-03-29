<?php
/**
 * Created by Fernando Robledo <overdesign@gmail.com>.
 */

use Overdesign\PsrCache\CacheItem;
use Overdesign\PsrCache\FileCacheDriver;

/**
 * @covers Overdesign\PsrCache\FileCacheDriver
 * @covers Overdesign\PsrCache\CacheItem
 */
class FileCacheDriverTest extends PHPUnit_Framework_TestCase
{
    private function getCachePool()
    {
        return new FileCacheDriver(__DIR__ . '/tmp/');
    }

    public function testPersist()
    {
        $key = 'abcABC_123.123';
        $data = 'My data';

        $driver = $this->getCachePool();
        $item = new CacheItem($key);

        $item->set($data);

        $this->assertTrue($driver->save($item));
        $this->assertTrue($driver->hasItem($key));
        $this->assertInstanceOf('Overdesign\PsrCache\CacheItem', $driver->getItem($key));
        $this->assertTrue($driver->deleteItem($key));
        $this->assertFalse($driver->hasItem($key));
        $this->assertInstanceOf('Overdesign\PsrCache\CacheItem', $driver->getItem($key));
    }

    /**
     * @expectedException          Overdesign\PsrCache\InvalidArgumentException
     * @expectedExceptionMessage   The given key BadKey#@! contains invalid characters.
     */
    public function testBadKey()
    {
        $driver = $this->getCachePool();

        $driver->getItem('BadKey#@!');
    }

    public function testGetItems()
    {
        $keys = array('1', '2', '3');

        $driver = $this->getCachePool();

        $items = $driver->getItems($keys);

        $this->assertEquals(count($keys), count($items));
    }

    public function testDeleteItems()
    {
        $keys = array('1', '2', '3');

        $driver = $this->getCachePool();

        $this->assertTrue($driver->deleteItems($keys));

        $items = $driver->getItems($keys);

        foreach ($items as $key => $item) {
            /** @var $item CacheItem */
            $item->set('test');
            $driver->save($item);
        }

        $this->assertTrue($driver->deleteItems($keys));

    }

    public function testSaveDeferred()
    {
        $driver = $this->getCachePool();

        $item = $driver->getItem('deferred');

        $item->set('test');
        $driver->saveDeferred($item);

        $this->assertTrue($driver->getItem('deferred')->isHit());

        $driver->commit();

        $this->assertTrue($driver->getItem('deferred')->isHit());
        $this->assertTrue($driver->deleteItem('deferred'));
    }

    public function testClear()
    {
        $keys   = array('1', '2', '3');
        $driver = $this->getCachePool();

        $this->assertTrue($driver->deleteItems($keys));

        $items = $driver->getItems($keys);

        foreach ($items as $key => $item) {
            /** @var $item CacheItem */
            $item->set('test');
            $driver->save($item);
        }

        $this->assertTrue($driver->clear());
    }

    public function testClearExpired()
    {
        $keys1   = array('a', 'b', 'c');
        $keys2   = array('d', 'e', 'f');
        $driver = $this->getCachePool();

        $this->assertTrue($driver->deleteItems($keys1));
        $this->assertTrue($driver->deleteItems($keys2));

        $items1 = $driver->getItems($keys1);
        $items2 = $driver->getItems($keys2);

        foreach ($items1 as $key => $item) {
            /** @var $item CacheItem */
            $item->set('clean-expired');
            $item->expiresAfter(1);
            $driver->save($item);
        }

        foreach ($items2 as $key => $item) {
            /** @var $item CacheItem */
            $item->set('clean-expired');
            $item->expiresAfter(3);
            $driver->save($item);
        }

        sleep(2);

        $this->assertTrue($driver->clearExpired());
        $fileCount = glob($driver->getPath() . 'cachepool-*.php', GLOB_NOSORT);
        $this->assertCount(3, $fileCount);

        sleep(2);

        $this->assertTrue($driver->clearExpired());
        $fileCount = glob($driver->getPath() . 'cachepool-*.php', GLOB_NOSORT);

        $this->assertCount(0, $fileCount);
    }

    public function testGc()
    {
        $keys1   = array('a', 'b', 'c');
        $keys2   = array('d', 'e', 'f');
        $driver = $this->getCachePool();

        $this->assertTrue($driver->deleteItems($keys1));
        $this->assertTrue($driver->deleteItems($keys2));

        $items1 = $driver->getItems($keys1);
        $items2 = $driver->getItems($keys2);

        foreach ($items1 as $key => $item) {
            /** @var $item CacheItem */
            $item->set('garbage-expired');
            $item->expiresAfter(1);
            $driver->save($item);
        }

        foreach ($items2 as $key => $item) {
            /** @var $item CacheItem */
            $item->set('garbage-not-expired');
            $item->expiresAfter(1000);
            $driver->save($item);
        }


        sleep(2);

        $this->assertTrue($driver->gc(true, true));
        $fileCount = glob($driver->getPath() . 'cachepool-*.php', GLOB_NOSORT);
        $this->assertCount(3, $fileCount);

        $this->assertTrue($driver->gc(false, true));
        $fileCount = glob($driver->getPath() . 'cachepool-*.php', GLOB_NOSORT);
        $this->assertCount(0, $fileCount);

    }
}
