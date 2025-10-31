<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Service\FacilityDataExtractor;

/**
 * @internal
 */
#[CoversClass(FacilityDataExtractor::class)]
final class FacilityDataExtractorTest extends TestCase
{
    private FacilityDataExtractor $extractor;

    /**
     * @return iterable<string, array{array<string, mixed>, string, string, string}>
     */
    public static function provideStringExtractionData(): iterable
    {
        yield '提取存在的字符串值' => [
            ['name' => '测试设施'],
            'name',
            '',
            '测试设施',
        ];

        yield '提取不存在的键返回默认值' => [
            ['name' => '测试设施'],
            'missing_key',
            'default',
            'default',
        ];

        yield '提取整数值转换为字符串' => [
            ['count' => 123],
            'count',
            '',
            '123',
        ];

        yield '提取浮点数值转换为字符串' => [
            ['price' => 99.99],
            'price',
            '',
            '99.99',
        ];

        yield '提取布尔值true转换为字符串' => [
            ['active' => true],
            'active',
            '',
            '1',
        ];

        yield '提取布尔值false转换为字符串' => [
            ['active' => false],
            'active',
            '',
            '',
        ];

        yield '提取数组返回默认值' => [
            ['data' => ['nested' => 'value']],
            'data',
            'default',
            'default',
        ];

        yield '提取对象返回默认值' => [
            ['obj' => new \stdClass()],
            'obj',
            'default',
            'default',
        ];

        yield '提取null值返回默认值' => [
            ['value' => null],
            'value',
            'default',
            'default',
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, float, float}>
     */
    public static function provideFloatExtractionData(): iterable
    {
        yield '提取存在的浮点值' => [
            ['price' => 99.99],
            'price',
            0.0,
            99.99,
        ];

        yield '提取不存在的键返回默认值' => [
            ['price' => 99.99],
            'missing_key',
            10.5,
            10.5,
        ];

        yield '提取整数值转换为浮点数' => [
            ['count' => 100],
            'count',
            0.0,
            100.0,
        ];

        yield '提取数字字符串转换为浮点数' => [
            ['value' => '123.45'],
            'value',
            0.0,
            123.45,
        ];

        yield '提取整数字符串转换为浮点数' => [
            ['value' => '100'],
            'value',
            0.0,
            100.0,
        ];

        yield '提取非数字字符串返回默认值' => [
            ['value' => 'not-a-number'],
            'value',
            99.9,
            99.9,
        ];

        yield '提取数组返回默认值' => [
            ['data' => [1, 2, 3]],
            'data',
            5.5,
            5.5,
        ];

        yield '提取null值返回默认值' => [
            ['value' => null],
            'value',
            7.7,
            7.7,
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, int, int}>
     */
    public static function provideIntExtractionData(): iterable
    {
        yield '提取存在的整数值' => [
            ['count' => 100],
            'count',
            0,
            100,
        ];

        yield '提取不存在的键返回默认值' => [
            ['count' => 100],
            'missing_key',
            50,
            50,
        ];

        yield '提取浮点数转换为整数' => [
            ['value' => 99.99],
            'value',
            0,
            99,
        ];

        yield '提取整数字符串转换为整数' => [
            ['value' => '123'],
            'value',
            0,
            123,
        ];

        yield '提取浮点数字符串转换为整数' => [
            ['value' => '123.45'],
            'value',
            0,
            123,
        ];

        yield '提取非数字字符串返回默认值' => [
            ['value' => 'not-a-number'],
            'value',
            99,
            99,
        ];

        yield '提取数组返回默认值' => [
            ['data' => [1, 2, 3]],
            'data',
            5,
            5,
        ];

        yield '提取null值返回默认值' => [
            ['value' => null],
            'value',
            7,
            7,
        ];

        yield '提取负整数' => [
            ['value' => -50],
            'value',
            0,
            -50,
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, array<int|string, mixed>}>
     */
    public static function provideArrayExtractionData(): iterable
    {
        yield '提取存在的数组值' => [
            ['items' => [1, 2, 3]],
            'items',
            [1, 2, 3],
        ];

        yield '提取不存在的键返回空数组' => [
            ['items' => [1, 2, 3]],
            'missing_key',
            [],
        ];

        yield '提取关联数组' => [
            ['data' => ['key1' => 'value1', 'key2' => 'value2']],
            'data',
            ['key1' => 'value1', 'key2' => 'value2'],
        ];

        yield '提取空数组' => [
            ['items' => []],
            'items',
            [],
        ];

        yield '提取字符串值返回空数组' => [
            ['value' => 'string'],
            'value',
            [],
        ];

        yield '提取整数值返回空数组' => [
            ['value' => 123],
            'value',
            [],
        ];

        yield '提取对象返回空数组' => [
            ['obj' => new \stdClass()],
            'obj',
            [],
        ];

        yield '提取null值返回空数组' => [
            ['value' => null],
            'value',
            [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('provideStringExtractionData')]
    public function testExtractString(array $data, string $key, string $default, string $expected): void
    {
        $result = $this->extractor->extractString($data, $key, $default);

        self::assertSame($expected, $result);
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('provideFloatExtractionData')]
    public function testExtractFloat(array $data, string $key, float $default, float $expected): void
    {
        $result = $this->extractor->extractFloat($data, $key, $default);

        self::assertSame($expected, $result);
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('provideIntExtractionData')]
    public function testExtractInt(array $data, string $key, int $default, int $expected): void
    {
        $result = $this->extractor->extractInt($data, $key, $default);

        self::assertSame($expected, $result);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int|string, mixed> $expected
     */
    #[DataProvider('provideArrayExtractionData')]
    public function testExtractArray(array $data, string $key, array $expected): void
    {
        $result = $this->extractor->extractArray($data, $key);

        self::assertSame($expected, $result);
    }

    public function testExtractStringWithoutDefault(): void
    {
        $data = ['name' => '测试'];
        $result = $this->extractor->extractString($data, 'name');

        self::assertSame('测试', $result);
    }

    public function testExtractStringMissingKeyWithoutDefault(): void
    {
        $data = ['name' => '测试'];
        $result = $this->extractor->extractString($data, 'missing');

        self::assertSame('', $result);
    }

    public function testExtractFloatWithoutDefault(): void
    {
        $data = ['value' => 99.99];
        $result = $this->extractor->extractFloat($data, 'value');

        self::assertSame(99.99, $result);
    }

    public function testExtractFloatMissingKeyWithoutDefault(): void
    {
        $data = ['value' => 99.99];
        $result = $this->extractor->extractFloat($data, 'missing');

        self::assertSame(0.0, $result);
    }

    public function testExtractIntWithoutDefault(): void
    {
        $data = ['count' => 100];
        $result = $this->extractor->extractInt($data, 'count');

        self::assertSame(100, $result);
    }

    public function testExtractIntMissingKeyWithoutDefault(): void
    {
        $data = ['count' => 100];
        $result = $this->extractor->extractInt($data, 'missing');

        self::assertSame(0, $result);
    }

    public function testExtractArrayMissingKey(): void
    {
        $data = ['items' => [1, 2, 3]];
        $result = $this->extractor->extractArray($data, 'missing');

        self::assertSame([], $result);
    }

    protected function setUp(): void
    {
        $this->extractor = new FacilityDataExtractor();
    }
}
