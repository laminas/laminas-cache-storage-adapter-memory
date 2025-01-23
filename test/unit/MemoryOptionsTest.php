<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\Adapter\MemoryOptions;

/**
 * @template-extends AbstractAdapterOptionsTest<MemoryOptions>
 */
final class MemoryOptionsTest extends AbstractAdapterOptionsTest
{
    protected function createAdapterOptions(): AdapterOptions
    {
        return new MemoryOptions();
    }

    public function testSetMaxItems(): void
    {
        $options = new MemoryOptions();
        $options->setMaxItems(1);
        self::assertSame(1, $options->getMaxItems());
    }
}
