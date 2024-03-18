<?php
declare(strict_types=1);

namespace LaminasTest\Cache\Storage\Adapter;

use Laminas\Cache;
use Laminas\Cache\Exception\InvalidArgumentException;

class MemoryOptionsTest extends AbstractCommonAdapterTest
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
     *
     * @runInSeparateProcess
     * @dataProvider iniValuesDataSet
     */
    public function testMemoryLimitFromIni($iniValue, $expectedMemoryLimit)
    {
        ini_set('memory_limit', $iniValue);

        $this->assertEquals($expectedMemoryLimit, $this->options->getMemoryLimit());
    }

    /**
     * @param string|null $memoryLimit
     * @param string $expectedExceptionMessage
     * @dataProvider invalidMemoryLimitDataSet
     */
    public function testInvalidMemoryLimitThrowsException($memoryLimit, $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->options->setMemoryLimit($memoryLimit);
    }

    /**
     * @param string|int $value
     * @param int $expectedMemoryLimit
     * @dataProvider validMemoryLimitDataSet
     */
    public function testValidMemoryLimit($memoryLimit, $expectedMemoryLimit)
    {
        $this->options->setMemoryLimit($memoryLimit);

        $this->assertEquals($expectedMemoryLimit, $this->options->getMemoryLimit());
    }

    /**
     * @return array
     */
    public static function invalidMemoryLimitDataSet()
    {
        return [
            ['invalid', "Invalid memory limit 'invalid'"],
        ];
    }

    /**
     * @return array
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
     * @return array
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