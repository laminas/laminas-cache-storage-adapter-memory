<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter\Memory;

use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\Adapter\Memory\AdapterPluginManagerDelegatorFactory;
use LaminasTest\Cache\Storage\Adapter\PluginManagerDelegatorFactoryTestTrait;
use PHPUnit\Framework\TestCase;

final class AdapterPluginManagerDelegatorFactoryTest extends TestCase
{
    use PluginManagerDelegatorFactoryTestTrait;

    /** @var AdapterPluginManagerDelegatorFactory */
    private $delegator;

    public function getCommonAdapterNamesProvider(): iterable
    {
        return [
            'lowercase'    => ['memory'],
            'ucfirst'      => ['Memory'],
            'class-string' => [Memory::class],
        ];
    }

    public function getDelegatorFactory(): callable
    {
        return $this->delegator;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->delegator = new AdapterPluginManagerDelegatorFactory();
    }
}
