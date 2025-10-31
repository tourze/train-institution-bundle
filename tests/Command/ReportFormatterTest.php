<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainInstitutionBundle\Command\ReportFormatter;

/**
 * @internal
 */
#[CoversClass(ReportFormatter::class)]
#[RunTestsInSeparateProcesses]
final class ReportFormatterTest extends AbstractIntegrationTestCase
{
    private ReportFormatter $formatter;

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function provideReportDataForCsv(): iterable
    {
        yield '完整报告数据' => [
            [
                'generated_at' => '2024-01-01 10:00:00',
                'institution' => [
                    'name' => '测试机构',
                    'code' => 'TEST001',
                ],
            ],
            '设施统计',
            '机构名称,测试机构',
        ];

        yield '缺少机构信息' => [
            [
                'generated_at' => '2024-01-01 10:00:00',
            ],
            '设施统计',
            '生成时间,2024-01-01 10:00:00',
        ];

        yield '缺少生成时间' => [
            [
                'institution' => [
                    'name' => '测试机构',
                    'code' => 'TEST001',
                ],
            ],
            '设施统计',
            '生成时间,Unknown',
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function provideReportDataForHtml(): iterable
    {
        yield '完整报告数据' => [
            [
                'generated_at' => '2024-01-01 10:00:00',
                'institution' => [
                    'name' => '测试机构',
                    'code' => 'TEST001',
                ],
            ],
            '设施统计',
            '机构名称：测试机构',
        ];

        yield '缺少机构信息' => [
            [
                'generated_at' => '2024-01-01 10:00:00',
            ],
            '设施统计',
            '生成时间：2024-01-01 10:00:00',
        ];

        yield '缺少生成时间' => [
            [
                'institution' => [
                    'name' => '测试机构',
                    'code' => 'TEST001',
                ],
            ],
            '设施统计',
            '生成时间：Unknown',
        ];
    }

    public function testFormatJsonReport(): void
    {
        $reportData = [
            'generated_at' => '2024-01-01 10:00:00',
            'institution' => [
                'name' => '测试机构',
                'code' => 'TEST001',
            ],
            'summary' => [
                'total' => 10,
            ],
        ];

        $result = $this->formatter->formatJsonReport($reportData);

        self::assertIsString($result);
        self::assertJson($result);

        $decoded = json_decode($result, true);
        self::assertIsArray($decoded);

        self::assertArrayHasKey('generated_at', $decoded);
        self::assertArrayHasKey('institution', $decoded);
        self::assertIsArray($decoded['institution']);

        self::assertSame('2024-01-01 10:00:00', $decoded['generated_at']);
        self::assertSame('测试机构', $decoded['institution']['name']);
        self::assertSame('TEST001', $decoded['institution']['code']);
    }

    public function testFormatJsonReportWithEmptyArray(): void
    {
        $result = $this->formatter->formatJsonReport([]);

        self::assertIsString($result);
        self::assertJson($result);
        self::assertSame('{}', [] === json_decode($result, true) ? '{}' : 'not empty');
    }

    /**
     * @param array<string, mixed> $reportData
     */
    #[DataProvider('provideReportDataForCsv')]
    public function testFormatCsvReport(array $reportData, string $reportType, string $expectedContent): void
    {
        $result = $this->formatter->formatCsvReport($reportData, $reportType);

        self::assertIsString($result);
        self::assertStringContainsString($expectedContent, $result);
        self::assertStringContainsString("报告类型,{$reportType}", $result);
    }

    /**
     * @param array<string, mixed> $reportData
     */
    #[DataProvider('provideReportDataForHtml')]
    public function testFormatHtmlReport(array $reportData, string $reportType, string $expectedContent): void
    {
        $result = $this->formatter->formatHtmlReport($reportData, $reportType);

        self::assertIsString($result);
        self::assertStringContainsString('<html>', $result);
        self::assertStringContainsString('</html>', $result);
        self::assertStringContainsString($expectedContent, $result);
        self::assertStringContainsString("培训机构{$reportType}报告", $result);
    }

    public function testFormatHtmlReportStructure(): void
    {
        $reportData = [
            'generated_at' => '2024-01-01 10:00:00',
            'institution' => [
                'name' => '测试机构',
                'code' => 'TEST001',
            ],
        ];

        $result = $this->formatter->formatHtmlReport($reportData, '设施统计');

        // 验证HTML结构完整性
        self::assertStringContainsString('<head>', $result);
        self::assertStringContainsString('<title>培训机构报告</title>', $result);
        self::assertStringContainsString('<body>', $result);
        self::assertStringContainsString('</body>', $result);
        self::assertStringContainsString('<h1>', $result);
        self::assertStringContainsString('<h2>机构信息</h2>', $result);
        self::assertStringContainsString('<p>', $result);
    }

    public function testFormatCsvReportWithInvalidDataTypes(): void
    {
        $reportData = [
            'generated_at' => 123, // 非字符串类型
            'institution' => [
                'name' => 456, // 非字符串类型
                'code' => null, // null值
            ],
        ];

        $result = $this->formatter->formatCsvReport($reportData, '设施统计');

        self::assertIsString($result);
        self::assertStringContainsString('生成时间,Unknown', $result);
        self::assertStringContainsString('机构名称,Unknown', $result);
        self::assertStringContainsString('机构代码,Unknown', $result);
    }

    public function testFormatHtmlReportWithInvalidDataTypes(): void
    {
        $reportData = [
            'generated_at' => 123, // 非字符串类型
            'institution' => [
                'name' => 456, // 非字符串类型
                'code' => null, // null值
            ],
        ];

        $result = $this->formatter->formatHtmlReport($reportData, '设施统计');

        self::assertIsString($result);
        self::assertStringContainsString('生成时间：Unknown', $result);
        self::assertStringContainsString('机构名称：Unknown', $result);
        self::assertStringContainsString('机构代码：Unknown', $result);
    }

    protected function onSetUp(): void
    {
        $formatter = self::getService(ReportFormatter::class);
        self::assertInstanceOf(ReportFormatter::class, $formatter);
        $this->formatter = $formatter;
    }
}
