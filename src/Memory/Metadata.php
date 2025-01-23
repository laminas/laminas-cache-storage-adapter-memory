<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Adapter\Memory;

/**
 * @psalm-immutable
 * @psalm-api
 */
final class Metadata
{
    public function __construct(
        public readonly int $mtime,
        public readonly int $ctime,
    ) {
    }
}
