<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Adapter;

use Laminas\Cache\Exception;

final class MemoryOptions extends AdapterOptions
{
    public const UNLIMITED_ITEMS = 0;

    /** @var non-negative-int */
    protected int $maxItems = self::UNLIMITED_ITEMS;

    /**
     * @param non-negative-int $maxItems
     */
    public function setMaxItems(int $maxItems): self
    {
        /**
         * @psalm-suppress DocblockTypeContradiction Just because we expect non-negative-int does not prevent users
         *                                           from passing negative integers.
         */
        if ($maxItems < 0) {
            throw new Exception\InvalidArgumentException(
                'Provided `maxItems` option must be greater than or equal to 0',
            );
        }

        $this->maxItems = $maxItems;
        return $this;
    }

    /**
     * @return non-negative-int
     */
    public function getMaxItems(): int
    {
        return $this->maxItems;
    }
}
