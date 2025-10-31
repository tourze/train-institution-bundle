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
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

/**
 * 机构状态检查命令
 *
 * 检查培训机构的状态和合规性，确保机构符合AQ8011-2023标准
 * 建议每日执行一次（cron: 0 8 * * *）
 */
#[AsCommand(name: self::NAME, description: '检查培训机构状态和合规性')]
class InstitutionStatusCheckCommand extends Command
{
    public const NAME = 'institution:status:check';

    public function __construct(
        private readonly InstitutionService $institutionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'status',
                's',
                InputOption::VALUE_OPTIONAL,
                '检查指定状态的机构（如：正常运营、待审核等）'
            )
            ->addOption(
                'institution-id',
                'i',
                InputOption::VALUE_OPTIONAL,
                '检查指定ID的机构'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '干运行模式，只显示结果不执行实际操作'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                '输出格式（table|json|summary）',
                'table'
            )
            ->addOption(
                'compliance-only',
                'c',
                InputOption::VALUE_NONE,
                '只检查合规性问题'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $parameters = $this->parseCommandParameters($input, $io);
            $institutions = $this->getInstitutionsToCheck($parameters, $io);

            if ([] === $institutions) {
                $io->warning('没有找到符合条件的机构');

                return Command::SUCCESS;
            }

            $checkResults = $this->performInstitutionChecks($institutions, $io);
            $this->outputResults($checkResults, $parameters, $io, $output);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * 解析和验证命令参数
     * @return array<string, mixed>
     */
    private function parseCommandParameters(InputInterface $input, SymfonyStyle $io): array
    {
        $parameters = [
            'status' => $input->getOption('status'),
            'institution_id' => $input->getOption('institution-id'),
            'dry_run' => (bool) $input->getOption('dry-run'),
            'format' => $input->getOption('format'),
            'compliance_only' => (bool) $input->getOption('compliance-only'),
        ];

        $io->title('培训机构状态检查');

        if ($parameters['dry_run']) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        return $parameters;
    }

    /**
     * 获取要检查的机构列表
     * @param array<string, mixed> $parameters
     * @return array<mixed>
     */
    private function getInstitutionsToCheck(array $parameters, SymfonyStyle $io): array
    {
        $institutionId = $parameters['institution_id'] ?? null;
        if (null !== $institutionId && '' !== $institutionId && is_string($institutionId)) {
            return $this->getInstitutionById($institutionId, $io);
        }

        $status = $parameters['status'] ?? null;
        if (null !== $status && '' !== $status && is_string($status)) {
            $institutions = $this->institutionService->getInstitutionsByStatus($status);
            $io->info("检查状态为 '{$status}' 的机构");

            return $institutions;
        }

        $institutions = $this->institutionService->getInstitutionsByStatus('正常运营');
        $io->info('检查所有正常运营的机构');

        return $institutions;
    }

    /**
     * 根据ID获取单个机构
     * @return array<mixed>
     */
    private function getInstitutionById(string $institutionId, SymfonyStyle $io): array
    {
        $institution = $this->institutionService->getInstitutionById($institutionId);

        if (null === $institution) {
            $io->error("未找到ID为 {$institutionId} 的机构");
            throw new InstitutionNotFoundException($institutionId);
        }

        return [$institution];
    }

    /**
     * 执行机构检查
     * @param array<mixed> $institutions
     * @return array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}>
     */
    private function performInstitutionChecks(array $institutions, SymfonyStyle $io): array
    {
        $io->section('检查 ' . count($institutions) . ' 个机构');

        $checkResults = [];

        foreach ($institutions as $institution) {
            if (!$institution instanceof Institution) {
                continue;
            }

            $io->progressStart();

            $complianceIssues = $this->institutionService->checkInstitutionCompliance($institution->getId());
            $isCompliant = [] === $complianceIssues;

            $checkResults[] = [
                'institution' => $institution,
                'is_compliant' => $isCompliant,
                'issues' => $complianceIssues,
                'issue_count' => count($complianceIssues),
            ];

            $io->progressAdvance();
        }

        $io->progressFinish();

        return $checkResults;
    }

    /**
     * 输出检查结果
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     * @param array<string, mixed> $parameters
     */
    private function outputResults(array $checkResults, array $parameters, SymfonyStyle $io, OutputInterface $output): void
    {
        $statistics = $this->calculateStatistics($checkResults);

        $format = $parameters['format'] ?? 'table';
        $complianceOnly = $parameters['compliance_only'] ?? false;

        if (!is_string($format)) {
            $format = 'table';
        }
        if (!is_bool($complianceOnly)) {
            $complianceOnly = false;
        }

        switch ($format) {
            case 'json':
                $this->outputJsonFormat($checkResults, $statistics, $output);
                break;
            case 'summary':
                $this->outputSummaryFormat($checkResults, $statistics, $io);
                break;
            case 'table':
            default:
                $this->outputTableFormat($checkResults, $statistics, $complianceOnly, $io);
                break;
        }
    }

    /**
     * 计算统计数据
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     * @return array<string, mixed>
     */
    private function calculateStatistics(array $checkResults): array
    {
        $compliantCount = 0;
        $nonCompliantCount = 0;
        $totalIssues = 0;

        foreach ($checkResults as $result) {
            if ($result['is_compliant']) {
                ++$compliantCount;
            } else {
                ++$nonCompliantCount;
                $totalIssues += $result['issue_count'];
            }
        }

        return [
            'total_institutions' => count($checkResults),
            'compliant' => $compliantCount,
            'non_compliant' => $nonCompliantCount,
            'total_issues' => $totalIssues,
            'compliance_rate' => count($checkResults) > 0 ? round($compliantCount / count($checkResults) * 100, 2) : 0,
            'check_time' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 输出JSON格式结果
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     * @param array<string, mixed> $statistics
     */
    private function outputJsonFormat(array $checkResults, array $statistics, OutputInterface $output): void
    {
        $jsonData = [
            'summary' => $statistics,
            'results' => array_map(function (array $result): array {
                return [
                    'institution_id' => $result['institution']->getId(),
                    'institution_name' => $result['institution']->getInstitutionName(),
                    'institution_code' => $result['institution']->getInstitutionCode(),
                    'status' => $result['institution']->getInstitutionStatus(),
                    'is_compliant' => $result['is_compliant'],
                    'issue_count' => $result['issue_count'],
                    'issues' => $result['issues'],
                ];
            }, $checkResults),
        ];

        $jsonResult = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $output->writeln(false !== $jsonResult ? $jsonResult : '{}');
    }

    /**
     * 输出摘要格式结果
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     * @param array<string, mixed> $statistics
     */
    private function outputSummaryFormat(array $checkResults, array $statistics, SymfonyStyle $io): void
    {
        $io->section('检查结果摘要');
        $complianceRate = is_numeric($statistics['compliance_rate']) ? (string) $statistics['compliance_rate'] : '0';
        $io->definitionList(
            ['检查机构总数' => $statistics['total_institutions']],
            ['合规机构数量' => $statistics['compliant']],
            ['不合规机构数量' => $statistics['non_compliant']],
            ['发现问题总数' => $statistics['total_issues']],
            ['合规率' => $complianceRate . '%'],
            ['检查时间' => $statistics['check_time']]
        );

        $nonCompliantCount = $statistics['non_compliant'] ?? 0;
        if (is_int($nonCompliantCount) && $nonCompliantCount > 0) {
            $this->outputNonCompliantInstitutionsSummary($checkResults, $io);
        }
    }

    /**
     * 输出不合规机构摘要
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     */
    private function outputNonCompliantInstitutionsSummary(array $checkResults, SymfonyStyle $io): void
    {
        $io->section('不合规机构列表');
        $summaryData = [];

        foreach ($checkResults as $result) {
            if (!$result['is_compliant']) {
                $mainIssues = implode('；', array_slice($result['issues'], 0, 2));
                $suffix = $result['issue_count'] > 2 ? '...' : '';
                $summaryData[] = [
                    $result['institution']->getInstitutionName(),
                    $result['institution']->getInstitutionCode(),
                    $result['issue_count'],
                    $mainIssues . $suffix,
                ];
            }
        }

        $io->table(['机构名称', '机构代码', '问题数量', '主要问题'], $summaryData);
    }

    /**
     * 输出表格格式结果
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     * @param array<string, mixed> $statistics
     */
    private function outputTableFormat(array $checkResults, array $statistics, bool $complianceOnly, SymfonyStyle $io): void
    {
        if ($complianceOnly) {
            $this->outputComplianceOnlyResults($checkResults, $io);
        } else {
            $this->outputFullTableResults($checkResults, $io);
        }

        $this->outputTableStatistics($statistics, $io);
    }

    /**
     * 输出仅合规性问题的结果
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     */
    private function outputComplianceOnlyResults(array $checkResults, SymfonyStyle $io): void
    {
        $nonCompliantResults = array_filter($checkResults, fn (array $r): bool => !$r['is_compliant']);

        if ([] === $nonCompliantResults) {
            $io->success('所有机构都符合合规要求');
        } else {
            $io->warning('发现 ' . count($nonCompliantResults) . ' 个机构存在合规问题');
            foreach ($nonCompliantResults as $result) {
                $io->section($result['institution']->getInstitutionName() . ' - 合规问题');
                $io->listing($result['issues']);
            }
        }
    }

    /**
     * 输出完整表格结果
     * @param array<array{institution: Institution, is_compliant: bool, issues: array<string>, issue_count: int}> $checkResults
     */
    private function outputFullTableResults(array $checkResults, SymfonyStyle $io): void
    {
        $tableData = [];

        foreach ($checkResults as $result) {
            $mainIssues = $result['issue_count'] > 0 ? implode('；', array_slice($result['issues'], 0, 2)) : '-';
            $suffix = $result['issue_count'] > 2 ? '...' : '';
            $tableData[] = [
                $result['institution']->getInstitutionName(),
                $result['institution']->getInstitutionCode(),
                $result['institution']->getInstitutionStatus(),
                $result['is_compliant'] ? '合规' : '不合规',
                $result['issue_count'],
                $mainIssues . $suffix,
            ];
        }

        $io->table(
            ['机构名称', '机构代码', '状态', '合规性', '问题数量', '主要问题'],
            $tableData
        );
    }

    /**
     * 输出表格模式统计信息
     * @param array<string, mixed> $statistics
     */
    private function outputTableStatistics(array $statistics, SymfonyStyle $io): void
    {
        $io->section('检查结果统计');
        $complianceRate = is_numeric($statistics['compliance_rate']) ? (string) $statistics['compliance_rate'] : '0';
        $io->definitionList(
            ['检查机构总数' => $statistics['total_institutions']],
            ['合规机构数量' => $statistics['compliant']],
            ['不合规机构数量' => $statistics['non_compliant']],
            ['发现问题总数' => $statistics['total_issues']],
            ['合规率' => $complianceRate . '%'],
            ['检查时间' => $statistics['check_time']]
        );

        $nonCompliantCount = $statistics['non_compliant'] ?? 0;
        if (is_int($nonCompliantCount) && $nonCompliantCount > 0) {
            $this->outputRecommendations($nonCompliantCount, $io);
        } else {
            $io->success('所有机构都符合合规要求');
        }
    }

    /**
     * 输出建议
     */
    private function outputRecommendations(int $nonCompliantCount, SymfonyStyle $io): void
    {
        $io->error("发现 {$nonCompliantCount} 个机构存在合规问题，请及时处理！");
        $io->note('建议：');
        $io->listing([
            '联系相关机构负责人，要求整改',
            '安排专人跟进整改进度',
            '必要时暂停机构培训资格',
            '定期复查整改效果',
        ]);
    }
}
