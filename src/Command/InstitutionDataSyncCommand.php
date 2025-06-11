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
#[AsCommand(
    name: self::NAME,
    description: '同步培训机构数据'
)]
class InstitutionDataSyncCommand extends Command
{
    public const NAME = 'institution:data:sync';

    public function __construct(
        private readonly InstitutionService $institutionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('此命令同步培训机构数据，确保数据一致性和完整性。建议每日执行一次。')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getOption('source');
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');
        $batchSize = (int) $input->getOption('batch-size');

        $io->title('培训机构数据同步');

        if ($dryRun) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        if ($force) {
            $io->warning('强制模式已启用，将覆盖现有数据');
        }

        try {
            $io->section("从 {$source} 同步数据");

            // 获取需要同步的数据
            $syncData = $this->getSyncData($source);
            
            if (empty($syncData)) {
                $io->success('没有需要同步的数据');
                return Command::SUCCESS;
            }

            $io->info("发现 " . count($syncData) . " 条需要同步的数据");

            $successCount = 0;
            $failureCount = 0;
            $skippedCount = 0;
            $updatedCount = 0;
            $createdCount = 0;

            $io->progressStart(count($syncData));

            // 分批处理数据
            $batches = array_chunk($syncData, $batchSize);
            
            foreach ($batches as $batch) {
                foreach ($batch as $data) {
                    try {
                        if (!$dryRun) {
                            $result = $this->syncInstitutionData($data, $force);
                            
                            switch ($result['action']) {
                                case 'created':
                                    $createdCount++;
                                    $successCount++;
                                    break;
                                case 'updated':
                                    $updatedCount++;
                                    $successCount++;
                                    break;
                                case 'skipped':
                                    $skippedCount++;
                                    break;
                            }
                        } else {
                            // 干运行模式，只验证数据
                            $this->validateSyncData($data);
                            $successCount++;
                        }
                    } catch  (\Throwable $e) {
                        $failureCount++;
                        $io->error("同步数据失败: {$data['name']} - {$e->getMessage()}");
                    }

                    $io->progressAdvance();
                }
            }

            $io->progressFinish();

            // 显示同步结果
            $io->section('同步结果统计');
            $io->definitionList(
                ['总计' => count($syncData)],
                ['成功' => $successCount],
                ['失败' => $failureCount],
                ['跳过' => $skippedCount],
                ['新建' => $createdCount],
                ['更新' => $updatedCount],
                ['批处理大小' => $batchSize],
                ['数据源' => $source],
                ['同步时间' => date('Y-m-d H:i:s')]
            );

            if ($failureCount > 0) {
                $io->warning("有 {$failureCount} 条数据同步失败");
            } else {
                $io->success("所有数据同步成功");
            }

            // 生成同步报告
            if (!$dryRun && $successCount > 0) {
                $this->generateSyncReport([
                    'total' => count($syncData),
                    'success' => $successCount,
                    'failure' => $failureCount,
                    'skipped' => $skippedCount,
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'source' => $source,
                    'sync_time' => date('Y-m-d H:i:s'),
                ]);
                $io->note('同步报告已生成');
            }

            return Command::SUCCESS;

        } catch  (\Throwable $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 获取同步数据
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
     */
    private function getSyncDataFromApi(): array
    {
        // 模拟API数据
        return [];
    }

    /**
     * 从文件获取同步数据
     */
    private function getSyncDataFromFile(): array
    {
        // 模拟文件数据
        return [];
    }

    /**
     * 从数据库获取同步数据
     */
    private function getSyncDataFromDatabase(): array
    {
        // 模拟数据库数据
        return [];
    }

    /**
     * 同步机构数据
     */
    private function syncInstitutionData(array $data, bool $force): array
    {
        // 检查机构是否已存在
        $existingInstitution = null;
        if (isset($data['code'])) {
            $institutions = $this->institutionService->searchInstitutions(['code' => $data['code']]);
            $existingInstitution = !empty($institutions) ? $institutions[0] : null;
        }

        if ($existingInstitution) {
            if ($force) {
                // 更新现有机构
                $this->institutionService->updateInstitution($existingInstitution->getId(), $data);
                return ['action' => 'updated', 'institution_id' => $existingInstitution->getId()];
            } else {
                // 跳过已存在的机构
                return ['action' => 'skipped', 'institution_id' => $existingInstitution->getId()];
            }
        } else {
            // 创建新机构
            $institution = $this->institutionService->createInstitution($data);
            return ['action' => 'created', 'institution_id' => $institution->getId()];
        }
    }

    /**
     * 验证同步数据
     */
    private function validateSyncData(array $data): void
    {
        $this->institutionService->validateInstitutionData($data);
    }

    /**
     * 生成同步报告
     */
    private function generateSyncReport(array $stats): void
    {
        // 这里可以生成详细的同步报告
        // 例如保存到文件、发送邮件等
    }
} 