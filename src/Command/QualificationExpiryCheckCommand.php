<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * 资质到期检查命令
 *
 * 检查即将到期的培训机构资质证书，生成提醒报告
 * 建议每日执行一次（cron: 0 9 * * *）
 */
#[AsCommand(name: self::NAME, description: '检查即将到期的培训机构资质证书')]
final class QualificationExpiryCheckCommand extends Command
{
    public const NAME = 'institution:qualification:expiry-check';

    public function __construct(
        private readonly QualificationService $qualificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
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
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $params = $this->parseAndValidateParameters($input, $io);
            $expiringQualifications = $this->findExpiringQualifications($io, $params['days']);

            if ([] === $expiringQualifications) {
                $io->success("未发现{$params['days']}天内到期的资质");

                return Command::SUCCESS;
            }

            $processedData = $this->processQualificationData($expiringQualifications);
            $this->displayResults($output, $io, $processedData, $params);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('执行过程中发生错误: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * 解析和验证命令参数
     * @return array{days: int, dry_run: bool, format: string}
     */
    private function parseAndValidateParameters(InputInterface $input, SymfonyStyle $io): array
    {
        $daysOption = $input->getOption('days');
        $days = is_numeric($daysOption) ? (int) $daysOption : 30;
        $dryRun = (bool) $input->getOption('dry-run');
        $formatOption = $input->getOption('format');
        $format = is_string($formatOption) ? $formatOption : 'table';

        $io->title('培训机构资质到期检查');

        if ($dryRun) {
            $io->note('运行在干运行模式，不会执行实际操作');
        }

        return [
            'days' => $days,
            'dry_run' => $dryRun,
            'format' => $format,
        ];
    }

    /**
     * 查找即将到期的资质
     * @return array<InstitutionQualification>
     */
    private function findExpiringQualifications(SymfonyStyle $io, int $days): array
    {
        $io->section("检查{$days}天内到期的资质");
        $expiringQualifications = $this->qualificationService->getExpiringQualifications($days);

        if ([] !== $expiringQualifications) {
            $io->warning('发现 ' . count($expiringQualifications) . ' 个即将到期的资质');
        }

        return $expiringQualifications;
    }

    /**
     * 处理资质数据，生成统计信息
     * @param array<InstitutionQualification> $expiringQualifications
     * @return array{table_data: array<int, array<int, string|int>>, critical_count: int, warning_count: int, total_count: int, qualifications: array<InstitutionQualification>}
     */
    private function processQualificationData(array $expiringQualifications): array
    {
        $tableData = [];
        $criticalCount = 0; // 7天内到期
        $warningCount = 0;  // 30天内到期

        foreach ($expiringQualifications as $qualification) {
            $institution = $qualification->getInstitution();
            if (null === $institution) {
                continue;
            }

            $remainingDays = $qualification->getRemainingDays();
            $status = $remainingDays <= 7 ? '紧急' : '警告';

            if ($remainingDays <= 7) {
                ++$criticalCount;
            } else {
                ++$warningCount;
            }

            $tableData[] = [
                $institution->getInstitutionName(),
                $qualification->getQualificationName(),
                $qualification->getCertificateNumber(),
                $qualification->getValidTo()->format('Y-m-d'),
                $remainingDays,
                $status,
            ];
        }

        return [
            'table_data' => $tableData,
            'critical_count' => $criticalCount,
            'warning_count' => $warningCount,
            'total_count' => count($expiringQualifications),
            'qualifications' => $expiringQualifications,
        ];
    }

    /**
     * 根据格式显示结果
     * @param array{table_data: array<int, array<int, string|int>>, critical_count: int, warning_count: int, total_count: int, qualifications: array<InstitutionQualification>} $processedData
     * @param array{days: int, dry_run: bool, format: string} $params
     */
    private function displayResults(OutputInterface $output, SymfonyStyle $io, array $processedData, array $params): void
    {
        $format = $params['format'];

        switch ($format) {
            case 'json':
                $this->outputJsonFormat($output, $processedData, $params['days']);
                break;
            case 'csv':
                $this->outputCsvFormat($output, $processedData['table_data']);
                break;
            case 'table':
            default:
                $this->outputTableFormat($io, $processedData, $params['days']);
                break;
        }
    }

    /**
     * 输出JSON格式
     * @param array{table_data: array<int, array<int, string|int>>, critical_count: int, warning_count: int, total_count: int, qualifications: array<InstitutionQualification>} $processedData
     */
    private function outputJsonFormat(OutputInterface $output, array $processedData, int $days): void
    {
        $jsonData = [
            'summary' => [
                'total' => $processedData['total_count'],
                'critical' => $processedData['critical_count'],
                'warning' => $processedData['warning_count'],
                'check_days' => $days,
                'check_time' => date('Y-m-d H:i:s'),
            ],
            'qualifications' => array_map(function (InstitutionQualification $q): array {
                $institution = $q->getInstitution();
                $institutionName = null !== $institution ? $institution->getInstitutionName() : 'Unknown';

                return [
                    'institution_name' => $institutionName,
                    'qualification_name' => $q->getQualificationName(),
                    'certificate_number' => $q->getCertificateNumber(),
                    'valid_to' => $q->getValidTo()->format('Y-m-d'),
                    'remaining_days' => $q->getRemainingDays(),
                    'status' => $q->getRemainingDays() <= 7 ? 'critical' : 'warning',
                ];
            }, $processedData['qualifications']),
        ];

        $jsonResult = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $output->writeln(false !== $jsonResult ? $jsonResult : '{}');
    }

    /**
     * 输出CSV格式
     * @param array<int, array<int, string|int>> $tableData
     */
    private function outputCsvFormat(OutputInterface $output, array $tableData): void
    {
        $output->writeln('机构名称,资质名称,证书编号,到期日期,剩余天数,状态');
        foreach ($tableData as $row) {
            $csvRow = array_map(fn ($value): string => is_string($value) ? $value : (string) $value, $row);
            $output->writeln(implode(',', $csvRow));
        }
    }

    /**
     * 输出表格格式并显示统计信息
     * @param array{table_data: array<int, array<int, string|int>>, critical_count: int, warning_count: int, total_count: int, qualifications: array<InstitutionQualification>} $processedData
     */
    private function outputTableFormat(SymfonyStyle $io, array $processedData, int $days): void
    {
        $io->table(
            ['机构名称', '资质名称', '证书编号', '到期日期', '剩余天数', '状态'],
            $processedData['table_data']
        );

        $this->displayStatistics($io, $processedData, $days);
        $this->displayRecommendations($io, $processedData);
    }

    /**
     * 显示统计信息
     * @param array{table_data: array<int, array<int, string|int>>, critical_count: int, warning_count: int, total_count: int, qualifications: array<InstitutionQualification>} $processedData
     */
    private function displayStatistics(SymfonyStyle $io, array $processedData, int $days): void
    {
        $io->section('统计信息');
        $io->definitionList(
            ['总计' => $processedData['total_count']],
            ['紧急（7天内）' => $processedData['critical_count']],
            ['警告（30天内）' => $processedData['warning_count']],
            ['检查范围' => "{$days}天内"],
            ['检查时间' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * 显示建议信息
     * @param array{table_data: array<int, array<int, string|int>>, critical_count: int, warning_count: int, total_count: int, qualifications: array<InstitutionQualification>} $processedData
     */
    private function displayRecommendations(SymfonyStyle $io, array $processedData): void
    {
        $criticalCount = $processedData['critical_count'];
        $warningCount = $processedData['warning_count'];

        if ($criticalCount > 0) {
            $io->error("有 {$criticalCount} 个资质将在7天内到期，请立即处理！");
        }
        if ($warningCount > 0) {
            $io->warning("有 {$warningCount} 个资质将在30天内到期，请及时安排续期。");
        }
    }
}
