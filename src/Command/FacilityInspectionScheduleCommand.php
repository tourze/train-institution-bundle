<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainInstitutionBundle\Service\FacilityService;

/**
 * 设施检查安排命令
 * 
 * 自动安排需要检查的培训设施，确保设施安全和合规
 * 建议每周执行一次（cron: 0 10 * * 1）
 */
#[AsCommand(
    name: self::NAME,
    description: '安排培训设施检查'
)]
class FacilityInspectionScheduleCommand extends Command
{
    public const NAME = 'institution:facility:inspection-schedule';
    public function __construct(
        private readonly FacilityService $facilityService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('此命令自动安排需要检查的培训设施，确保设施安全和合规。建议每周执行一次。')
            ->addOption(
                'start-date',
                's',
                InputOption::VALUE_OPTIONAL,
                '检查开始日期（YYYY-MM-DD格式，默认明天）'
            )
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                '检查间隔天数（默认7天）',
                7
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '干运行模式，只显示结果不执行实际操作'
            )
            ->addOption(
                'auto-schedule',
                'a',
                InputOption::VALUE_NONE,
                '自动安排所有需要检查的设施'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startDateStr = $input->getOption('start-date');
        $interval = (int) $input->getOption('interval');
        $dryRun = $input->getOption('dry-run');
        $autoSchedule = $input->getOption('auto-schedule');

        $io->title('培训设施检查安排');

        if ($dryRun) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        try {
            // 解析开始日期
            $startDate = $startDateStr 
                ? new \DateTimeImmutable($startDateStr)
                : new \DateTimeImmutable('tomorrow');

            $io->section('查找需要检查的设施');

            // 获取需要检查的设施
            $facilitiesNeedingInspection = $this->facilityService->getFacilitiesNeedingInspection();

            if (empty($facilitiesNeedingInspection)) {
                $io->success('当前没有需要检查的设施');
                return Command::SUCCESS;
            }

            $io->info("发现 " . count($facilitiesNeedingInspection) . " 个需要检查的设施");

            // 显示需要检查的设施列表
            $tableData = [];
            $facilityIds = [];

            foreach ($facilitiesNeedingInspection as $facility) {
                $lastInspection = $facility->getLastInspectionDate();
                $nextInspection = $facility->getNextInspectionDate();
                
                $tableData[] = [
                    $facility->getId(),
                    $facility->getInstitution()->getInstitutionName(),
                    $facility->getFacilityName(),
                    $facility->getFacilityType(),
                    $lastInspection ? $lastInspection->format('Y-m-d') : '从未检查',
                    $nextInspection ? $nextInspection->format('Y-m-d') : '未安排',
                    $facility->getFacilityStatus(),
                ];

                $facilityIds[] = $facility->getId();
            }

            $io->table(
                ['设施ID', '机构名称', '设施名称', '设施类型', '上次检查', '下次检查', '状态'],
                $tableData
            );

            // 如果是自动安排模式或用户确认
            if ($autoSchedule || $io->confirm('是否要安排这些设施的检查？', false)) {
                $io->section('安排检查计划');

                if (!$dryRun) {
                    // 批量安排检查
                    $results = $this->facilityService->batchScheduleInspections(
                        $facilityIds,
                        $startDate,
                        $interval
                    );

                    // 显示安排结果
                    $successCount = 0;
                    $failureCount = 0;
                    $scheduleData = [];

                    foreach ($results as $result) {
                        if ($result['success']) {
                            $successCount++;
                            $scheduleData[] = [
                                $result['facility_id'],
                                '成功',
                                $result['scheduled_date']->format('Y-m-d'),
                                '-',
                            ];
                        } else {
                            $failureCount++;
                            $scheduleData[] = [
                                $result['facility_id'],
                                '失败',
                                '-',
                                $result['error'],
                            ];
                        }
                    }

                    $io->table(
                        ['设施ID', '安排状态', '检查日期', '错误信息'],
                        $scheduleData
                    );

                    $io->section('安排结果统计');
                    $io->definitionList(
                        ['成功安排' => $successCount],
                        ['安排失败' => $failureCount],
                        ['开始日期' => $startDate->format('Y-m-d')],
                        ['检查间隔' => "{$interval}天"],
                        ['安排时间' => date('Y-m-d H:i:s')]
                    );

                    if ($successCount > 0) {
                        $io->success("成功安排了 {$successCount} 个设施的检查");
                    }
                    if ($failureCount > 0) {
                        $io->warning("有 {$failureCount} 个设施安排失败");
                    }

                } else {
                    // 干运行模式，只显示计划
                    $io->note('干运行模式 - 以下是安排计划：');
                    $currentDate = $startDate;
                    $planData = [];

                    foreach ($facilityIds as $facilityId) {
                        $planData[] = [
                            $facilityId,
                            $currentDate->format('Y-m-d'),
                            $currentDate->format('l'), // 星期几
                        ];
                        $currentDate = $currentDate->modify("+{$interval} days");
                    }

                    $io->table(
                        ['设施ID', '计划检查日期', '星期'],
                        $planData
                    );
                }
            } else {
                $io->note('用户取消了检查安排');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 