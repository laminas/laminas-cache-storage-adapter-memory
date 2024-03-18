<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Cache\Exception\InvalidArgumentException;

use function ini_set;

final class MemoryOptionsTest extends AbstractCommonAdapterTest
{
    public function setUp(): void
    {
        // instantiate memory adapter
        $this->options = new Cache\Storage\Adapter\MemoryOptions();
        $this->storage = new Cache\Storage\Adapter\Memory();
        $this->storage->setOptions($this->options);

        parent::setUp();
    }

    /**
     * @param string $iniValue
     * @param int $expectedMemoryLimit
     * @return void
     * @runInSeparateProcess
     * @dataProvider iniValuesDataSet
     */
    public function testMemoryLimitFromIni($iniValue, $expectedMemoryLimit)
    {
        ini_set('memory_limit', $iniValue);

        /**
         * @var Cache\Storage\Adapter\MemoryOptions $options
         */
        $options = $this->options;

        $this->assertEquals($expectedMemoryLimit, $options->getMemoryLimit());
    }

    /**
     * @param string $memoryLimit
     * @param string $expectedExceptionMessage
     * @dataProvider invalidMemoryLimitDataSet
     * @return void
     */
    public function testInvalidMemoryLimitThrowsException($memoryLimit, $expectedExceptionMessage)
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
     * @param string|int $memoryLimit
     * @param int $expectedMemoryLimit
     * @dataProvider validMemoryLimitDataSet
     * @return void
     */
    public function testValidMemoryLimit($memoryLimit, $expectedMemoryLimit)
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
    public static function invalidMemoryLimitDataSet()
    {
        return [
            ['invalid', "Invalid memory limit 'invalid'"],
        ];
    }

    /**
     * @return array<array{string|int, int}>
     */
    public static function validMemoryLimitDataSet()
    {
        return [
            ['M256', 256],
            ['G1024', 1024],
            [134217728, 134217728], // 128M
            [1073741824, 1073741824], // 1G
            [1024, 1024], // 1k
            ['128M', 134217728],
            ['1G', 1073741824],
            ['1k', 1024],
        ];
    }

    /**
     * @return array<array{string, int}>
     */
    public static function iniValuesDataSet()
    {
        return [
            ['128M', 67108864],
            ['1G', 536870912],
            ['32M', 16777216],
        ];
    }
}
