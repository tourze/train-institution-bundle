<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

/**
 * 机构状态检查命令
 * 
 * 检查培训机构的状态和合规性，确保机构符合AQ8011-2023标准
 * 建议每日执行一次（cron: 0 8 * * *）
 */
#[AsCommand(
    name: self::NAME,
    description: '检查培训机构状态和合规性'
)]
class InstitutionStatusCheckCommand extends Command
{
    public const NAME = 'institution:status:check';
    public function __construct(
        private readonly InstitutionService $institutionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('此命令检查培训机构的状态和合规性，确保机构符合AQ8011-2023标准。建议每日执行一次。')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $status = $input->getOption('status');
        $institutionId = $input->getOption('institution-id');
        $dryRun = $input->getOption('dry-run');
        $format = $input->getOption('format');
        $complianceOnly = $input->getOption('compliance-only');

        $io->title('培训机构状态检查');

        if ($dryRun) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        try {
            $institutions = [];

            // 根据参数获取要检查的机构
            if ($institutionId) {
                $institution = $this->institutionService->getInstitutionById($institutionId);
                if ($institution) {
                    $institutions = [$institution];
                } else {
                    $io->error("未找到ID为 {$institutionId} 的机构");
                    return Command::FAILURE;
                }
            } elseif ($status) {
                $institutions = $this->institutionService->getInstitutionsByStatus($status);
                $io->info("检查状态为 '{$status}' 的机构");
            } else {
                // 获取所有正常运营的机构
                $institutions = $this->institutionService->getInstitutionsByStatus('正常运营');
                $io->info('检查所有正常运营的机构');
            }

            if (empty($institutions)) {
                $io->warning('没有找到符合条件的机构');
                return Command::SUCCESS;
            }

            $io->section("检查 " . count($institutions) . " 个机构");

            $checkResults = [];
            $totalIssues = 0;
            $compliantCount = 0;
            $nonCompliantCount = 0;

            foreach ($institutions as $institution) {
                $io->progressStart();
                
                // 检查合规性
                $complianceIssues = $this->institutionService->checkInstitutionCompliance($institution->getId());
                $isCompliant = empty($complianceIssues);
                
                if ($isCompliant) {
                    $compliantCount++;
                } else {
                    $nonCompliantCount++;
                    $totalIssues += count($complianceIssues);
                }

                $checkResults[] = [
                    'institution' => $institution,
                    'is_compliant' => $isCompliant,
                    'issues' => $complianceIssues,
                    'issue_count' => count($complianceIssues),
                ];

                $io->progressAdvance();
            }

            $io->progressFinish();

            // 根据格式输出结果
            switch ($format) {
                case 'json':
                    $jsonData = [
                        'summary' => [
                            'total_institutions' => count($institutions),
                            'compliant' => $compliantCount,
                            'non_compliant' => $nonCompliantCount,
                            'total_issues' => $totalIssues,
                            'check_time' => date('Y-m-d H:i:s'),
                        ],
                        'results' => array_map(function ($result) {
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
                    $output->writeln(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    break;

                case 'summary':
                    $io->section('检查结果摘要');
                    $io->definitionList(
                        ['检查机构总数' => count($institutions)],
                        ['合规机构数量' => $compliantCount],
                        ['不合规机构数量' => $nonCompliantCount],
                        ['发现问题总数' => $totalIssues],
                        ['合规率' => count($institutions) > 0 ? round($compliantCount / count($institutions) * 100, 2) . '%' : '0%'],
                        ['检查时间' => date('Y-m-d H:i:s')]
                    );

                    if ($nonCompliantCount > 0) {
                        $io->section('不合规机构列表');
                        $summaryData = [];
                        foreach ($checkResults as $result) {
                            if (!$result['is_compliant']) {
                                $summaryData[] = [
                                    $result['institution']->getInstitutionName(),
                                    $result['institution']->getInstitutionCode(),
                                    $result['issue_count'],
                                    implode('；', array_slice($result['issues'], 0, 2)) . ($result['issue_count'] > 2 ? '...' : ''),
                                ];
                            }
                        }
                        $io->table(['机构名称', '机构代码', '问题数量', '主要问题'], $summaryData);
                    }
                    break;

                case 'table':
                default:
                    if ($complianceOnly) {
                        // 只显示有合规问题的机构
                        $nonCompliantResults = array_filter($checkResults, fn($r) => !$r['is_compliant']);
                        if (empty($nonCompliantResults)) {
                            $io->success('所有机构都符合合规要求');
                        } else {
                            $io->warning("发现 " . count($nonCompliantResults) . " 个机构存在合规问题");
                            foreach ($nonCompliantResults as $result) {
                                $io->section($result['institution']->getInstitutionName() . ' - 合规问题');
                                $io->listing($result['issues']);
                            }
                        }
                    } else {
                        // 显示所有机构的检查结果
                        $tableData = [];
                        foreach ($checkResults as $result) {
                            $tableData[] = [
                                $result['institution']->getInstitutionName(),
                                $result['institution']->getInstitutionCode(),
                                $result['institution']->getInstitutionStatus(),
                                $result['is_compliant'] ? '合规' : '不合规',
                                $result['issue_count'],
                                $result['issue_count'] > 0 ? implode('；', array_slice($result['issues'], 0, 2)) . ($result['issue_count'] > 2 ? '...' : '') : '-',
                            ];
                        }

                        $io->table(
                            ['机构名称', '机构代码', '状态', '合规性', '问题数量', '主要问题'],
                            $tableData
                        );
                    }
                    break;
            }

            // 显示统计信息（表格模式）
            if ($format === 'table') {
                $io->section('检查结果统计');
                $io->definitionList(
                    ['检查机构总数' => count($institutions)],
                    ['合规机构数量' => $compliantCount],
                    ['不合规机构数量' => $nonCompliantCount],
                    ['发现问题总数' => $totalIssues],
                    ['合规率' => count($institutions) > 0 ? round($compliantCount / count($institutions) * 100, 2) . '%' : '0%'],
                    ['检查时间' => date('Y-m-d H:i:s')]
                );

                // 提供建议
                if ($nonCompliantCount > 0) {
                    $io->error("发现 {$nonCompliantCount} 个机构存在合规问题，请及时处理！");
                    $io->note('建议：');
                    $io->listing([
                        '联系相关机构负责人，要求整改',
                        '安排专人跟进整改进度',
                        '必要时暂停机构培训资格',
                        '定期复查整改效果',
                    ]);
                } else {
                    $io->success('所有机构都符合合规要求');
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 