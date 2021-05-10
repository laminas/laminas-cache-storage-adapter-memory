<?php

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Laminas\Cache\Psr\CacheItemPool\CacheException;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\StorageFactory;
use PHPUnit\Framework\TestCase;

class MemoryIntegrationTest extends TestCase
{
    /**
     * The memory adapter calculates the TTL on reading which violates PSR-6
     */
    public function testAdapterNotSupported()
    {
        $storage = StorageFactory::adapterFactory('memory');

        $this->expectException(CacheException::class);
        new CacheItemPoolDecorator($storage);
    }
}
