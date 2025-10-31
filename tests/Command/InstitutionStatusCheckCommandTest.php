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
use Tourze\TrainInstitutionBundle\Command\InstitutionStatusCheckCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

/**
 * InstitutionStatusCheckCommand 单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionStatusCheckCommand::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionStatusCheckCommandTest extends AbstractCommandTestCase
{
    private MockObject&InstitutionService $institutionService;

    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        // 创建Mock服务并注册到容器
        $this->institutionService = $this->createMock(InstitutionService::class);
        self::getContainer()->set(InstitutionService::class, $this->institutionService);

        // 从容器获取命令实例
        $command = self::getService(InstitutionStatusCheckCommand::class);

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
    private function createTestInstitution(string $name, string $code, string $status = '正常运营'): MockObject&Institution
    {
        /*
         * 使用具体类 Institution 进行 Mock：
         * 1) 为什么必须使用具体类：Institution 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，在测试中需要创建可控的实体对象，避免依赖数据库持久化
         * 3) 更好的替代方案：可以使用 Entity 工厂或 Builder 模式，但 Mock 在单元测试中更加灵活可控
         */
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
    public function testCommandConfiguration(): void
    {
        $command = self::getService(InstitutionStatusCheckCommand::class);
        self::assertEquals(InstitutionStatusCheckCommand::NAME, $command->getName());
        self::assertEquals('检查培训机构状态和合规性', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('status'));
        self::assertTrue($definition->hasOption('institution-id'));
        self::assertTrue($definition->hasOption('dry-run'));
        self::assertTrue($definition->hasOption('format'));
        self::assertTrue($definition->hasOption('compliance-only'));
    }

    /**
     * 测试没有找到符合条件的机构
     */
    public function testExecuteWithNoInstitutionsReturnsSuccess(): void
    {
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->with('正常运营')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('没有找到符合条件的机构', $output);
    }

    /**
     * 测试检查所有正常运营的机构
     */
    public function testExecuteWithDefaultOptionsChecksAllOperatingInstitutions(): void
    {
        $institution1 = $this->createTestInstitution('机构A', 'INST001');
        $institution2 = $this->createTestInstitution('机构B', 'INST002');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->with('正常运营')
            ->willReturn([$institution1, $institution2])
        ;

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质即将过期'])
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('检查所有正常运营的机构', $output);
        self::assertStringContainsString('检查 2 个机构', $output);
        self::assertStringContainsString('机构A', $output);
        self::assertStringContainsString('机构B', $output);
    }

    /**
     * 测试检查指定状态的机构
     */
    public function testExecuteWithStatusOptionChecksSpecificStatusInstitutions(): void
    {
        $institution = $this->createTestInstitution('待审核机构', 'INST003', '待审核');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->with('待审核')
            ->willReturn([$institution])
        ;

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute(['--status' => '待审核']);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString("检查状态为 '待审核' 的机构", $output);
        self::assertStringContainsString('待审核机构', $output);
    }

    /**
     * 测试检查指定ID的机构
     */
    public function testExecuteWithInstitutionIdOptionChecksSpecificInstitution(): void
    {
        $institutionId = 'specific-institution-id';
        $institution = $this->createTestInstitution('指定机构', 'INST004');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionById')
            ->with($institutionId)
            ->willReturn($institution)
        ;

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute(['--institution-id' => $institutionId]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('指定机构', $output);
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
        $institution = $this->createTestInstitution('测试机构', 'TEST001');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution])
        ;

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn(['问题1', '问题2'])
        ;

        $exitCode = $this->commandTester->execute(['--format' => 'json']);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();

        // 提取JSON部分（可能前面有其他输出）
        $jsonStart = strpos($output, '{');
        self::assertNotFalse($jsonStart, 'JSON output not found');
        $jsonOutput = substr($output, $jsonStart);

        // 验证JSON格式
        /** @var array<string, mixed>|null $jsonData */
        $jsonData = json_decode($jsonOutput, true);
        self::assertNotNull($jsonData);
        self::assertIsArray($jsonData);
        self::assertArrayHasKey('summary', $jsonData);
        self::assertArrayHasKey('results', $jsonData);
        self::assertIsArray($jsonData['summary']);
        self::assertArrayHasKey('total_institutions', $jsonData['summary']);
        self::assertArrayHasKey('compliant', $jsonData['summary']);
        self::assertArrayHasKey('non_compliant', $jsonData['summary']);
        self::assertArrayHasKey('total_issues', $jsonData['summary']);
        self::assertEquals(1, $jsonData['summary']['total_institutions']);
        self::assertEquals(0, $jsonData['summary']['compliant']);
        self::assertEquals(1, $jsonData['summary']['non_compliant']);
        self::assertEquals(2, $jsonData['summary']['total_issues']);
    }

    /**
     * 测试摘要格式输出
     */
    public function testExecuteWithSummaryFormatOutputsSummaryData(): void
    {
        $institution1 = $this->createTestInstitution('合规机构', 'COMP001');
        $institution2 = $this->createTestInstitution('不合规机构', 'NONCOMP001');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2])
        ;

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质过期', '设施不足'])
        ;

        $exitCode = $this->commandTester->execute(['--format' => 'summary']);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('检查结果摘要', $output);
        self::assertStringContainsString('检查机构总数', $output);
        self::assertStringContainsString('合规机构数量', $output);
        self::assertStringContainsString('不合规机构数量', $output);
        self::assertStringContainsString('不合规机构列表', $output);
        self::assertStringContainsString('不合规机构', $output);
    }

    /**
     * 测试只检查合规性问题
     */
    public function testExecuteWithComplianceOnlyOptionShowsOnlyNonCompliantInstitutions(): void
    {
        $institution1 = $this->createTestInstitution('合规机构', 'COMP001');
        $institution2 = $this->createTestInstitution('不合规机构', 'NONCOMP001');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2])
        ;

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质过期'])
        ;

        $exitCode = $this->commandTester->execute(['--compliance-only' => true]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('发现 1 个机构存在合规问题', $output);
        self::assertStringContainsString('不合规机构 - 合规问题', $output);
        self::assertStringContainsString('资质过期', $output);
    }

    /**
     * 测试所有机构都合规的情况
     */
    public function testExecuteWithAllCompliantInstitutionsShowsSuccessMessage(): void
    {
        $institution1 = $this->createTestInstitution('机构A', 'INST001');
        $institution2 = $this->createTestInstitution('机构B', 'INST002');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2])
        ;

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('所有机构都符合合规要求', $output);
        self::assertStringContainsString('合规率', $output);
        self::assertStringContainsString('100%', $output);
    }

    /**
     * 测试有不合规机构的情况
     */
    public function testExecuteWithNonCompliantInstitutionsShowsWarningAndSuggestions(): void
    {
        $institution1 = $this->createTestInstitution('合规机构', 'COMP001');
        $institution2 = $this->createTestInstitution('不合规机构', 'NONCOMP001');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2])
        ;

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['资质过期', '设施不足'])
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('发现 1 个机构存在合规问题', $output);
        self::assertStringContainsString('建议：', $output);
        self::assertStringContainsString('联系相关机构负责人', $output);
        self::assertStringContainsString('安排专人跟进', $output);
    }

    /**
     * 测试干运行模式
     */
    public function testExecuteWithDryRunOptionShowsDryRunNote(): void
    {
        $institution = $this->createTestInstitution('测试机构', 'TEST001');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution])
        ;

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('运行在干运行模式', $output);
    }

    /**
     * 测试表格格式输出（默认）
     */
    public function testExecuteWithTableFormatDisplaysTableOutput(): void
    {
        $institution1 = $this->createTestInstitution('机构A', 'INST001');
        $institution2 = $this->createTestInstitution('机构B', 'INST002');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution1, $institution2])
        ;

        $this->institutionService
            ->expects($this->exactly(2))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], ['问题1'])
        ;

        $exitCode = $this->commandTester->execute(['--format' => 'table']);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('机构名称', $output);
        self::assertStringContainsString('机构代码', $output);
        self::assertStringContainsString('合规性', $output);
        self::assertStringContainsString('问题数量', $output);
        self::assertStringContainsString('机构A', $output);
        self::assertStringContainsString('机构B', $output);
        self::assertStringContainsString('合规', $output);
        self::assertStringContainsString('不合规', $output);
    }

    /**
     * 测试异常处理
     */
    public function testExecuteWithExceptionReturnsFailure(): void
    {
        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willThrowException(new \RuntimeException('数据库连接失败'))
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('执行过程中发生错误', $output);
        self::assertStringContainsString('数据库连接失败', $output);
    }

    /**
     * 测试合规率计算
     */
    public function testExecuteCalculatesCorrectComplianceRate(): void
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
            ->willReturn($institutions)
        ;

        // 3个合规，1个不合规，合规率应该是75%
        $this->institutionService
            ->expects($this->exactly(4))
            ->method('checkInstitutionCompliance')
            ->willReturnOnConsecutiveCalls([], [], [], ['问题1'])
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('75%', $output);
    }

    /**
     * 测试只有合规问题的机构显示（compliance-only模式）
     */
    public function testExecuteWithComplianceOnlyAndAllCompliantShowsSuccessMessage(): void
    {
        $institution = $this->createTestInstitution('合规机构', 'COMP001');

        $this->institutionService
            ->expects($this->once())
            ->method('getInstitutionsByStatus')
            ->willReturn([$institution])
        ;

        $this->institutionService
            ->expects($this->once())
            ->method('checkInstitutionCompliance')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute(['--compliance-only' => true]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('所有机构都符合合规要求', $output);
    }

    /**
     * 测试 --status 选项
     */
    public function testOptionStatus(): void
    {
        $command = self::getService(InstitutionStatusCheckCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('status'),
            'Command should have --status option'
        );
    }

    /**
     * 测试 --institution-id 选项
     */
    public function testOptionInstitutionId(): void
    {
        $command = self::getService(InstitutionStatusCheckCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('institution-id'),
            'Command should have --institution-id option'
        );
    }

    /**
     * 测试 --dry-run 选项
     */
    public function testOptionDryRun(): void
    {
        $command = self::getService(InstitutionStatusCheckCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('dry-run'),
            'Command should have --dry-run option'
        );
    }

    /**
     * 测试 --format 选项
     */
    public function testOptionFormat(): void
    {
        $command = self::getService(InstitutionStatusCheckCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('format'),
            'Command should have --format option'
        );
    }

    /**
     * 测试 --compliance-only 选项
     */
    public function testOptionComplianceOnly(): void
    {
        $command = self::getService(InstitutionStatusCheckCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('compliance-only'),
            'Command should have --compliance-only option'
        );
    }
}
