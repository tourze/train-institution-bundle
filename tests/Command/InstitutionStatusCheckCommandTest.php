<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TrainInstitutionBundle\Command\InstitutionStatusCheckCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

/**
 * InstitutionStatusCheckCommand 单元测试
 */
class InstitutionStatusCheckCommandTest extends TestCase
{
    private InstitutionStatusCheckCommand $command;
    private MockObject&InstitutionService $institutionService;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->institutionService = $this->createMock(InstitutionService::class);
        $this->command = new InstitutionStatusCheckCommand($this->institutionService);

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * 创建测试机构
     */
    private function createTestInstitution(string $name, string $code, string $status = '正常运营'): MockObject&Institution
    {
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn('inst-' . uniqid());
        $institution->method('getInstitutionName')->willReturn($name);
        $institution->method('getInstitutionCode')->willReturn($code);
        $institution->method('getInstitutionStatus')->willReturn($status);
        
        return $institution;
    }

    /**
     * 测试命令配置
     */
    public function test_commandConfiguration(): void
    {
        $this->assertEquals(InstitutionStatusCheckCommand::NAME, $this->command->getName());
        $this->assertEquals('检查培训机构状态和合规性', $this->command->getDescription());
        
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('status'));
        $this->assertTrue($definition->hasOption('institution-id'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('format'));
        $this->assertTrue($definition->hasOption('compliance-only'));
    }

    /**
     * 测试没有找到符合条件的机构
     */
    public function test_execute_withNoInstitutions_returnsSuccess(): void
    {
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->with('正常运营')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有找到符合条件的机构', $output);
    }

    /**
     * 测试检查所有正常运营的机构
     */
    public function test_execute_withDefaultOptions_checksAllOperatingInstitutions(): void
    {
        $institution1 = $this->createTestInstitution('机构A', 'INST001');
        $institution2 = $this->createTestInstitution('机构B', 'INST002');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->with('正常运营')
            ->willReturn([$institution1, $institution2]);

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质即将过期']);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('检查所有正常运营的机构', $output);
        $this->assertStringContainsString('检查 2 个机构', $output);
        $this->assertStringContainsString('机构A', $output);
        $this->assertStringContainsString('机构B', $output);
    }

    /**
     * 测试检查指定状态的机构
     */
    public function test_execute_withStatusOption_checksSpecificStatusInstitutions(): void
    {
        $institution = $this->createTestInstitution('待审核机构', 'INST003', '待审核');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->with('待审核')
            ->willReturn([$institution]);

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute(['--status' => '待审核']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("检查状态为 '待审核' 的机构", $output);
        $this->assertStringContainsString('待审核机构', $output);
    }

    /**
     * 测试检查指定ID的机构
     */
    public function test_execute_withInstitutionIdOption_checksSpecificInstitution(): void
    {
        $institutionId = 'specific-institution-id';
        $institution = $this->createTestInstitution('指定机构', 'INST004');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution);

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute(['--institution-id' => $institutionId]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('指定机构', $output);
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
        $institution = $this->createTestInstitution('测试机构', 'TEST001');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution]);

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn(['问题1', '问题2']);

        $exitCode = $this->commandTester->execute(['--format' => 'json']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        
        // 提取JSON部分（可能前面有其他输出）
        $jsonStart = strpos($output, '{');
        $this->assertNotFalse($jsonStart, 'JSON output not found');
        $jsonOutput = substr($output, $jsonStart);
        
        // 验证JSON格式
        $jsonData = json_decode($jsonOutput, true);
        $this->assertNotNull($jsonData);
        $this->assertArrayHasKey('summary', $jsonData);
        $this->assertArrayHasKey('results', $jsonData);
        $this->assertEquals(1, $jsonData['summary']['total_institutions']);
        $this->assertEquals(0, $jsonData['summary']['compliant']);
        $this->assertEquals(1, $jsonData['summary']['non_compliant']);
        $this->assertEquals(2, $jsonData['summary']['total_issues']);
    }

    /**
     * 测试摘要格式输出
     */
    public function test_execute_withSummaryFormat_outputsSummaryData(): void
    {
        $institution1 = $this->createTestInstitution('合规机构', 'COMP001');
        $institution2 = $this->createTestInstitution('不合规机构', 'NONCOMP001');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2]);

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质过期', '设施不足']);

        $exitCode = $this->commandTester->execute(['--format' => 'summary']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('检查结果摘要', $output);
        $this->assertStringContainsString('检查机构总数', $output);
        $this->assertStringContainsString('合规机构数量', $output);
        $this->assertStringContainsString('不合规机构数量', $output);
        $this->assertStringContainsString('不合规机构列表', $output);
        $this->assertStringContainsString('不合规机构', $output);
    }

    /**
     * 测试只检查合规性问题
     */
    public function test_execute_withComplianceOnlyOption_showsOnlyNonCompliantInstitutions(): void
    {
        $institution1 = $this->createTestInstitution('合规机构', 'COMP001');
        $institution2 = $this->createTestInstitution('不合规机构', 'NONCOMP001');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2]);

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质过期']);

        $exitCode = $this->commandTester->execute(['--compliance-only' => true]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('发现 1 个机构存在合规问题', $output);
        $this->assertStringContainsString('不合规机构 - 合规问题', $output);
        $this->assertStringContainsString('资质过期', $output);
    }

    /**
     * 测试所有机构都合规的情况
     */
    public function test_execute_withAllCompliantInstitutions_showsSuccessMessage(): void
    {
        $institution1 = $this->createTestInstitution('机构A', 'INST001');
        $institution2 = $this->createTestInstitution('机构B', 'INST002');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2]);

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('所有机构都符合合规要求', $output);
        $this->assertStringContainsString('合规率', $output);
        $this->assertStringContainsString('100%', $output);
    }

    /**
     * 测试有不合规机构的情况
     */
    public function test_execute_withNonCompliantInstitutions_showsWarningAndSuggestions(): void
    {
        $institution1 = $this->createTestInstitution('合规机构', 'COMP001');
        $institution2 = $this->createTestInstitution('不合规机构', 'NONCOMP001');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2]);

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质过期', '设施不足']);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('发现 1 个机构存在合规问题', $output);
        $this->assertStringContainsString('建议：', $output);
        $this->assertStringContainsString('联系相关机构负责人', $output);
        $this->assertStringContainsString('安排专人跟进', $output);
    }

    /**
     * 测试干运行模式
     */
    public function test_execute_withDryRunOption_showsDryRunNote(): void
    {
        $institution = $this->createTestInstitution('测试机构', 'TEST001');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution]);

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('运行在干运行模式', $output);
    }

    /**
     * 测试表格格式输出（默认）
     */
    public function test_execute_withTableFormat_displaysTableOutput(): void
    {
        $institution1 = $this->createTestInstitution('机构A', 'INST001');
        $institution2 = $this->createTestInstitution('机构B', 'INST002');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2]);

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['问题1']);

        $exitCode = $this->commandTester->execute(['--format' => 'table']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('机构名称', $output);
        $this->assertStringContainsString('机构代码', $output);
        $this->assertStringContainsString('合规性', $output);
        $this->assertStringContainsString('问题数量', $output);
        $this->assertStringContainsString('机构A', $output);
        $this->assertStringContainsString('机构B', $output);
        $this->assertStringContainsString('合规', $output);
        $this->assertStringContainsString('不合规', $output);
    }

    /**
     * 测试异常处理
     */
    public function test_execute_withException_returnsFailure(): void
    {
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willThrowException(new \RuntimeException('数据库连接失败'));

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('执行过程中发生错误', $output);
        $this->assertStringContainsString('数据库连接失败', $output);
    }

    /**
     * 测试合规率计算
     */
    public function test_execute_calculatesCorrectComplianceRate(): void
    {
        $institutions = [
            $this->createTestInstitution('机构1', 'INST001'),
            $this->createTestInstitution('机构2', 'INST002'),
            $this->createTestInstitution('机构3', 'INST003'),
            $this->createTestInstitution('机构4', 'INST004'),
        ];
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn($institutions);

        // 3个合规，1个不合规，合规率应该是75%
        $this->institutionService
            ->expects($this->exactly(4))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], [], [], ['问题1']);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('75%', $output);
    }

    /**
     * 测试只有合规问题的机构显示（compliance-only模式）
     */
    public function test_execute_withComplianceOnlyAndAllCompliant_showsSuccessMessage(): void
    {
        $institution = $this->createTestInstitution('合规机构', 'COMP001');
        
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution]);

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute(['--compliance-only' => true]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('所有机构都符合合规要求', $output);
    }
} 