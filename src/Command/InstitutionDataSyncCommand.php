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
 * 机构数据同步命令
 *
 * 同步培训机构数据，确保数据一致性和完整性
 * 建议每日执行一次（cron: 0 2 * * *）
 */
#[AsCommand(name: self::NAME, description: '同步培训机构数据')]
final class InstitutionDataSyncCommand extends Command
{
    public const NAME = 'institution:data:sync';

    public function __construct(
        private readonly InstitutionService $institutionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('同步培训机构数据，确保数据一致性和完整性。建议每日执行一次（cron: 0 2 * * *）')
            ->addOption(
                'source',
                's',
                InputOption::VALUE_OPTIONAL,
                '数据源类型（database|api|file）',
                'database'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '干运行模式，只显示结果不执行实际操作'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                '强制同步，覆盖现有数据'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                '批处理大小（默认100）',
                100
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. 解析参数
        $options = $this->parseInputOptions($input, $io);

        try {
            // 2. 获取同步数据
            /** @var string $source */
            $source = $options['source'];
            $syncData = $this->getSyncData($source);

            if ([] === $syncData) {
                $io->success('没有需要同步的数据');

                return Command::SUCCESS;
            }

            // 3. 执行数据同步
            $stats = $this->performDataSync($syncData, $options, $io);

            // 4. 显示结果和生成报告
            $this->displayResultsAndGenerateReport($stats, $options, $io);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            return $this->handleSyncError($e, $io);
        }
    }

    /**
     * 解析输入参数
     * @return array<string, mixed>
     */
    private function parseInputOptions(InputInterface $input, SymfonyStyle $io): array
    {
        $sourceValue = $input->getOption('source');
        $batchSizeValue = $input->getOption('batch-size');

        $options = [
            'source' => is_string($sourceValue) ? $sourceValue : 'database',
            'dryRun' => (bool) $input->getOption('dry-run'),
            'force' => (bool) $input->getOption('force'),
            'batchSize' => is_numeric($batchSizeValue) ? (int) $batchSizeValue : 100,
        ];

        $io->title('培训机构数据同步');

        if ($options['dryRun']) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        if ($options['force']) {
            $io->warning('强制模式已启用，将覆盖现有数据');
        }

        $source = $options['source'];
        $io->section("从 {$source} 同步数据");

        return $options;
    }

    /**
     * 执行数据同步
     * @param array<array<string, mixed>> $syncData
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function performDataSync(array $syncData, array $options, SymfonyStyle $io): array
    {
        $io->info('发现 ' . count($syncData) . ' 条需要同步的数据');

        $stats = $this->initializeStats();
        $io->progressStart(count($syncData));

        /** @var int $batchSize */
        $batchSize = $options['batchSize'];
        $batches = array_chunk($syncData, max(1, $batchSize));

        foreach ($batches as $batch) {
            $stats = $this->processBatch($batch, $options, $io, $stats);
        }

        $io->progressFinish();
        $stats['total'] = count($syncData);

        return $stats;
    }

    /**
     * 初始化统计数据
     * @return array<string, mixed>
     */
    private function initializeStats(): array
    {
        return [
            'success' => 0,
            'failure' => 0,
            'skipped' => 0,
            'updated' => 0,
            'created' => 0,
            'total' => 0,
        ];
    }

    /**
     * 处理数据批次
     * @param array<array<string, mixed>> $batch
     * @param array<string, mixed> $options
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    private function processBatch(array $batch, array $options, SymfonyStyle $io, array $stats): array
    {
        foreach ($batch as $data) {
            try {
                $stats = $this->processSingleRecord($data, $options, $stats);
            } catch (\Throwable $e) {
                $stats = $this->handleRecordError($data, $e, $io, $stats);
            }

            $io->progressAdvance();
        }

        return $stats;
    }

    /**
     * 处理单条记录
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    private function processSingleRecord(array $data, array $options, array $stats): array
    {
        /** @var bool $dryRun */
        $dryRun = $options['dryRun'];
        /** @var bool $force */
        $force = $options['force'];

        if (!$dryRun) {
            $result = $this->syncInstitutionData($data, $force);
            $stats = $this->updateStatsFromResult($result, $stats);
        } else {
            $this->validateSyncData($data);
            assert(is_int($stats['success'] ?? 0));
            $stats['success'] = ($stats['success'] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * 根据同步结果更新统计
     * @param array<string, mixed> $result
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    private function updateStatsFromResult(array $result, array $stats): array
    {
        $action = $result['action'] ?? 'unknown';
        switch ($action) {
            case 'created':
                $createdValue = $stats['created'] ?? 0;
                $successValue = $stats['success'] ?? 0;
                $createdCount = is_int($createdValue) ? $createdValue : 0;
                $successCount = is_int($successValue) ? $successValue : 0;
                $stats['created'] = $createdCount + 1;
                $stats['success'] = $successCount + 1;
                break;
            case 'updated':
                $updatedValue = $stats['updated'] ?? 0;
                $successValue = $stats['success'] ?? 0;
                $updatedCount = is_int($updatedValue) ? $updatedValue : 0;
                $successCount = is_int($successValue) ? $successValue : 0;
                $stats['updated'] = $updatedCount + 1;
                $stats['success'] = $successCount + 1;
                break;
            case 'skipped':
                $skippedValue = $stats['skipped'] ?? 0;
                $skippedCount = is_int($skippedValue) ? $skippedValue : 0;
                $stats['skipped'] = $skippedCount + 1;
                break;
        }

        return $stats;
    }

    /**
     * 处理记录错误
     * @param array<string, mixed> $data
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    private function handleRecordError(array $data, \Throwable $e, SymfonyStyle $io, array $stats): array
    {
        assert(is_int($stats['failure'] ?? 0));
        $stats['failure'] = ($stats['failure'] ?? 0) + 1;
        $dataName = isset($data['name']) && is_string($data['name']) ? $data['name'] : 'Unknown';
        $io->error("同步数据失败: {$dataName} - {$e->getMessage()}");

        return $stats;
    }

    /**
     * 显示结果并生成报告
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $options
     */
    private function displayResultsAndGenerateReport(array $stats, array $options, SymfonyStyle $io): void
    {
        $this->displaySyncResults($stats, $options, $io);

        if (!(bool) $options['dryRun'] && $stats['success'] > 0) {
            $this->generateAndNotifyReport($stats, $options, $io);
        }
    }

    /**
     * 显示同步结果
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $options
     */
    private function displaySyncResults(array $stats, array $options, SymfonyStyle $io): void
    {
        $io->section('同步结果统计');
        $io->definitionList(
            ['总计' => $stats['total']],
            ['成功' => $stats['success']],
            ['失败' => $stats['failure']],
            ['跳过' => $stats['skipped']],
            ['新建' => $stats['created']],
            ['更新' => $stats['updated']],
            ['批处理大小' => $options['batchSize']],
            ['数据源' => $options['source']],
            ['同步时间' => date('Y-m-d H:i:s')]
        );

        assert(is_int($stats['failure'] ?? 0));
        $failureCount = $stats['failure'] ?? 0;
        if ($failureCount > 0) {
            $io->warning("有 {$failureCount} 条数据同步失败");
        } else {
            $io->success('所有数据同步成功');
        }
    }

    /**
     * 生成报告并通知
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $options
     */
    private function generateAndNotifyReport(array $stats, array $options, SymfonyStyle $io): void
    {
        $reportData = array_merge($stats, [
            'source' => $options['source'],
            'sync_time' => date('Y-m-d H:i:s'),
        ]);

        $this->generateSyncReport($reportData);
        $io->note('同步报告已生成');
    }

    /**
     * 处理同步错误
     */
    private function handleSyncError(\Throwable $e, SymfonyStyle $io): int
    {
        $io->error('执行过程中发生错误: ' . $e->getMessage());

        return Command::FAILURE;
    }

    /**
     * 获取同步数据
     * @return array<array<string, mixed>>
     */
    private function getSyncData(string $source): array
    {
        // 模拟获取同步数据
        // 实际实现中应该从指定的数据源获取数据
        switch ($source) {
            case 'api':
                return $this->getSyncDataFromApi();
            case 'file':
                return $this->getSyncDataFromFile();
            case 'database':
            default:
                return $this->getSyncDataFromDatabase();
        }
    }

    /**
     * 从API获取同步数据
     * @return array<array<string, mixed>>
     */
    private function getSyncDataFromApi(): array
    {
        // 模拟API数据
        return [];
    }

    /**
     * 从文件获取同步数据
     * @return array<array<string, mixed>>
     */
    private function getSyncDataFromFile(): array
    {
        // 模拟文件数据
        return [];
    }

    /**
     * 从数据库获取同步数据
     * @return array<array<string, mixed>>
     */
    private function getSyncDataFromDatabase(): array
    {
        // 模拟数据库数据
        return [];
    }

    /**
     * 同步机构数据
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function syncInstitutionData(array $data, bool $force): array
    {
        // 检查机构是否已存在
        $existingInstitution = null;
        if (isset($data['code'])) {
            $institutions = $this->institutionService->searchInstitutions(['code' => $data['code']]);
            $existingInstitution = [] !== $institutions ? $institutions[0] : null;
        }

        if (null !== $existingInstitution) {
            if ($force) {
                // 更新现有机构
                $this->institutionService->updateInstitution($existingInstitution->getId(), $data);

                return ['action' => 'updated', 'institution_id' => $existingInstitution->getId()];
            }

            // 跳过已存在的机构
            return ['action' => 'skipped', 'institution_id' => $existingInstitution->getId()];
        }
        // 创建新机构
        $institution = $this->institutionService->createInstitution($data);

        return ['action' => 'created', 'institution_id' => $institution->getId()];
    }

    /**
     * 验证同步数据
     * @param array<string, mixed> $data
     */
    private function validateSyncData(array $data): void
    {
        $this->institutionService->validateInstitutionData($data);
    }

    /**
     * 生成同步报告
     * @param array<string, mixed> $stats
     */
    private function generateSyncReport(array $stats): void
    {
        // 这里可以生成详细的同步报告
        // 例如保存到文件、发送邮件等
    }
}
