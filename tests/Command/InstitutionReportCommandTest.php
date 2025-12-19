<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainInstitutionBundle\Command\InstitutionReportCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;

/**
 * InstitutionReportCommand 集成测试
 *
 * @internal
 */
#[CoversClass(InstitutionReportCommand::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionReportCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    protected function onSetUp(): void
    {

        // 从容器获取命令实例
        $command = self::getService(InstitutionReportCommand::class);

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

    /**
     * 测试生成空报告（无数据时也会输出统计）
     */
    public function testGenerateEmptyReport(): void
    {
        $this->clearTestData();

        $this->commandTester->execute(['--type' => 'summary']);

        $output = $this->commandTester->getDisplay();
        // 即使没有机构数据，命令也会输出全局统计报告
        self::assertStringContainsString('全局摘要', $output);
        self::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * 测试生成指定机构的报告
     */
    public function testGenerateReportWithInstitutionId(): void
    {
        $this->clearTestData();
        $institution = $this->createTestInstitution('测试机构A', 'INST001', '正常运营');

        $this->commandTester->execute([
            '--institution-id' => $institution->getId(),
            '--type' => 'summary',
        ]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('测试机构A', $output);
        self::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}