<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Adapter;

use DateInterval;
use DateTimeZone;
use Laminas\Cache\Exception;
use Laminas\Cache\Storage\AbstractMetadataCapableAdapter;
use Laminas\Cache\Storage\Adapter\Memory\CacheItem;
use Laminas\Cache\Storage\Adapter\Memory\Clock;
use Laminas\Cache\Storage\Adapter\Memory\Metadata;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\ClearByNamespaceInterface;
use Laminas\Cache\Storage\ClearByPrefixInterface;
use Laminas\Cache\Storage\ClearExpiredInterface;
use Laminas\Cache\Storage\FlushableInterface;
use Laminas\Cache\Storage\IterableInterface;
use Laminas\Cache\Storage\TaggableInterface;
use Psr\Clock\ClockInterface;

use function array_diff;
use function array_keys;
use function array_reverse;
use function assert;
use function count;
use function date_default_timezone_get;
use function round;
use function sprintf;
use function str_starts_with;

use const PHP_INT_MAX;
use const PHP_ROUND_HALF_UP;

/**
 * @template-extends AbstractMetadataCapableAdapter<MemoryOptions, Metadata>
 * @template-implements IterableInterface<non-empty-string,mixed>
 */
final class Memory extends AbstractMetadataCapableAdapter implements
    ClearByPrefixInterface,
    ClearByNamespaceInterface,
    ClearExpiredInterface,
    FlushableInterface,
    IterableInterface,
    TaggableInterface
{
    /** @var array<string,array<non-empty-string|int,CacheItem>> */
    private array $data = [];
    private readonly ClockInterface $clock;

    /**
     * @param iterable<string,mixed>|MemoryOptions|null $options
     */
    public function __construct(iterable|MemoryOptions|null $options = null, ClockInterface|null $clock = null)
    {
        parent::__construct($options);
        $this->clock = $clock ?? new Clock(new DateTimeZone(date_default_timezone_get()));
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(iterable|AdapterOptions|MemoryOptions $options): self
    {
        if (! $options instanceof MemoryOptions) {
            $options = new MemoryOptions($options);
        }

        parent::setOptions($options);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): MemoryOptions
    {
        if ($this->options === null) {
            $this->setOptions(new MemoryOptions());
        }

        assert($this->options instanceof MemoryOptions);
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): KeyListIterator
    {
        $ns   = $this->getOptions()->getNamespace();
        $keys = [];

        if (isset($this->data[$ns])) {
            foreach (array_keys($this->data[$ns]) as $key) {
                $key = (string) $key;
                if ($this->internalHasItem($key)) {
                    $keys[] = $key;
                }
            }
        }

        return new KeyListIterator($this, $keys);
    }

    protected function internalSetItems(array $normalizedKeyValuePairs): array
    {
        $maxItems                  = $this->getOptions()->getMaxItems();
        $keysWhichWereNotPersisted = [];

        if ($maxItems !== MemoryOptions::UNLIMITED_ITEMS && count($normalizedKeyValuePairs) > $maxItems) {
            [$normalizedKeyValuePairs, $keysWhichWereNotPersisted] = $this->sliceOldestItems(
                $normalizedKeyValuePairs,
                $maxItems,
            );
        }

        foreach ($normalizedKeyValuePairs as $key => $value) {
            $succeeded = $this->internalSetItem((string) $key, $value);
            if ($succeeded === false) {
                $keysWhichWereNotPersisted[] = $key;
            }
        }

        return $keysWhichWereNotPersisted;
    }

    /**
     * {@inheritDoc}
     */
    public function flush(): bool
    {
        $this->data = [];
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clearExpired(): bool
    {
        $ns = $this->getOptions()->getNamespace();
        if (! isset($this->data[$ns])) {
            return true;
        }

        $items = $this->data[$ns];
        foreach (array_keys($items) as $key) {
            if ($this->clock->now()->getTimestamp() >= $items[$key]->expires) {
                unset($items[$key]);
            }
        }

        $this->data[$ns] = $items;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clearByNamespace(string $namespace): bool
    {
        /**
         * @psalm-suppress TypeDoesNotContainType Even tho, we expect the prefix is being passed as a non-empty-string,
         *                                        having this check around ensures that only those with a given prefix
         *                                        are being dropped. Empty prefix would actually clear all.
         */
        if ($namespace === '') {
            throw new Exception\InvalidArgumentException('No namespace given');
        }

        unset($this->data[$namespace]);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clearByPrefix(string $prefix): bool
    {
        /**
         * @psalm-suppress TypeDoesNotContainType Even tho, we expect the prefix is being passed as a non-empty-string,
         *                                        having this check around ensures that only those with a given prefix
         *                                        are being dropped. Empty prefix would actually clear all.
         */
        if ($prefix === '') {
            throw new Exception\InvalidArgumentException('No prefix given');
        }

        $ns = $this->getOptions()->getNamespace();
        if (! isset($this->data[$ns])) {
            return true;
        }

        $data = $this->data[$ns];
        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, $prefix)) {
                unset($data[$key]);
            }
        }

        $this->data[$ns] = $data;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setTags(string $key, array $tags): bool
    {
        $ns = $this->getOptions()->getNamespace();

        $cacheItem = $this->data[$ns][$key] ?? null;
        if ($cacheItem === null) {
            return false;
        }

        $this->data[$ns][$key]->tags = $tags;
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getTags(string $key): array|false
    {
        $cacheItem = $this->getCacheItem($key);
        if ($cacheItem === null) {
            return false;
        }

        return $cacheItem->tags;
    }

    /**
     * {@inheritDoc}
     */
    public function clearByTags(array $tags, bool $disjunction = false): bool
    {
        $ns = $this->getOptions()->getNamespace();
        if (! isset($this->data[$ns])) {
            return true;
        }

        $tagCount = count($tags);
        $data     = $this->data[$ns];
        foreach ($data as $key => $item) {
            if ($item->tags !== []) {
                $diff = array_diff($tags, $item->tags);
                if (($disjunction && count($diff) < $tagCount) || (! $disjunction && ! $diff)) {
                    unset($data[$key]);
                }
            }
        }

        $this->data[$ns] = $data;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetItem(
        string $normalizedKey,
        bool|null &$success = null,
        mixed &$casToken = null
    ): mixed {
        $item    = $this->getCacheItem($normalizedKey);
        $success = $item !== null;
        if ($item === null) {
            return null;
        }

        $casToken = $item->value;
        return $casToken;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalHasItem(string $normalizedKey): bool
    {
        return $this->getCacheItem($normalizedKey) !== null;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetMetadata(string $normalizedKey): Metadata|null
    {
        $cacheItem = $this->getCacheItem($normalizedKey);
        if ($cacheItem === null) {
            return null;
        }

        return new Metadata(
            mtime: $cacheItem->lastModified,
            ctime: $cacheItem->created,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function internalSetItem(string $normalizedKey, mixed $value): bool
    {
        $options = $this->getOptions();
        $now     = $this->clock->now()->getTimestamp();
        assert($now >= 0);

        $expires = $this->calculateExpireTimestampBasedOnTtl($options->getTtl());
        return $this->persistCacheItem($normalizedKey, new CacheItem(
            $normalizedKey,
            $value,
            $now,
            $now,
            $expires
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function internalAddItem(string $normalizedKey, mixed $value): bool
    {
        $cacheItem = $this->getCacheItem($normalizedKey);
        if ($cacheItem !== null) {
            return false;
        }

        $this->internalSetItem($normalizedKey, $value);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalReplaceItem(string $normalizedKey, mixed $value): bool
    {
        $cacheItem = $this->getCacheItem($normalizedKey);
        if ($cacheItem === null) {
            return false;
        }

        /**
         * NOTE: We are explicitly implementing this as we do not want to have available space check during replacement
         */
        $now = $this->clock->now()->getTimestamp();
        assert($now >= 0);
        $expires = $this->calculateExpireTimestampBasedOnTtl($this->getOptions()->getTtl());

        return $this->persistCacheItem($normalizedKey, new CacheItem(
            $cacheItem->key,
            $value,
            $cacheItem->created,
            $now,
            $expires
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function internalTouchItem(string $normalizedKey): bool
    {
        $cacheItem = $this->getCacheItem($normalizedKey);
        if ($cacheItem === null) {
            return false;
        }

        $now = $this->clock->now()->getTimestamp();
        assert($now >= 0);
        $expires = $this->calculateExpireTimestampBasedOnTtl($this->getOptions()->getTtl());

        return $this->persistCacheItem(
            $normalizedKey,
            new CacheItem(
                $cacheItem->key,
                $cacheItem->value,
                $cacheItem->created,
                $now,
                $expires,
                $cacheItem->tags
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function internalRemoveItem(string $normalizedKey): bool
    {
        $cacheItem = $this->getCacheItem($normalizedKey);
        if ($cacheItem === null) {
            return false;
        }

        $ns = $this->getOptions()->getNamespace();
        unset($this->data[$ns][$normalizedKey]);

        // remove empty namespace
        if (! $this->data[$ns]) {
            unset($this->data[$ns]);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetCapabilities(): Capabilities
    {
        return $this->capabilities ??= new Capabilities(
            maxKeyLength: Capabilities::UNLIMITED_KEY_LENGTH,
            ttlSupported: true,
            namespaceIsPrefix: false,
            supportedDataTypes: [
                'NULL'     => true,
                'boolean'  => true,
                'integer'  => true,
                'double'   => true,
                'string'   => true,
                'array'    => true,
                'object'   => true,
                'resource' => true,
            ],
            ttlPrecision: 1,
        );
    }

    private function getCacheItem(string $key): CacheItem|null
    {
        $namespace = $this->getOptions()->getNamespace();
        $cacheItem = $this->data[$namespace][$key] ?? null;
        if ($cacheItem === null) {
            return null;
        }

        if ($this->isCacheItemExpired($cacheItem)) {
            unset($this->data[$namespace][$key]);
            return null;
        }

        return $cacheItem;
    }

    /**
     * @param non-empty-string $key
     */
    private function persistCacheItem(string $key, CacheItem $item): bool
    {
        $options   = $this->getOptions();
        $namespace = $options->getNamespace();
        unset($this->data[$namespace][$key]);

        $maxItems = $options->getMaxItems();
        $this->free($namespace, $maxItems);

        $this->data[$namespace][$key] = $item;

        return true;
    }

    /**
     * @return non-negative-int
     */
    private function calculateExpireTimestampBasedOnTtl(float|int $ttl): int
    {
        if ($ttl < 1) {
            return PHP_INT_MAX;
        }

        $ttl      = (int) round($ttl, PHP_ROUND_HALF_UP);
        $interval = DateInterval::createFromDateString(sprintf('%d seconds', $ttl));
        if ($interval === false) {
            throw new Exception\InvalidArgumentException('Configured TTL cannot be converted to seconds.');
        }

        $timestamp = $this->clock
            ->now()
            ->add($interval)
            ->getTimestamp();

        assert($timestamp >= 0);
        return $timestamp;
    }

    private function isCacheItemExpired(CacheItem $cacheItem): bool
    {
        return $this->clock->now()->getTimestamp() >= $cacheItem->expires;
    }

    private function free(string $namespace, int $maxItems): void
    {
        if ($maxItems === MemoryOptions::UNLIMITED_ITEMS) {
            return;
        }

        if (count($this->data[$namespace] ?? []) < $maxItems) {
            return;
        }

        $oldest = null;
        foreach ($this->data[$namespace] ?? [] as $key => $existingItem) {
            if ($this->isCacheItemExpired($existingItem)) {
                unset($this->data[$namespace][$key]);
                break;
            }

            if ($oldest === null) {
                $oldest = $existingItem;
            }

            if ($existingItem->expires < $oldest->expires) {
                $oldest = $existingItem;
            }
        }

        if ($oldest === null) {
            return;
        }

        unset($this->data[$namespace][$oldest->key]);
    }

    /**
     * @param non-empty-array<non-empty-string|int,mixed> $normalizedKeyValuePairs
     * @param positive-int $maxItems
     * @return array{non-empty-array<non-empty-string|int,mixed>, list<non-empty-string|int>}
     */
    private function sliceOldestItems(array $normalizedKeyValuePairs, int $maxItems): array
    {
        $keysWhichAreNotPersisted = [];
        $maxItemsExceeded         = false;
        $items                    = 0;

        foreach (array_keys(array_reverse($normalizedKeyValuePairs, true)) as $key) {
            if ($maxItemsExceeded === true) {
                $keysWhichAreNotPersisted[] = $key;
                unset($normalizedKeyValuePairs[$key]);
                continue;
            }

            $items++;
            if ($items < $maxItems) {
                continue;
            }

            $maxItemsExceeded = true;
        }

        assert($normalizedKeyValuePairs !== []);
        return [$normalizedKeyValuePairs, $keysWhichAreNotPersisted];
    }
}
