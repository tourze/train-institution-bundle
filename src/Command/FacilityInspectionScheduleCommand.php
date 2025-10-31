<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Service\FacilityService;

/**
 * 设施检查安排命令
 *
 * 自动安排需要检查的培训设施，确保设施安全和合规
 * 建议每周执行一次（cron: 0 10 * * 1）
 */
#[AsCommand(name: self::NAME, description: '安排培训设施检查')]
class FacilityInspectionScheduleCommand extends Command
{
    public const NAME = 'institution:facility:inspection-schedule';

    public function __construct(
        private readonly FacilityService $facilityService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
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
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $params = $this->parseCommandParameters($input, $io);
            $facilities = $this->getFacilitiesRequiringInspection($io);

            if ([] === $facilities) {
                $io->success('当前没有需要检查的设施');

                return Command::SUCCESS;
            }

            $this->displayFacilitiesList($io, $facilities);

            /** @var bool $autoSchedule */
            $autoSchedule = $params['autoSchedule'];
            if ($this->shouldScheduleInspections($io, $autoSchedule)) {
                $this->performInspectionScheduling($io, $facilities, $params);
            } else {
                $io->note('用户取消了检查安排');
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * 解析命令参数
     * @return array<string, mixed>
     */
    private function parseCommandParameters(InputInterface $input, SymfonyStyle $io): array
    {
        $startDateStr = $input->getOption('start-date');
        $intervalValue = $input->getOption('interval');
        $interval = is_numeric($intervalValue) ? (int) $intervalValue : 7;
        $dryRun = (bool) $input->getOption('dry-run');
        $autoScheduleValue = $input->getOption('auto-schedule');
        $autoSchedule = (bool) $autoScheduleValue;

        $io->title('培训设施检查安排');

        if ($dryRun) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        $startDate = is_string($startDateStr) && '' !== $startDateStr
            ? new \DateTimeImmutable($startDateStr)
            : new \DateTimeImmutable('tomorrow');

        return [
            'startDate' => $startDate,
            'interval' => $interval,
            'dryRun' => $dryRun,
            'autoSchedule' => $autoSchedule,
        ];
    }

    /**
     * 获取需要检查的设施列表
     * @return array<InstitutionFacility>
     */
    private function getFacilitiesRequiringInspection(SymfonyStyle $io): array
    {
        $io->section('查找需要检查的设施');

        $facilities = $this->facilityService->getFacilitiesNeedingInspection();

        if ([] !== $facilities) {
            $io->info('发现 ' . count($facilities) . ' 个需要检查的设施');
        }

        return $facilities;
    }

    /**
     * 显示需要检查的设施列表
     * @param array<InstitutionFacility> $facilities
     */
    private function displayFacilitiesList(SymfonyStyle $io, array $facilities): void
    {
        $tableData = [];

        foreach ($facilities as $facility) {
            $lastInspection = $facility->getLastInspectionDate();
            $nextInspection = $facility->getNextInspectionDate();

            $institution = $facility->getInstitution();
            $tableData[] = [
                $facility->getId(),
                null !== $institution ? $institution->getInstitutionName() : 'N/A',
                $facility->getFacilityName(),
                $facility->getFacilityType(),
                null !== $lastInspection ? $lastInspection->format('Y-m-d') : '从未检查',
                null !== $nextInspection ? $nextInspection->format('Y-m-d') : '未安排',
                $facility->getFacilityStatus(),
            ];
        }

        $io->table(
            ['设施ID', '机构名称', '设施名称', '设施类型', '上次检查', '下次检查', '状态'],
            $tableData
        );
    }

    /**
     * 判断是否应该安排检查
     */
    private function shouldScheduleInspections(SymfonyStyle $io, bool $autoSchedule): bool
    {
        return $autoSchedule || $io->confirm('是否要安排这些设施的检查？', false);
    }

    /**
     * 执行检查安排
     * @param array<InstitutionFacility> $facilities
     * @param array<string, mixed> $params
     */
    private function performInspectionScheduling(SymfonyStyle $io, array $facilities, array $params): void
    {
        $io->section('安排检查计划');

        $facilityIds = array_map(fn (InstitutionFacility $facility): string => $facility->getId(), $facilities);

        if (!(bool) $params['dryRun']) {
            $this->executeActualScheduling($io, $facilityIds, $params);
        } else {
            $this->executeDryRunScheduling($io, $facilityIds, $params);
        }
    }

    /**
     * 执行实际的检查安排
     * @param array<string> $facilityIds
     * @param array<string, mixed> $params
     */
    private function executeActualScheduling(SymfonyStyle $io, array $facilityIds, array $params): void
    {
        /** @var \DateTimeImmutable $startDate */
        $startDate = $params['startDate'];
        /** @var int $interval */
        $interval = $params['interval'];
        $results = $this->facilityService->batchScheduleInspections(
            $facilityIds,
            $startDate,
            $interval
        );

        $this->displaySchedulingResults($io, $results, $params);
    }

    /**
     * 执行干运行模式的检查安排
     * @param array<string> $facilityIds
     * @param array<string, mixed> $params
     */
    private function executeDryRunScheduling(SymfonyStyle $io, array $facilityIds, array $params): void
    {
        $io->note('干运行模式 - 以下是安排计划：');

        /** @var \DateTimeImmutable $currentDate */
        $currentDate = $params['startDate'];
        $planData = [];

        foreach ($facilityIds as $facilityId) {
            $planData[] = [
                $facilityId,
                $currentDate->format('Y-m-d'),
                $currentDate->format('l'), // 星期几
            ];
            /** @var int $intervalDays */
            $intervalDays = $params['interval'];
            $currentDate = $currentDate->modify("+{$intervalDays} days");
        }

        $io->table(
            ['设施ID', '计划检查日期', '星期'],
            $planData
        );
    }

    /**
     * 显示安排结果
     * @param array<array<string, mixed>> $results
     * @param array<string, mixed> $params
     */
    private function displaySchedulingResults(SymfonyStyle $io, array $results, array $params): void
    {
        $successCount = 0;
        $failureCount = 0;
        $scheduleData = [];

        foreach ($results as $result) {
            if (isset($result['success']) && (bool) $result['success']) {
                ++$successCount;
                $scheduleData[] = [
                    $result['facility_id'] ?? 'N/A',
                    '成功',
                    isset($result['scheduled_date']) && $result['scheduled_date'] instanceof \DateTimeInterface
                        ? $result['scheduled_date']->format('Y-m-d')
                        : 'N/A',
                    '-',
                ];
            } else {
                ++$failureCount;
                $scheduleData[] = [
                    $result['facility_id'] ?? 'N/A',
                    '失败',
                    '-',
                    $result['error'] ?? 'Unknown error',
                ];
            }
        }

        $io->table(
            ['设施ID', '安排状态', '检查日期', '错误信息'],
            $scheduleData
        );

        $this->displaySchedulingStatistics($io, $successCount, $failureCount, $params);
    }

    /**
     * 显示安排统计信息
     * @param array<string, mixed> $params
     */
    private function displaySchedulingStatistics(SymfonyStyle $io, int $successCount, int $failureCount, array $params): void
    {
        $io->section('安排结果统计');
        /** @var int $intervalDays */
        $intervalDays = $params['interval'];
        $io->definitionList(
            ['成功安排' => $successCount],
            ['安排失败' => $failureCount],
            ['开始日期' => $params['startDate'] instanceof \DateTimeInterface ? $params['startDate']->format('Y-m-d') : 'N/A'],
            ['检查间隔' => "{$intervalDays}天"],
            ['安排时间' => date('Y-m-d H:i:s')]
        );

        if ($successCount > 0) {
            $io->success("成功安排了 {$successCount} 个设施的检查");
        }
        if ($failureCount > 0) {
            $io->warning("有 {$failureCount} 个设施安排失败");
        }
    }
}
