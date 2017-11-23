<?php
/**
 * Created by Fernando Robledo <fernando.robledo@opinno.com>.
 *
 * Date: 9/10/17, Time: 13:35
 */

use Overdesign\PsrCache\CacheItem;
use Overdesign\PsrCache\FileCacheDriver;

/**
 * @covers Overdesign\PsrCache\FileCacheDriver
 * @covers Overdesign\PsrCache\CacheItem
 */
class FileCacheDriverTest extends PHPUnit_Framework_TestCase
{

    public function testPersist()
    {
        $key = 'abcABC_123.123';
        $data = 'My data';

        $driver = new FileCacheDriver();
        $item = new CacheItem($key);

        $item->set($data);

        $this->assertTrue($driver->save($item));
        $this->assertTrue($driver->hasItem($key));
        $this->assertInstanceOf('Overdesign\PsrCache\CacheItem', $driver->getItem($key));
        $this->assertTrue($driver->deleteItem($key));
        $this->assertFalse($driver->hasItem($key));
        $this->assertFalse($driver->deleteItem($key));
        $this->assertInstanceOf('Overdesign\PsrCache\CacheItem', $driver->getItem($key));
    }

    /**
     * @expectedException          Overdesign\PsrCache\InvalidArgumentException
     * @expectedExceptionMessage   The given key BadKey#@! contains invalid characters.
     */
    public function testBadKey()
    {
        $driver = new FileCacheDriver();

        $driver->getItem('BadKey#@!');
    }

    public function testGetItems()
    {
        $keys = array('1', '2', '3');

        $driver = new FileCacheDriver();

        $items = $driver->getItems($keys);

        $this->assertEquals(count($keys), count($items));
    }

    public function testDeleteItems()
    {
        $keys = array('1', '2', '3');

        $driver = new FileCacheDriver();

        $this->assertFalse($driver->deleteItems($keys));

        $items = $driver->getItems($keys);

        foreach ($items as $key => $item) {
            /** @var $item CacheItem */
            $item->set('test');
            $driver->save($item);
        }

        $this->assertTrue($driver->deleteItems($keys));

    }

    public function testSaveDeffered()
    {
        $driver = new FileCacheDriver();

        $item = $driver->getItem('deffered');

        $item->set('test');
        $driver->saveDeferred($item);

        $this->assertFalse($driver->getItem('deffered')->isHit());

        $driver->commit();

        $this->assertTrue($driver->getItem('deffered')->isHit());
        $this->assertTrue($driver->deleteItem('deffered'));
    }

    public function testClear()
    {
        $keys   = array('1', '2', '3');
        $driver = new FileCacheDriver();

        $this->assertFalse($driver->deleteItems($keys));

        $items = $driver->getItems($keys);

        foreach ($items as $key => $item) {
            /** @var $item CacheItem */
            $item->set('test');
            $driver->save($item);
        }

        $this->assertTrue($driver->clear());
    }

}
