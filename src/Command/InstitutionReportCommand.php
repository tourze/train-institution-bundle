<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;
use Tourze\TrainInstitutionBundle\Service\FacilityService;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * 机构报告生成命令
 *
 * 生成培训机构的综合报告，包括机构状态、资质情况、设施状况等
 * 建议每月执行一次（cron: 0 6 1 * *）
 */
#[AsCommand(name: self::NAME, description: '生成培训机构综合报告')]
final class InstitutionReportCommand extends Command
{
    public const NAME = 'institution:report:generate';

    public function __construct(
        private readonly InstitutionService $institutionService,
        private readonly QualificationService $qualificationService,
        private readonly FacilityService $facilityService,
        private readonly ChangeRecordService $changeRecordService,
        private readonly ReportFormatter $reportFormatter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'institution-id',
                'i',
                InputOption::VALUE_OPTIONAL,
                '生成指定机构的报告'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                '报告类型（summary|detailed|compliance|statistics）',
                'summary'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                '输出格式（table|json|html|csv）',
                'table'
            )
            ->addOption(
                'output-file',
                'o',
                InputOption::VALUE_OPTIONAL,
                '输出文件路径（不指定则输出到控制台）'
            )
            ->addOption(
                'date-range',
                'd',
                InputOption::VALUE_OPTIONAL,
                '统计日期范围（格式：YYYY-MM-DD,YYYY-MM-DD）'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('培训机构报告生成');

        try {
            $parameters = $this->parseCommandParameters($input, $io);
            $reportData = $this->generateReport($parameters, $io);

            if ([] === $reportData) {
                return Command::FAILURE;
            }

            $this->outputReport($reportData, $parameters, $io, $output);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * 生成单个机构报告
     * @return array<string, mixed>
     */
    private function generateInstitutionReport(string $institutionId, string $reportType, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $institution = $this->institutionService->getInstitutionById($institutionId);

        if (null === $institution) {
            throw new \InvalidArgumentException("Institution with ID {$institutionId} not found");
        }

        $report = $this->buildBaseReport($institution, $reportType);

        return $this->appendReportDataByType($report, $institutionId, $reportType, $startDate, $endDate);
    }

    /**
     * 构建基础报告数据
     * @return array<string, mixed>
     */
    private function buildBaseReport(Institution $institution, string $reportType): array
    {
        return [
            'institution' => [
                'id' => $institution->getId(),
                'name' => $institution->getInstitutionName(),
                'code' => $institution->getInstitutionCode(),
                'type' => $institution->getInstitutionType(),
                'status' => $institution->getInstitutionStatus(),
                'legal_person' => $institution->getLegalPerson(),
                'contact_person' => $institution->getContactPerson(),
                'address' => $institution->getAddress(),
                'establish_date' => $institution->getEstablishDate()->format('Y-m-d'),
            ],
            'generated_at' => date('Y-m-d H:i:s'),
            'report_type' => $reportType,
        ];
    }

    /**
     * 根据报告类型追加数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendReportDataByType(array $report, string $institutionId, string $reportType, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        return match ($reportType) {
            'detailed' => $this->appendDetailedReportData($report, $institutionId, $startDate, $endDate),
            'compliance' => $this->appendComplianceReportData($report, $institutionId),
            'statistics' => $this->appendStatisticsReportData($report, $institutionId, $startDate, $endDate),
            default => $this->appendSummaryReportData($report, $institutionId),
        };
    }

    /**
     * 追加详细报告数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendDetailedReportData(array $report, string $institutionId, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $report['qualifications'] = $this->getQualificationReport($institutionId);
        $report['facilities'] = $this->getFacilityReport($institutionId);
        $report['changes'] = $this->getChangeReport($institutionId, $startDate, $endDate);
        $report['compliance'] = $this->getComplianceReport($institutionId);

        return $report;
    }

    /**
     * 追加合规性报告数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendComplianceReportData(array $report, string $institutionId): array
    {
        $report['compliance'] = $this->getComplianceReport($institutionId);
        $report['qualifications_compliance'] = $this->getQualificationComplianceReport($institutionId);
        $report['facilities_compliance'] = $this->getFacilityComplianceReport($institutionId);

        return $report;
    }

    /**
     * 追加统计报告数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendStatisticsReportData(array $report, string $institutionId, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $report['statistics'] = $this->getInstitutionStatistics($institutionId, $startDate, $endDate);

        return $report;
    }

    /**
     * 追加摘要报告数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendSummaryReportData(array $report, string $institutionId): array
    {
        $report['summary'] = $this->getInstitutionSummary($institutionId);

        return $report;
    }

    /**
     * 生成全局报告
     * @return array<string, mixed>
     */
    private function generateGlobalReport(string $reportType, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $report = $this->buildGlobalBaseReport($reportType, $startDate, $endDate);

        return $this->appendGlobalReportDataByType($report, $reportType, $startDate, $endDate);
    }

    /**
     * 构建全局基础报告数据
     * @return array<string, mixed>
     */
    private function buildGlobalBaseReport(string $reportType, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'report_type' => $reportType,
            'date_range' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * 根据报告类型追加全局数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendGlobalReportDataByType(array $report, string $reportType, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        return match ($reportType) {
            'detailed' => $this->appendGlobalDetailedData($report, $startDate, $endDate),
            'compliance' => $this->appendGlobalComplianceData($report),
            'statistics' => $this->appendGlobalStatisticsData($report, $startDate, $endDate),
            default => $this->appendGlobalSummaryData($report),
        };
    }

    /**
     * 追加全局详细数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendGlobalDetailedData(array $report, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $report['institutions'] = $this->getAllInstitutionsReport();
        $report['global_statistics'] = $this->getGlobalStatistics($startDate, $endDate);

        return $report;
    }

    /**
     * 追加全局合规性数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendGlobalComplianceData(array $report): array
    {
        $report['compliance_overview'] = $this->getGlobalComplianceReport();

        return $report;
    }

    /**
     * 追加全局统计数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendGlobalStatisticsData(array $report, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $report['statistics'] = $this->getGlobalStatistics($startDate, $endDate);

        return $report;
    }

    /**
     * 追加全局摘要数据
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function appendGlobalSummaryData(array $report): array
    {
        $report['summary'] = $this->getGlobalSummary();

        return $report;
    }

    /**
     * 获取资质报告
     * @return array<string, mixed>
     */
    private function getQualificationReport(string $institutionId): array
    {
        $expiryInfo = $this->qualificationService->checkQualificationExpiry($institutionId);
        $statistics = $this->qualificationService->getQualificationStatistics();

        return [
            'expiry_check' => $expiryInfo,
            'statistics' => $statistics,
        ];
    }

    /**
     * 获取设施报告
     * @return array<string, mixed>
     */
    private function getFacilityReport(string $institutionId): array
    {
        return $this->facilityService->generateFacilityReport($institutionId);
    }

    /**
     * 获取变更报告
     * @return array<string, mixed>
     */
    private function getChangeReport(string $institutionId, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        if (null !== $startDate && null !== $endDate) {
            $changes = $this->changeRecordService->getChangesByDateRange($institutionId, $startDate, $endDate);

            return ['changes_in_range' => $changes];
        }

        return $this->changeRecordService->generateChangeReport($institutionId);
    }

    /**
     * 获取合规性报告
     * @return array<string>
     */
    private function getComplianceReport(string $institutionId): array
    {
        return $this->institutionService->checkInstitutionCompliance($institutionId);
    }

    /**
     * 获取机构摘要
     * @return array<string, mixed>
     */
    private function getInstitutionSummary(string $institutionId): array
    {
        $qualificationStats = $this->qualificationService->getQualificationStatistics();
        $facilityReport = $this->facilityService->generateFacilityReport($institutionId);
        $changeStats = $this->changeRecordService->getChangeStatistics();

        $summary = is_array($facilityReport['summary'] ?? null) ? $facilityReport['summary'] : [];

        return [
            'qualification_count' => $this->extractIntValue($qualificationStats, 'total'),
            'facility_count' => $this->extractIntValue($summary, 'total_facilities'),
            'total_area' => $this->extractFloatValue($summary, 'total_area'),
            'change_count' => $this->extractIntValue($changeStats, 'total'),
            'compliance_status' => [] === $this->getComplianceReport($institutionId) ? 'compliant' : 'non_compliant',
        ];
    }

    /**
     * 格式化报告
     * @param array<string, mixed> $reportData
     */
    private function formatReport(array $reportData, string $format, string $reportType): string
    {
        return match ($format) {
            'json' => $this->reportFormatter->formatJsonReport($reportData),
            'csv' => $this->reportFormatter->formatCsvReport($reportData, $reportType),
            'html' => $this->reportFormatter->formatHtmlReport($reportData, $reportType),
            default => '', // 表格格式在displayTableReport中处理
        };
    }

    /**
     * 显示表格报告
     * @param array<string, mixed> $reportData
     */
    private function displayTableReport(SymfonyStyle $io, array $reportData, string $reportType): void
    {
        if (isset($reportData['institution'])) {
            $this->displayInstitutionTableReport($io, $reportData);
        } else {
            $this->displayGlobalTableReport($io, $reportData);
        }

        $generatedAt = is_string($reportData['generated_at'] ?? null) ? $reportData['generated_at'] : 'Unknown';
        $io->note("报告生成时间：{$generatedAt}");
    }

    /**
     * 获取全局摘要
     * @return array<string, mixed>
     */
    private function getGlobalSummary(): array
    {
        // 简化实现，实际应该调用各个服务获取统计数据
        return [
            'total_institutions' => 0,
            'active_institutions' => 0,
            'total_qualifications' => 0,
            'total_facilities' => 0,
            'compliance_rate' => 0,
        ];
    }

    /**
     * 获取全局统计
     * @return array<string, mixed>
     */
    private function getGlobalStatistics(?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        return [
            'institutions' => $this->institutionService->getInstitutionStatistics(),
            'qualifications' => $this->qualificationService->getQualificationStatistics(),
            'changes' => $this->changeRecordService->getChangeStatistics(),
        ];
    }

    /**
     * 获取全局合规报告
     * @return array<string, mixed>
     */
    private function getGlobalComplianceReport(): array
    {
        // 简化实现
        return [
            'total_checked' => 0,
            'compliant' => 0,
            'non_compliant' => 0,
            'compliance_rate' => 0,
        ];
    }

    /**
     * 获取所有机构报告
     * @return array<mixed>
     */
    private function getAllInstitutionsReport(): array
    {
        // 简化实现
        return [];
    }

    /**
     * 获取机构统计
     * @return array<string, mixed>
     */
    private function getInstitutionStatistics(string $institutionId, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        return [
            'qualifications' => $this->qualificationService->getQualificationStatistics(),
            'changes' => $this->changeRecordService->getChangeStatistics(),
        ];
    }

    /**
     * 获取资质合规报告
     * @return array<string, mixed>
     */
    private function getQualificationComplianceReport(string $institutionId): array
    {
        $expiryInfo = $this->qualificationService->checkQualificationExpiry($institutionId);

        return [
            'expiry_info' => $expiryInfo,
        ];
    }

    /**
     * 获取设施合规报告
     * @return array<string, mixed>
     */
    private function getFacilityComplianceReport(string $institutionId): array
    {
        return $this->facilityService->validateFacilityRequirements($institutionId);
    }

    /**
     * 解析命令参数
     * @return array<string, mixed>
     */
    private function parseCommandParameters(InputInterface $input, SymfonyStyle $io): array
    {
        $parameters = $this->extractBasicParameters($input);

        return $this->addDateRangeParameters($parameters, $io);
    }

    /**
     * 提取基本参数
     * @return array<string, mixed>
     */
    private function extractBasicParameters(InputInterface $input): array
    {
        return [
            'institutionId' => $input->getOption('institution-id'),
            'reportType' => $input->getOption('type'),
            'format' => $input->getOption('format'),
            'outputFile' => $input->getOption('output-file'),
            'dateRange' => $input->getOption('date-range'),
            'startDate' => null,
            'endDate' => null,
        ];
    }

    /**
     * 添加日期范围参数
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function addDateRangeParameters(array $parameters, SymfonyStyle $io): array
    {
        if (null !== $parameters['dateRange'] && '' !== $parameters['dateRange']) {
            return $this->parseDateRange($parameters, $io);
        }

        return $parameters;
    }

    /**
     * 解析日期范围
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function parseDateRange(array $parameters, SymfonyStyle $io): array
    {
        $dateRange = $parameters['dateRange'] ?? '';
        if (!is_string($dateRange)) {
            throw new \InvalidArgumentException('dateRange 参数必须是字符串');
        }
        $dates = explode(',', $dateRange);
        if (2 === count($dates)) {
            $parameters['startDate'] = new \DateTimeImmutable(trim($dates[0]));
            $parameters['endDate'] = new \DateTimeImmutable(trim($dates[1]));
            $io->info("统计日期范围：{$parameters['startDate']->format('Y-m-d')} 至 {$parameters['endDate']->format('Y-m-d')}");
        }

        return $parameters;
    }

    /**
     * 生成报告
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function generateReport(array $parameters, SymfonyStyle $io): array
    {
        $institutionId = $parameters['institutionId'] ?? null;
        if (null !== $institutionId && '' !== $institutionId) {
            return $this->generateSingleInstitutionReport($parameters, $io);
        }

        return $this->generateAllInstitutionsReport($parameters, $io);
    }

    /**
     * 生成单个机构报告
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function generateSingleInstitutionReport(array $parameters, SymfonyStyle $io): array
    {
        $institutionId = $parameters['institutionId'] ?? null;
        if (!is_string($institutionId)) {
            throw new \InvalidArgumentException('institutionId 参数必须是字符串');
        }

        $institution = $this->institutionService->getInstitutionById($institutionId);
        if (null === $institution) {
            $io->error("未找到ID为 {$institutionId} 的机构");

            return [];
        }

        $io->section("生成机构报告：{$institution->getInstitutionName()}");

        $reportType = $parameters['reportType'] ?? '';
        if (!is_string($reportType)) {
            throw new \InvalidArgumentException('reportType 参数必须是字符串');
        }

        $startDate = $parameters['startDate'] ?? null;
        $endDate = $parameters['endDate'] ?? null;

        return $this->generateInstitutionReport(
            $institution->getId(),
            $reportType,
            $startDate instanceof \DateTimeInterface ? $startDate : null,
            $endDate instanceof \DateTimeInterface ? $endDate : null
        );
    }

    /**
     * 生成所有机构报告
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function generateAllInstitutionsReport(array $parameters, SymfonyStyle $io): array
    {
        $io->section('生成全局统计报告');

        $reportType = $parameters['reportType'] ?? '';
        if (!is_string($reportType)) {
            throw new \InvalidArgumentException('reportType 参数必须是字符串');
        }

        $startDate = $parameters['startDate'] ?? null;
        $endDate = $parameters['endDate'] ?? null;

        return $this->generateGlobalReport(
            $reportType,
            $startDate instanceof \DateTimeInterface ? $startDate : null,
            $endDate instanceof \DateTimeInterface ? $endDate : null
        );
    }

    /**
     * 输出报告
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $parameters
     */
    private function outputReport(array $reportData, array $parameters, SymfonyStyle $io, OutputInterface $output): void
    {
        $outputParams = $this->validateAndExtractOutputParams($parameters);

        // 确保format和reportType为string类型
        $format = is_string($outputParams['format']) ? $outputParams['format'] : 'table';
        $reportType = is_string($outputParams['reportType']) ? $outputParams['reportType'] : 'general';

        $reportContent = $this->formatReport($reportData, $format, $reportType);

        if (null !== $outputParams['outputFile']) {
            $this->outputToFile($reportContent, $outputParams['outputFile'], $io);
        } else {
            $this->outputToConsole($reportData, $reportContent, $format, $reportType, $io, $output);
        }
    }

    /**
     * 验证并提取输出参数
     * @param array<string, mixed> $parameters
     * @return array<string, string|null>
     */
    private function validateAndExtractOutputParams(array $parameters): array
    {
        $format = $parameters['format'] ?? 'table';
        $reportType = $parameters['reportType'] ?? 'general';
        $outputFile = $parameters['outputFile'] ?? null;

        if (!is_string($format)) {
            throw new \InvalidArgumentException('format 参数必须是字符串');
        }
        if (!is_string($reportType)) {
            throw new \InvalidArgumentException('reportType 参数必须是字符串');
        }
        if (null !== $outputFile && !is_string($outputFile)) {
            throw new \InvalidArgumentException('outputFile 参数必须是字符串');
        }

        $finalOutputFile = null;
        if (is_string($outputFile) && '' !== $outputFile) {
            $finalOutputFile = $outputFile;
        }

        return [
            'format' => $format,
            'reportType' => $reportType,
            'outputFile' => $finalOutputFile,
        ];
    }

    /**
     * 输出到文件
     */
    private function outputToFile(string $reportContent, string $outputFile, SymfonyStyle $io): void
    {
        file_put_contents($outputFile, $reportContent);
        $io->success("报告已保存到文件：{$outputFile}");
    }

    /**
     * 输出到控制台
     * @param array<string, mixed> $reportData
     */
    private function outputToConsole(array $reportData, string $reportContent, string $format, string $reportType, SymfonyStyle $io, OutputInterface $output): void
    {
        if ('table' === $format) {
            $this->displayTableReport($io, $reportData, $reportType);
        } else {
            $output->writeln($reportContent);
        }
    }

    /**
     * 显示单个机构表格报告
     * @param array<string, mixed> $reportData
     */
    private function displayInstitutionTableReport(SymfonyStyle $io, array $reportData): void
    {
        $institution = $reportData['institution'] ?? null;
        if (is_array($institution) && !array_is_list($institution)) {
            /** @var array<string, mixed> $institution */
            $this->displayInstitutionBasicInfo($io, $institution);
        }

        $summary = $reportData['summary'] ?? null;
        if (is_array($summary) && !array_is_list($summary)) {
            /** @var array<string, mixed> $summary */
            $this->displayInstitutionSummary($io, $summary);
        }
    }

    /**
     * 显示机构基本信息
     * @param array<string, mixed> $institution
     */
    private function displayInstitutionBasicInfo(SymfonyStyle $io, array $institution): void
    {
        $io->section('机构基本信息');
        $io->definitionList(
            ['机构名称' => $institution['name']],
            ['机构代码' => $institution['code']],
            ['机构类型' => $institution['type']],
            ['机构状态' => $institution['status']],
            ['法人代表' => $institution['legal_person']],
            ['联系人' => $institution['contact_person']],
            ['成立日期' => $institution['establish_date']]
        );
    }

    /**
     * 显示机构摘要
     * @param array<string, mixed> $summary
     */
    private function displayInstitutionSummary(SymfonyStyle $io, array $summary): void
    {
        $io->section('机构摘要');
        $totalArea = $summary['total_area'] ?? 0;
        $totalAreaStr = is_numeric($totalArea) ? (string) $totalArea : '0';
        $io->definitionList(
            ['资质数量' => $summary['qualification_count']],
            ['设施数量' => $summary['facility_count']],
            ['总面积' => $totalAreaStr . ' 平方米'],
            ['变更记录' => $summary['change_count']],
            ['合规状态' => 'compliant' === $summary['compliance_status'] ? '合规' : '不合规']
        );
    }

    /**
     * 显示全局表格报告
     * @param array<string, mixed> $reportData
     */
    private function displayGlobalTableReport(SymfonyStyle $io, array $reportData): void
    {
        $summary = $reportData['summary'] ?? null;
        if (is_array($summary) && !array_is_list($summary)) {
            /** @var array<string, mixed> $summary */
            $this->displayGlobalSummary($io, $summary);
        }
    }

    /**
     * 显示全局摘要
     * @param array<string, mixed> $summary
     */
    private function displayGlobalSummary(SymfonyStyle $io, array $summary): void
    {
        $io->section('全局摘要');
        $complianceRate = $summary['compliance_rate'] ?? 0;
        $complianceRateStr = is_numeric($complianceRate) ? (string) $complianceRate : '0';
        $io->definitionList(
            ['机构总数' => $summary['total_institutions'] ?? 0],
            ['正常运营' => $summary['active_institutions'] ?? 0],
            ['资质总数' => $summary['total_qualifications'] ?? 0],
            ['设施总数' => $summary['total_facilities'] ?? 0],
            ['合规率' => $complianceRateStr . '%']
        );
    }

    /**
     * 安全提取整数值
     * @param array<mixed> $data
     */
    private function extractIntValue(array $data, string $key): int
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * 安全提取浮点值
     * @param array<mixed> $data
     */
    private function extractFloatValue(array $data, string $key): float
    {
        $value = $data[$key] ?? 0.0;

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
