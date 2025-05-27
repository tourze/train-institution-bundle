<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TrainInstitutionBundle\Command\InstitutionReportCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;
use Tourze\TrainInstitutionBundle\Service\FacilityService;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * InstitutionReportCommand 单元测试
 */
class InstitutionReportCommandTest extends TestCase
{
    private InstitutionReportCommand $command;
    private MockObject&InstitutionService $institutionService;
    private MockObject&QualificationService $qualificationService;
    private MockObject&FacilityService $facilityService;
    private MockObject&ChangeRecordService $changeRecordService;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->institutionService = $this->createMock(InstitutionService::class);
        $this->qualificationService = $this->createMock(QualificationService::class);
        $this->facilityService = $this->createMock(FacilityService::class);
        $this->changeRecordService = $this->createMock(ChangeRecordService::class);
        
        $this->command = new InstitutionReportCommand(
            $this->institutionService,
            $this->qualificationService,
            $this->facilityService,
            $this->changeRecordService
        );

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * 创建测试机构
     */
    private function createTestInstitution(): MockObject&Institution
    {
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
    public function test_commandConfiguration(): void
    {
        $this->assertEquals(InstitutionReportCommand::NAME, $this->command->getName());
        $this->assertEquals('生成培训机构综合报告', $this->command->getDescription());
        
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('institution-id'));
        $this->assertTrue($definition->hasOption('type'));
        $this->assertTrue($definition->hasOption('format'));
        $this->assertTrue($definition->hasOption('output-file'));
        $this->assertTrue($definition->hasOption('date-range'));
    }

    /**
     * 测试生成全局摘要报告（默认）
     */
    public function test_execute_withDefaultOptions_generatesGlobalSummaryReport(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('培训机构报告生成', $output);
        $this->assertStringContainsString('生成全局统计报告', $output);
    }

    /**
     * 测试生成指定机构的报告
     */
    public function test_execute_withInstitutionId_generatesSpecificInstitutionReport(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution);

        // Mock其他服务的调用（summary报告类型只需要基本统计）
        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0]);

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->with($institutionId)
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]]);

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0]);

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute(['--institution-id' => $institutionId]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('生成机构报告：测试培训机构', $output);
    }

    /**
     * 测试指定机构ID不存在
     */
    public function test_execute_withNonExistentInstitutionId_returnsFailure(): void
    {
        $institutionId = 'non-existent-id';

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn(null);

        $exitCode = $this->commandTester->execute(['--institution-id' => $institutionId]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("未找到ID为 {$institutionId} 的机构", $output);
    }



    /**
     * 测试JSON格式输出
     */
    public function test_execute_withJsonFormat_outputsJsonData(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution);

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0]);

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]]);

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0]);

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'json'
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        
        // 提取JSON部分
        $jsonStart = strpos($output, '{');
        $this->assertNotFalse($jsonStart, 'JSON output not found');
        $jsonOutput = substr($output, $jsonStart);
        
        // 验证JSON格式
        $jsonData = json_decode($jsonOutput, true);
        $this->assertNotNull($jsonData);
        $this->assertArrayHasKey('institution', $jsonData);
        $this->assertArrayHasKey('generated_at', $jsonData);
        $this->assertEquals('测试培训机构', $jsonData['institution']['name']);
    }

    /**
     * 测试输出到文件
     */
    public function test_execute_withOutputFile_savesToFile(): void
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
            ->willReturn($institution);

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0]);

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]]);

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0]);

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'json',
            '--output-file' => $outputFile
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("报告已保存到文件：{$outputFile}", $output);
        $this->assertFileExists($outputFile);

        // 验证文件内容
        $fileContent = file_get_contents($outputFile);
        $jsonData = json_decode($fileContent, true);
        $this->assertNotNull($jsonData);
        $this->assertArrayHasKey('institution', $jsonData);

        // 清理文件
        unlink($outputFile);
    }

    /**
     * 测试日期范围过滤
     */
    public function test_execute_withDateRange_filtersDataByDateRange(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution);

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('checkQualificationExpiry')
            ->willReturn([]);

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0]);

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]]);

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangesByDateRange')
            ->with($institutionId, $this->isInstanceOf(\DateTimeImmutable::class), $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([]);

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--type' => 'detailed',
            '--date-range' => '2024-01-01,2024-12-31'
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('统计日期范围：2024-01-01 至 2024-12-31', $output);
    }

    /**
     * 测试CSV格式输出
     */
    public function test_execute_withCsvFormat_outputsCsvData(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution);

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0]);

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]]);

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0]);

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'csv'
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('报告类型,summary', $output);
        $this->assertStringContainsString('机构名称,测试培训机构', $output);
    }

    /**
     * 测试HTML格式输出
     */
    public function test_execute_withHtmlFormat_outputsHtmlData(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution);

        $this->qualificationService
            ->expects($this->atLeastOnce())
            ->method('getQualificationStatistics')
            ->willReturn(['total' => 0]);

        $this->facilityService
            ->expects($this->atLeastOnce())
            ->method('generateFacilityReport')
            ->willReturn(['summary' => ['total_facilities' => 0, 'total_area' => 0]]);

        $this->changeRecordService
            ->expects($this->atLeastOnce())
            ->method('getChangeStatistics')
            ->willReturn(['total' => 0]);

        $this->institutionService
            ->expects($this->atLeastOnce())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([
            '--institution-id' => $institutionId,
            '--format' => 'html'
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('<html>', $output);
        $this->assertStringContainsString('<title>培训机构报告</title>', $output);
        $this->assertStringContainsString('测试培训机构', $output);
    }

    /**
     * 测试异常处理
     */
    public function test_execute_withException_returnsFailure(): void
    {
        // 模拟getInstitutionById抛出异常
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionById')
            ->with('invalid-id')
            ->willThrowException(new \RuntimeException('数据库连接失败'));

        $exitCode = $this->commandTester->execute(['--institution-id' => 'invalid-id']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('执行过程中发生错误', $output);
        $this->assertStringContainsString('数据库连接失败', $output);
    }
} 