<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\Adapter\AdapterOptions;

use function ini_set;

final class MemoryOptionsTest extends AbstractAdapterOptionsTest
{
    /**
     * @runInSeparateProcess
     * @dataProvider iniValuesDataSet
     */
    public function testMemoryLimitFromIni(string $iniValue, int $expectedMemoryLimit): void
    {
        ini_set('memory_limit', $iniValue);

        $options = new Cache\Storage\Adapter\MemoryOptions();

        $this->assertEquals($expectedMemoryLimit, $options->getMemoryLimit());
    }

    /**
     * @dataProvider invalidMemoryLimitDataSet
     */
    public function testInvalidMemoryLimitThrowsException(string $memoryLimit, string $expectedExceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        /**
         * @var Cache\Storage\Adapter\MemoryOptions $options
         */
        $options = $this->options;

        $options->setMemoryLimit($memoryLimit);
    }

    /**
     * @dataProvider validMemoryLimitDataSet
     */
    public function testValidMemoryLimit(string|int $memoryLimit, int $expectedMemoryLimit): void
    {
        /**
         * @var Cache\Storage\Adapter\MemoryOptions $options
         */
        $options = $this->options;

        $options->setMemoryLimit($memoryLimit);

        $this->assertEquals($expectedMemoryLimit, $options->getMemoryLimit());
    }

    /**
     * @return array<array{string, string}>
     */
    public static function invalidMemoryLimitDataSet(): array
    {
        return [
            ['invalid', "Invalid memory limit 'invalid'"],
            ['M256', "Invalid memory limit 'M256'"],
            ['G1024', "Invalid memory limit 'G1024'"],
        ];
    }

    /**
     * @return array<array{string|int, int}>
     */
    public static function validMemoryLimitDataSet(): array
    {
        return [
            [134217728, 134217728], // 128M
            [1073741824, 1073741824], // 1G
            [1024, 1024], // 1k
            ['128', 128],
            ['134217728', 134217728],
            ['128M', 134217728],
            ['1G', 1073741824],
            ['1k', 1024],
        ];
    }

    /**
     * @return array<array{string, int}>
     */
    public static function iniValuesDataSet(): array
    {
        return [
            ['128M', 67108864],
            ['1G', 536870912],
            ['32M', 16777216],
        ];
    }

    protected function createAdapterOptions(): AdapterOptions
    {
        return new Cache\Storage\Adapter\MemoryOptions();
    }
}
