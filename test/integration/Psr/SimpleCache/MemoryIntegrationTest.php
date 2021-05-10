<?php

namespace LaminasTest\Cache\Psr\SimpleCache;

use Cache\IntegrationTests\SimpleCacheTest;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\StorageFactory;

class MemoryIntegrationTest extends SimpleCacheTest
{
    public function setUp(): void
    {
        $this->skippedTests['testSetTtl']         = 'Memory adapter does not honor TTL';
        $this->skippedTests['testSetMultipleTtl'] = 'Memory adapter does not honor TTL';

        $this->skippedTests['testObjectDoesNotChangeInCache'] =
            'Memory adapter stores objects in memory; so change in references is possible';

        $this->skippedTests['testBasicUsageWithLongKey'] = 'SimpleCacheDecorator requires keys to be <= 64 chars';

        parent::setUp();
    }

    public function createSimpleCache(): SimpleCacheDecorator
    {
        $storage = StorageFactory::adapterFactory('memory');
        return new SimpleCacheDecorator($storage);
    }
}
