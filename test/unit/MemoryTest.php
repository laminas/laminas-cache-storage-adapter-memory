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
        $this->options->setMemoryLimit($this->getUsedMemory() - 1);

        $this->expectException(OutOfSpaceException::class);
        $this->storage->addItem('test', 'test');
    }

    public function testReclaimMemory()
    {
        $outOfSpaceExceptionThrown = false;
        try {
            $startMemoryAllocatedToPhp = $this->getAllocatedMemory();

            for ($i = 0; $i < 100000; ++$i) {
                $this->storage->addItem('item' . $i, sha1((string) mt_rand()));
            }

            $finishMemoryAllocatedToPhp = $this->getUsedMemory();

            $this->assertGreaterThan($startMemoryAllocatedToPhp, $finishMemoryAllocatedToPhp);

            $this->storage->flush();

            $flushedMemoryAllocatedToPhp = $this->getUsedMemory();

            self::assertLessThan($finishMemoryAllocatedToPhp, $flushedMemoryAllocatedToPhp);
        } catch (OutOfSpaceException $ignore) {
            $outOfSpaceExceptionThrown = true;
        }

        self::assertFalse($outOfSpaceExceptionThrown, 'OutOfSpaceException was thrown');
    }

    public function testReclaimMemoryPr7()
    {
        $this->options->setMemoryLimit($this->getAllocatedMemory() + 200);

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

    public function testReclaimMemoryAfterOutOfSpaceExceptionThrown()
    {
        $startMemoryAllocatedToPhp = $this->getAllocatedMemory();
        $this->options->setMemoryLimit($startMemoryAllocatedToPhp);
        $outOfSpaceExceptionThrown = false;
        try {
            for ($i = 0; $i < 100000; ++$i) {
                $this->storage->addItem('item' . $i, sha1((string) mt_rand()));
            }
        } catch (OutOfSpaceException $ignore) {
            $outOfSpaceExceptionThrown = true;
        }

        self::assertTrue($outOfSpaceExceptionThrown, 'OutOfSpaceException was not thrown');

        $finishMemoryAllocatedToPhp = $this->getUsedMemory();

        $this->assertGreaterThan($startMemoryAllocatedToPhp, $finishMemoryAllocatedToPhp);

        $this->storage->flush();

        $flushedMemoryAllocatedToPhp = $this->getUsedMemory();

        self::assertLessThan($finishMemoryAllocatedToPhp, $flushedMemoryAllocatedToPhp);

        $this->storage->addItem('item1', sha1((string) mt_rand()));

        $this->storage->addItem('item2', $this->storage->getItem('item1'));

        self::assertSame(
            $this->storage->getItem('item1'),
            $this->storage->getItem('item2')
        );
    }

    /** @return int */
    private function getUsedMemory()
    {
        return memory_get_usage(false);
    }

    /** @return int */
    private function getAllocatedMemory()
    {
        return memory_get_usage(true);
    }
}
