<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\Adapter\MemoryOptions;
use Lcobucci\Clock\FrozenClock;

use function assert;

/**
 * @template-extends AbstractCommonAdapterTest<MemoryOptions,Memory>
 */
final class MemoryTest extends AbstractCommonAdapterTest
{
    use ModifiableClockTrait;

    public function setUp(): void
    {
        // instantiate memory adapter
        $this->options = new MemoryOptions();
        $this->storage = new Memory($this->options, $this->getClock());

        parent::setUp();
    }

    public function testMetadataIsCreatedAndModifiedAccordingly(): void
    {
        $clock   = new FrozenClock(new DateTimeImmutable('2024-06-15 21:55:31', new DateTimeZone('Europe/Berlin')));
        $storage = new Memory(clock: $clock);

        $storage->setItem('foo', 'bar');
        $metadata = $storage->getMetadata('foo');
        self::assertNotNull($metadata);
        $now = $clock->now();
        self::assertSame($now->getTimestamp(), $metadata->ctime);
        self::assertSame($now->getTimestamp(), $metadata->mtime);

        $interval = DateInterval::createFromDateString('2 days');
        assert($interval !== false);

        $clock->setTo($now->add($interval));
        $storage->touchItem('foo');

        $future   = $clock->now();
        $metadata = $storage->getMetadata('foo');
        self::assertNotNull($metadata);
        self::assertSame($now->getTimestamp(), $metadata->ctime);
        self::assertSame($future->getTimestamp(), $metadata->mtime);
    }

    public function testWillRemoveFirstItemWhenPersistingMoreThanAllowedItems(): void
    {
        $storage = new Memory(new MemoryOptions(['max_items' => 3]));
        self::assertSame(['foo'], $storage->setItems(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'qoo', 'qoo' => 'ooq']));
    }

    public function testWillRemoveOldestItemWhenPersistingMoreThanAllowedItems(): void
    {
        $storage = new Memory(new MemoryOptions(['max_items' => 3]));
        self::assertTrue($storage->setItem('foo', 'bar'));
        self::assertTrue($storage->setItem('bar', 'baz'));
        self::assertTrue($storage->setItem('baz', 'qoo'));
        self::assertTrue($storage->setItem('qoo', 'ooq'));
        self::assertFalse($storage->hasItem('foo'));

        $storage->flush();

        $options = $storage->getOptions();
        $options->setTtl(1000);
        self::assertTrue($storage->setItem('foo', 'bar'));
        self::assertTrue($storage->setItem('bar', 'baz'));
        $options->setTtl(100);
        self::assertTrue($storage->setItem('baz', 'qoo'));
        $options->setTtl(1000);
        self::assertTrue($storage->setItem('qoo', 'ooq'));

        self::assertFalse($storage->hasItem('baz'));
    }
}
