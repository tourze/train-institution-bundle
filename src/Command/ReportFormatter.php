<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

/**
 * 报告格式化器
 *
 * 负责将报告数据格式化为不同的输出格式（JSON、CSV、HTML）
 */
final class ReportFormatter
{
    /**
     * 格式化JSON报告
     * @param array<string, mixed> $reportData
     */
    public function formatJsonReport(array $reportData): string
    {
        $result = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return false !== $result ? $result : '{}';
    }

    /**
     * 格式化CSV报告
     * @param array<string, mixed> $reportData
     */
    public function formatCsvReport(array $reportData, string $reportType): string
    {
        $csv = "报告类型,{$reportType}\n";
        $generatedAt = is_string($reportData['generated_at'] ?? null) ? $reportData['generated_at'] : 'Unknown';
        $csv .= "生成时间,{$generatedAt}\n";

        if (isset($reportData['institution']) && is_array($reportData['institution'])) {
            $institutionName = is_string($reportData['institution']['name'] ?? null) ? $reportData['institution']['name'] : 'Unknown';
            $institutionCode = is_string($reportData['institution']['code'] ?? null) ? $reportData['institution']['code'] : 'Unknown';
            $csv .= "机构名称,{$institutionName}\n";
            $csv .= "机构代码,{$institutionCode}\n";
        }

        return $csv;
    }

    /**
     * 格式化HTML报告
     * @param array<string, mixed> $reportData
     */
    public function formatHtmlReport(array $reportData, string $reportType): string
    {
        $html = '<html><head><title>培训机构报告</title></head><body>';
        $html .= "<h1>培训机构{$reportType}报告</h1>";
        $generatedAt = is_string($reportData['generated_at'] ?? null) ? $reportData['generated_at'] : 'Unknown';
        $html .= "<p>生成时间：{$generatedAt}</p>";

        if (isset($reportData['institution']) && is_array($reportData['institution'])) {
            $inst = $reportData['institution'];
            $html .= '<h2>机构信息</h2>';
            $institutionName = is_string($inst['name'] ?? null) ? $inst['name'] : 'Unknown';
            $institutionCode = is_string($inst['code'] ?? null) ? $inst['code'] : 'Unknown';
            $html .= "<p>机构名称：{$institutionName}</p>";
            $html .= "<p>机构代码：{$institutionCode}</p>";
        }

        $html .= '</body></html>';

        return $html;
    }
}
