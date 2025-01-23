<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\Adapter\MemoryOptions;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\StorageInterface;
use LaminasTest\Cache\Storage\Adapter\AbstractCacheItemPoolIntegrationTest;
use LaminasTest\Cache\Storage\Adapter\ModifiableClockTrait;

/**
 * @uses FlushableInterface
 *
 * @template-extends AbstractCacheItemPoolIntegrationTest<MemoryOptions>
 */
final class MemoryIntegrationTest extends AbstractCacheItemPoolIntegrationTest
{
    use ModifiableClockTrait;

    private const TRANSIENT_STORAGE = 'Memory cache is not persistent and thus re-instantiating leads to data loss.';
    /** @var array<non-empty-string,non-empty-string> */
    protected array $skippedTests = [
        'testSaveWithoutExpire'         => self::TRANSIENT_STORAGE,
        'testDeferredSaveWithoutCommit' => self::TRANSIENT_STORAGE,
    ];

    protected function createStorage(): StorageInterface&FlushableInterface
    {
        return new Memory(clock: $this->getClock());
    }
}
