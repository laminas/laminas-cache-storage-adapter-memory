<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Cache\Exception\OutOfSpaceException;

use function memory_get_usage;
use function mt_rand;
use function sha1;

/**
 * @group      Laminas_Cache
 * @covers Laminas\Cache\Storage\Adapter\Memory<extended>
 */
class MemoryTest extends AbstractCommonAdapterTest
{
    public function setUp(): void
    {
        // instantiate memory adapter
        $this->options = new Cache\Storage\Adapter\MemoryOptions();
        $this->storage = new Cache\Storage\Adapter\Memory();
        $this->storage->setOptions($this->options);

        parent::setUp();
    }

    public function getCommonAdapterNamesProvider(): array
    {
        return [
            ['memory'],
            ['Memory'],
        ];
    }

    public function testThrowOutOfSpaceException()
    {
        $this->options->setMemoryLimit(memory_get_usage(true) - 8);

        $this->expectException(OutOfSpaceException::class);
        $this->storage->addItem('test', 'test');
    }

    public function testReclaimMemory()
    {
        $this->options->setMemoryLimit(memory_get_usage(true) + 200);

        try {
            for ($i = 0; $i <= 100000; $i++) {
                $this->storage->addItem('item' . $i, sha1((string) mt_rand()));
            }

            self::fail('filling the cache with test data to reach the memory limit failed');
        } catch (OutOfSpaceException $ignore) {
        }

        $this->storage->flush();
        $this->storage->addItem('item' . $i, sha1((string) mt_rand()));
    }
}
