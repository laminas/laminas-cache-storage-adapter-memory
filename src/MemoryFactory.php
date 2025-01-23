<?php

declare(strict_types=1);

namespace Laminas\Cache\Storage\Adapter;

use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function assert;

final class MemoryFactory
{
    /**
     * @phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public function __invoke(ContainerInterface $container, string $_, array|null $options = null): Memory
    {
        $clock = null;
        if ($container->has(ClockInterface::class)) {
            $clock = $container->get(ClockInterface::class);
            assert($clock instanceof ClockInterface);
        }

        Assert::nullOrIsMap($options);
        return new Memory(
            $options,
            $clock,
        );
    }
}
