<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Adapter\Memory;

/**
 * @internal Used to persist cache values inside of memory adapter
 */
final class CacheItem
{
    /**
     * @param non-empty-string $key
     * @param non-negative-int $created
     * @param non-negative-int $lastModified
     * @param non-negative-int $expires
     * @param string[] $tags
     */
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly int $created,
        public int $lastModified,
        public int $expires,
        public array $tags = [],
    ) {
    }
}
