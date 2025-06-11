<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
#[AsCommand(
    name: self::NAME,
    description: '生成培训机构综合报告'
)]
class InstitutionReportCommand extends Command
{
    public const NAME = 'institution:report:generate';
    public function __construct(
        private readonly InstitutionService $institutionService,
        private readonly QualificationService $qualificationService,
        private readonly FacilityService $facilityService,
        private readonly ChangeRecordService $changeRecordService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('此命令生成培训机构的综合报告，包括机构状态、资质情况、设施状况等。建议每月执行一次。')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $institutionId = $input->getOption('institution-id');
        $reportType = $input->getOption('type');
        $format = $input->getOption('format');
        $outputFile = $input->getOption('output-file');
        $dateRange = $input->getOption('date-range');

        $io->title('培训机构报告生成');

        try {
            // 解析日期范围
            $startDate = null;
            $endDate = null;
            if ($dateRange) {
                $dates = explode(',', $dateRange);
                if (count($dates) === 2) {
                    $startDate = new \DateTimeImmutable(trim($dates[0]));
                    $endDate = new \DateTimeImmutable(trim($dates[1]));
                    $io->info("统计日期范围：{$startDate->format('Y-m-d')} 至 {$endDate->format('Y-m-d')}");
                }
            }

            $reportData = [];

            if ($institutionId) {
                // 生成单个机构报告
                $institution = $this->institutionService->getInstitutionById($institutionId);
                if (!$institution) {
                    $io->error("未找到ID为 {$institutionId} 的机构");
                    return Command::FAILURE;
                }

                $io->section("生成机构报告：{$institution->getInstitutionName()}");
                $reportData = $this->generateInstitutionReport($institution->getId(), $reportType, $startDate, $endDate);

            } else {
                // 生成全局统计报告
                $io->section('生成全局统计报告');
                $reportData = $this->generateGlobalReport($reportType, $startDate, $endDate);
            }

            // 根据格式输出报告
            $reportContent = $this->formatReport($reportData, $format, $reportType);

            if ($outputFile) {
                // 输出到文件
                file_put_contents($outputFile, $reportContent);
                $io->success("报告已保存到文件：{$outputFile}");
            } else {
                // 输出到控制台
                if ($format === 'table') {
                    $this->displayTableReport($io, $reportData, $reportType);
                } else {
                    $output->writeln($reportContent);
                }
            }

            return Command::SUCCESS;

        } catch  (\Throwable $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 生成单个机构报告
     */
    private function generateInstitutionReport(string $institutionId, string $reportType, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $institution = $this->institutionService->getInstitutionById($institutionId);
        
        $report = [
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

        switch ($reportType) {
            case 'detailed':
                // 详细报告
                $report['qualifications'] = $this->getQualificationReport($institutionId);
                $report['facilities'] = $this->getFacilityReport($institutionId);
                $report['changes'] = $this->getChangeReport($institutionId, $startDate, $endDate);
                $report['compliance'] = $this->getComplianceReport($institutionId);
                break;

            case 'compliance':
                // 合规性报告
                $report['compliance'] = $this->getComplianceReport($institutionId);
                $report['qualifications_compliance'] = $this->getQualificationComplianceReport($institutionId);
                $report['facilities_compliance'] = $this->getFacilityComplianceReport($institutionId);
                break;

            case 'statistics':
                // 统计报告
                $report['statistics'] = $this->getInstitutionStatistics($institutionId, $startDate, $endDate);
                break;

            case 'summary':
            default:
                // 摘要报告
                $report['summary'] = $this->getInstitutionSummary($institutionId);
                break;
        }

        return $report;
    }

    /**
     * 生成全局报告
     */
    private function generateGlobalReport(string $reportType, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'report_type' => $reportType,
            'date_range' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
        ];

        switch ($reportType) {
            case 'detailed':
                $report['institutions'] = $this->getAllInstitutionsReport();
                $report['global_statistics'] = $this->getGlobalStatistics($startDate, $endDate);
                break;

            case 'compliance':
                $report['compliance_overview'] = $this->getGlobalComplianceReport();
                break;

            case 'statistics':
                $report['statistics'] = $this->getGlobalStatistics($startDate, $endDate);
                break;

            case 'summary':
            default:
                $report['summary'] = $this->getGlobalSummary();
                break;
        }

        return $report;
    }

    /**
     * 获取资质报告
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
     */
    private function getFacilityReport(string $institutionId): array
    {
        return $this->facilityService->generateFacilityReport($institutionId);
    }

    /**
     * 获取变更报告
     */
    private function getChangeReport(string $institutionId, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        if ($startDate && $endDate) {
            $changes = $this->changeRecordService->getChangesByDateRange($institutionId, $startDate, $endDate);
            return ['changes_in_range' => $changes];
        }
        
        return $this->changeRecordService->generateChangeReport($institutionId);
    }

    /**
     * 获取合规性报告
     */
    private function getComplianceReport(string $institutionId): array
    {
        return $this->institutionService->checkInstitutionCompliance($institutionId);
    }

    /**
     * 获取机构摘要
     */
    private function getInstitutionSummary(string $institutionId): array
    {
        $qualificationStats = $this->qualificationService->getQualificationStatistics();
        $facilityReport = $this->facilityService->generateFacilityReport($institutionId);
        $changeStats = $this->changeRecordService->getChangeStatistics();

        return [
            'qualification_count' => $qualificationStats['total'] ?? 0,
            'facility_count' => $facilityReport['summary']['total_facilities'] ?? 0,
            'total_area' => $facilityReport['summary']['total_area'] ?? 0,
            'change_count' => $changeStats['total'] ?? 0,
            'compliance_status' => empty($this->getComplianceReport($institutionId)) ? 'compliant' : 'non_compliant',
        ];
    }

    /**
     * 格式化报告
     */
    private function formatReport(array $reportData, string $format, string $reportType): string
    {
        switch ($format) {
            case 'json':
                return json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            case 'csv':
                return $this->formatCsvReport($reportData, $reportType);

            case 'html':
                return $this->formatHtmlReport($reportData, $reportType);

            case 'table':
            default:
                return ''; // 表格格式在displayTableReport中处理
        }
    }

    /**
     * 显示表格报告
     */
    private function displayTableReport(SymfonyStyle $io, array $reportData, string $reportType): void
    {
        if (isset($reportData['institution'])) {
            // 单个机构报告
            $io->section('机构基本信息');
            $io->definitionList(
                ['机构名称' => $reportData['institution']['name']],
                ['机构代码' => $reportData['institution']['code']],
                ['机构类型' => $reportData['institution']['type']],
                ['机构状态' => $reportData['institution']['status']],
                ['法人代表' => $reportData['institution']['legal_person']],
                ['联系人' => $reportData['institution']['contact_person']],
                ['成立日期' => $reportData['institution']['establish_date']]
            );

            if (isset($reportData['summary'])) {
                $io->section('机构摘要');
                $io->definitionList(
                    ['资质数量' => $reportData['summary']['qualification_count']],
                    ['设施数量' => $reportData['summary']['facility_count']],
                    ['总面积' => $reportData['summary']['total_area'] . ' 平方米'],
                    ['变更记录' => $reportData['summary']['change_count']],
                    ['合规状态' => $reportData['summary']['compliance_status'] === 'compliant' ? '合规' : '不合规']
                );
            }
        } else {
            // 全局报告
            if (isset($reportData['summary'])) {
                $io->section('全局摘要');
                $summary = $reportData['summary'];
                $io->definitionList(
                    ['机构总数' => $summary['total_institutions'] ?? 0],
                    ['正常运营' => $summary['active_institutions'] ?? 0],
                    ['资质总数' => $summary['total_qualifications'] ?? 0],
                    ['设施总数' => $summary['total_facilities'] ?? 0],
                    ['合规率' => ($summary['compliance_rate'] ?? 0) . '%']
                );
            }
        }

        $io->note("报告生成时间：{$reportData['generated_at']}");
    }

    /**
     * 格式化CSV报告
     */
    private function formatCsvReport(array $reportData, string $reportType): string
    {
        // 简化的CSV格式实现
        $csv = "报告类型,{$reportType}\n";
        $csv .= "生成时间,{$reportData['generated_at']}\n";
        
        if (isset($reportData['institution'])) {
            $csv .= "机构名称,{$reportData['institution']['name']}\n";
            $csv .= "机构代码,{$reportData['institution']['code']}\n";
        }
        
        return $csv;
    }

    /**
     * 格式化HTML报告
     */
    private function formatHtmlReport(array $reportData, string $reportType): string
    {
        $html = "<html><head><title>培训机构报告</title></head><body>";
        $html .= "<h1>培训机构{$reportType}报告</h1>";
        $html .= "<p>生成时间：{$reportData['generated_at']}</p>";
        
        if (isset($reportData['institution'])) {
            $inst = $reportData['institution'];
            $html .= "<h2>机构信息</h2>";
            $html .= "<p>机构名称：{$inst['name']}</p>";
            $html .= "<p>机构代码：{$inst['code']}</p>";
        }
        
        $html .= "</body></html>";
        return $html;
    }

    /**
     * 获取全局摘要
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
     */
    private function getAllInstitutionsReport(): array
    {
        // 简化实现
        return [];
    }

    /**
     * 获取机构统计
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
     */
    private function getQualificationComplianceReport(string $institutionId): array
    {
        return $this->qualificationService->checkQualificationExpiry($institutionId);
    }

    /**
     * 获取设施合规报告
     */
    private function getFacilityComplianceReport(string $institutionId): array
    {
        return $this->facilityService->validateFacilityRequirements($institutionId);
    }
} 