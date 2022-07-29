<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\SimpleCache;

use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Storage\Adapter\AbstractSimpleCacheIntegrationTest;

class MemoryIntegrationTest extends AbstractSimpleCacheIntegrationTest
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

    protected function createStorage(): StorageInterface
    {
        return new Memory();
    }
}
