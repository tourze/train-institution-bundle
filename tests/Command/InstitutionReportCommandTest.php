<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainInstitutionBundle\Command\InstitutionReportCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;
use Tourze\TrainInstitutionBundle\Service\FacilityService;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * InstitutionReportCommand 单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionReportCommand::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionReportCommandTest extends AbstractCommandTestCase
{
    private MockObject&InstitutionService $institutionService;

    private MockObject&QualificationService $qualificationService;

    private MockObject&FacilityService $facilityService;

    private MockObject&ChangeRecordService $changeRecordService;

    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        // 创建Mock服务并注册到容器
        $this->institutionService = $this->createMock(InstitutionService::class);
        $this->qualificationService = $this->createMock(QualificationService::class);
        $this->facilityService = $this->createMock(FacilityService::class);
        $this->changeRecordService = $this->createMock(ChangeRecordService::class);

        self::getContainer()->set(InstitutionService::class, $this->institutionService);
        self::getContainer()->set(QualificationService::class, $this->qualificationService);
        self::getContainer()->set(FacilityService::class, $this->facilityService);
        self::getContainer()->set(ChangeRecordService::class, $this->changeRecordService);

        // 从容器获取命令实例
        $command = self::getService(InstitutionReportCommand::class);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    /**
     * 创建测试机构
     */
    private function createTestInstitution(): MockObject&Institution
    {
        /*
         * 使用具体类 Institution 进行 Mock：
         * 1) 为什么必须使用具体类：Institution 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，在测试中需要创建可控的实体对象，避免依赖数据库持久化
         * 3) 更好的替代方案：可以使用 Entity 工厂或 Builder 模式，但 Mock 在单元测试中更加灵活可控
         */
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn('test-institution-id');
        $institution->method('getInstitutionName')->willReturn('测试培训机构');
        $institution->method('getInstitutionCode')->willReturn('TEST001');
        $institution->method('getInstitutionType')->willReturn('企业培训机构');
        $institution->method('getInstitutionStatus')->willReturn('正常运营');
        $institution->method('getLegalPerson')->willReturn('张三');
        $institution->method('getContactPerson')->willReturn('李四');
        $institution->method('getAddress')->willReturn('北京市朝阳区测试路123号');
        $institution->method('getEstablishDate')->willReturn(new \DateTimeImmutable('2020-01-01'));

        return $institution;
    }

    /**
     * 测试命令配置
     */
    public function testCommandConfiguration(): void
    {
        $command = self::getService(InstitutionReportCommand::class);
        self::assertEquals(InstitutionReportCommand::NAME, $command->getName());
        self::assertEquals('生成培训机构综合报告', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('institution-id'));
        self::assertTrue($definition->hasOption('type'));
        self::assertTrue($definition->hasOption('format'));
        self::assertTrue($definition->hasOption('output-file'));
        self::assertTrue($definition->hasOption('date-range'));
    }

    /**
     * 测试生成全局摘要报告（默认）
     */
    public function testExecuteWithDefaultOptionsGeneratesGlobalSummaryReport(): void
    {
        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('培训机构报告生成', $output);
        self::assertStringContainsString('生成全局统计报告', $output);
    }

    /**
     * 测试生成指定机构的报告
     */
    public function testExecuteWithInstitutionIdGeneratesSpecificInstitutionReport(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution)
        ;

        // Mock其他服务的调用（summary报告类型只需要基本统计）
        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->with($institutionId)
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]])
        ;

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute(['--institution-id' => $institutionId]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('生成机构报告：测试培训机构', $output);
    }

    /**
     * 测试指定机构ID不存在
     */
    public function testExecuteWithNonExistentInstitutionIdReturnsFailure(): void
    {
        $institutionId = 'non-existent-id';

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn(null)
        ;

        $exitCode = $this->commandTester->execute(['--institution-id' => $institutionId]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString("未找到ID为 {$institutionId} 的机构", $output);
    }

    /**
     * 测试JSON格式输出
     */
    public function testExecuteWithJsonFormatOutputsJsonData(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution)
        ;

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]])
        ;

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'json',
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();

        // 提取JSON部分
        $jsonStart = strpos($output, '{');
        self::assertNotFalse($jsonStart, 'JSON output not found');
        $jsonOutput = substr($output, $jsonStart);

        // 验证JSON格式
        /** @var array<string, mixed>|null $jsonData */
        $jsonData = json_decode($jsonOutput, true);
        self::assertNotNull($jsonData);
        self::assertIsArray($jsonData);
        self::assertArrayHasKey('institution', $jsonData);
        self::assertArrayHasKey('generated_at', $jsonData);
        self::assertIsArray($jsonData['institution']);
        self::assertArrayHasKey('name', $jsonData['institution']);
        self::assertEquals('测试培训机构', $jsonData['institution']['name']);
    }

    /**
     * 测试输出到文件
     */
    public function testExecuteWithOutputFileSavesToFile(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';
        $outputFile = sys_get_temp_dir() . '/test_report.json';

        // 确保文件不存在
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution)
        ;

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]])
        ;

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'json',
            '--output-file' => $outputFile,
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString("报告已保存到文件：{$outputFile}", $output);
        self::assertFileExists($outputFile);

        // 验证文件内容
        $fileContent = file_get_contents($outputFile);
        self::assertNotFalse($fileContent);
        /** @var array<string, mixed>|null $jsonData */
        $jsonData = json_decode($fileContent, true);
        self::assertNotNull($jsonData);
        self::assertIsArray($jsonData);
        self::assertArrayHasKey('institution', $jsonData);

        // 清理文件
        unlink($outputFile);
    }

    /**
     * 测试日期范围过滤
     */
    public function testExecuteWithDateRangeFiltersDataByDateRange(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution)
        ;

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('checkQualificationExpiry')
            ->willReturn([])
        ;

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]])
        ;

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangesByDateRange')
            ->with($institutionId, self::isInstanceOf(\DateTimeImmutable::class), self::isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([])
        ;

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--type' => 'detailed',
            '--date-range' => '2024-01-01,2024-12-31',
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('统计日期范围：2024-01-01 至 2024-12-31', $output);
    }

    /**
     * 测试CSV格式输出
     */
    public function testExecuteWithCsvFormatOutputsCsvData(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution)
        ;

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]])
        ;

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'csv',
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('报告类型,summary', $output);
        self::assertStringContainsString('机构名称,测试培训机构', $output);
    }

    /**
     * 测试HTML格式输出
     */
    public function testExecuteWithHtmlFormatOutputsHtmlData(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution)
        ;

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]])
        ;

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0])
        ;

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'html',
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('<html>', $output);
        self::assertStringContainsString('<title>培训机构报告</title>', $output);
        self::assertStringContainsString('测试培训机构', $output);
    }

    /**
     * 测试异常处理
     */
    public function testExecuteWithExceptionReturnsFailure(): void
    {
        // 模拟getInstitutionById抛出异常
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionById')
            ->with('invalid-id')
            ->willThrowException(new \RuntimeException('数据库连接失败'))
        ;

        $exitCode = $this->commandTester->execute(['--institution-id' => 'invalid-id']);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('执行过程中发生错误', $output);
        self::assertStringContainsString('数据库连接失败', $output);
    }

    /**
     * 测试 --institution-id 选项
     */
    public function testOptionInstitutionId(): void
    {
        $command = self::getService(InstitutionReportCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('institution-id'),
            'Command should have --institution-id option'
        );
    }

    /**
     * 测试 --type 选项
     */
    public function testOptionType(): void
    {
        $command = self::getService(InstitutionReportCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('type'),
            'Command should have --type option'
        );
    }

    /**
     * 测试 --format 选项
     */
    public function testOptionFormat(): void
    {
        $command = self::getService(InstitutionReportCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('format'),
            'Command should have --format option'
        );
    }

    /**
     * 测试 --output-file 选项
     */
    public function testOptionOutputFile(): void
    {
        $command = self::getService(InstitutionReportCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('output-file'),
            'Command should have --output-file option'
        );
    }

    /**
     * 测试 --date-range 选项
     */
    public function testOptionDateRange(): void
    {
        $command = self::getService(InstitutionReportCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('date-range'),
            'Command should have --date-range option'
        );
    }
}
