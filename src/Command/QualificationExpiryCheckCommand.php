<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * 资质到期检查命令
 * 
 * 检查即将到期的培训机构资质证书，生成提醒报告
 * 建议每日执行一次（cron: 0 9 * * *）
 */
#[AsCommand(
    name: self::NAME,
    description: '检查即将到期的培训机构资质证书'
)]
class QualificationExpiryCheckCommand extends Command
{
    public const NAME = 'institution:qualification:expiry-check';

    public function __construct(
        private readonly QualificationService $qualificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('此命令检查即将到期的培训机构资质证书，生成提醒报告。建议每日执行一次。')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                '检查多少天内到期的资质（默认30天）',
                30
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
                '输出格式（table|json|csv）',
                'table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');
        $format = $input->getOption('format');

        $io->title('培训机构资质到期检查');

        if ($dryRun) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        try {
            // 获取即将到期的资质
            $io->section("检查{$days}天内到期的资质");
            $expiringQualifications = $this->qualificationService->getExpiringQualifications($days);

            if (empty($expiringQualifications)) {
                $io->success("未发现{$days}天内到期的资质");
                return Command::SUCCESS;
            }

            $io->warning("发现 " . count($expiringQualifications) . " 个即将到期的资质");

            // 准备输出数据
            $tableData = [];
            $criticalCount = 0; // 7天内到期
            $warningCount = 0;  // 30天内到期

            foreach ($expiringQualifications as $qualification) {
                $remainingDays = $qualification->getRemainingDays();
                $status = $remainingDays <= 7 ? '紧急' : '警告';
                
                if ($remainingDays <= 7) {
                    $criticalCount++;
                } else {
                    $warningCount++;
                }

                $tableData[] = [
                    $qualification->getInstitution()->getInstitutionName(),
                    $qualification->getQualificationName(),
                    $qualification->getCertificateNumber(),
                    $qualification->getValidTo()->format('Y-m-d'),
                    $remainingDays,
                    $status,
                ];
            }

            // 根据格式输出结果
            switch ($format) {
                case 'json':
                    $jsonData = [
                        'summary' => [
                            'total' => count($expiringQualifications),
                            'critical' => $criticalCount,
                            'warning' => $warningCount,
                            'check_days' => $days,
                            'check_time' => date('Y-m-d H:i:s'),
                        ],
                        'qualifications' => array_map(function ($q) {
                            return [
                                'institution_name' => $q->getInstitution()->getInstitutionName(),
                                'qualification_name' => $q->getQualificationName(),
                                'certificate_number' => $q->getCertificateNumber(),
                                'valid_to' => $q->getValidTo()->format('Y-m-d'),
                                'remaining_days' => $q->getRemainingDays(),
                                'status' => $q->getRemainingDays() <= 7 ? 'critical' : 'warning',
                            ];
                        }, $expiringQualifications),
                    ];
                    $output->writeln(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    break;

                case 'csv':
                    $output->writeln('机构名称,资质名称,证书编号,到期日期,剩余天数,状态');
                    foreach ($tableData as $row) {
                        $output->writeln(implode(',', $row));
                    }
                    break;

                case 'table':
                default:
                    $io->table(
                        ['机构名称', '资质名称', '证书编号', '到期日期', '剩余天数', '状态'],
                        $tableData
                    );
                    break;
            }

            // 显示统计信息
            if ($format === 'table') {
                $io->section('统计信息');
                $io->definitionList(
                    ['总计' => count($expiringQualifications)],
                    ['紧急（7天内）' => $criticalCount],
                    ['警告（30天内）' => $warningCount],
                    ['检查范围' => "{$days}天内"],
                    ['检查时间' => date('Y-m-d H:i:s')]
                );

                // 提供建议
                if ($criticalCount > 0) {
                    $io->error("有 {$criticalCount} 个资质将在7天内到期，请立即处理！");
                }
                if ($warningCount > 0) {
                    $io->warning("有 {$warningCount} 个资质将在30天内到期，请及时安排续期。");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 