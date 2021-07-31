<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Cache\Exception\OutOfSpaceException;

use function memory_get_usage;

final class MemoryTest extends AbstractCommonAdapterTest
{
    public function setUp(): void
    {
        // instantiate memory adapter
        $this->options = new Cache\Storage\Adapter\MemoryOptions();
        $this->storage = new Cache\Storage\Adapter\Memory();
        $this->storage->setOptions($this->options);

        parent::setUp();
    }

    public function testThrowOutOfSpaceException()
    {
        $this->options->setMemoryLimit(memory_get_usage(true) - 8);

        $this->expectException(OutOfSpaceException::class);
        $this->storage->addItem('test', 'test');
    }
}
