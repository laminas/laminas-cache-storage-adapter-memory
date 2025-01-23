<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Adapter\Memory;

use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Cache\Storage\Adapter\MemoryFactory;
use Laminas\Cache\Storage\AdapterPluginManager;
use Psr\Container\ContainerInterface;

use function assert;

final class AdapterPluginManagerDelegatorFactory
{
    /**
     * @phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public function __invoke(ContainerInterface $_, string $__, callable $callback): AdapterPluginManager
    {
        $pluginManager = $callback();
        assert($pluginManager instanceof AdapterPluginManager);

        $pluginManager->configure([
            'factories' => [
                Memory::class => MemoryFactory::class,
            ],
            'aliases'   => [
                'memory' => Memory::class,
                'Memory' => Memory::class,
            ],
        ]);

        return $pluginManager;
    }
}
