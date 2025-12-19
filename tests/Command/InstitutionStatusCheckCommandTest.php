<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainInstitutionBundle\Command\InstitutionStatusCheckCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * InstitutionStatusCheckCommand 集成测试
 *
 * @internal
 */
#[CoversClass(InstitutionStatusCheckCommand::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionStatusCheckCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;
    private InstitutionRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(InstitutionRepository::class);

        // 从容器获取命令实例
        $command = self::getService(InstitutionStatusCheckCommand::class);

        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    /**
     * 创建测试机构
     */
    private function createTestInstitution(string $name, string $code, string $status = '正常运营'): Institution
    {
        $institution = Institution::create(
            $name,
            $code,
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );
        $institution->setInstitutionStatus($status);

        $entityManager = self::getEntityManager();
        $entityManager->persist($institution);
        $entityManager->flush();

        return $institution;
    }

    /**
     * 清理测试数据
     */
    private function clearTestData(): void
    {
        $entityManager = self::getEntityManager();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\Institution')->execute();
        $entityManager->flush();
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
        $this->clearTestData();

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('没有找到符合条件的机构', $output);
    }

    /**
     * 测试检查正常运营的机构
     */
    public function testExecuteWithOperatingInstitutions(): void
    {
        $this->clearTestData();
        $this->createTestInstitution('测试机构A', 'INST001', '正常运营');

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('测试机构A', $output);
    }

    /**
     * 测试干运行模式
     */
    public function testExecuteWithDryRunOption(): void
    {
        $this->clearTestData();
        $this->createTestInstitution('测试机构', 'TEST001', '正常运营');

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('运行在干运行模式', $output);
    }

    /**
     * 测试JSON格式输出
     */
    public function testExecuteWithJsonFormat(): void
    {
        $this->clearTestData();
        $this->createTestInstitution('测试机构', 'TEST001', '正常运营');

        $exitCode = $this->commandTester->execute(['--format' => 'json']);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $jsonStart = strpos($output, '{');
        self::assertNotFalse($jsonStart, 'JSON output not found');
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