<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainInstitutionBundle\Command\FacilityInspectionScheduleCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * FacilityInspectionScheduleCommand 集成测试
 *
 * @internal
 */
#[CoversClass(FacilityInspectionScheduleCommand::class)]
#[RunTestsInSeparateProcesses]
final class FacilityInspectionScheduleCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    protected function onSetUp(): void
    {

        // 从容器获取命令实例
        $command = self::getService(FacilityInspectionScheduleCommand::class);

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
    private function createTestInstitution(string $name, string $code): Institution
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

        $entityManager = self::getEntityManager();
        $entityManager->persist($institution);
        $entityManager->flush();

        return $institution;
    }

    /**
     * 创建测试设施
     */
    private function createTestFacility(
        Institution $institution,
        ?\DateTimeImmutable $nextInspectionDate = null
    ): InstitutionFacility {
        $facility = InstitutionFacility::create(
            $institution,
            '教室',
            '测试教室',
            '1楼101室',
            120.5,
            50,
            ['投影仪'],
            ['灭火器'],
            '正常使用'
        );

        if (null !== $nextInspectionDate) {
            $facility->setNextInspectionDate($nextInspectionDate);
        }

        $entityManager = self::getEntityManager();
        $entityManager->persist($facility);
        $entityManager->flush();

        return $facility;
    }

    /**
     * 清理测试数据
     */
    private function clearTestData(): void
    {
        $entityManager = self::getEntityManager();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\InstitutionFacility')->execute();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\Institution')->execute();
        $entityManager->flush();
    }

    /**
     * 测试命令配置
     */
    public function testCommandConfiguration(): void
    {
        $command = self::getService(FacilityInspectionScheduleCommand::class);
        self::assertEquals(FacilityInspectionScheduleCommand::NAME, $command->getName());
        self::assertStringContainsString('培训设施检查', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('start-date'));
        self::assertTrue($definition->hasOption('interval'));
        self::assertTrue($definition->hasOption('dry-run'));
        self::assertTrue($definition->hasOption('auto-schedule'));
    }

    /**
     * 测试 --start-date 选项
     */
    public function testOptionStartDate(): void
    {
        $command = self::getService(FacilityInspectionScheduleCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('start-date'),
            'Command should have --start-date option'
        );
    }

    /**
     * 测试 --interval 选项
     */
    public function testOptionInterval(): void
    {
        $command = self::getService(FacilityInspectionScheduleCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('interval'),
            'Command should have --interval option'
        );
    }

    /**
     * 测试 --dry-run 选项
     */
    public function testOptionDryRun(): void
    {
        $command = self::getService(FacilityInspectionScheduleCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('dry-run'),
            'Command should have --dry-run option'
        );
    }

    /**
     * 测试 --auto-schedule 选项
     */
    public function testOptionAutoSchedule(): void
    {
        $command = self::getService(FacilityInspectionScheduleCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('auto-schedule'),
            'Command should have --auto-schedule option'
        );
    }

    /**
     * 测试没有需要检查的设施
     */
    public function testNoFacilitiesNeedInspection(): void
    {
        $this->clearTestData();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('没有需要检查的设施', $output);
        self::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * 测试有需要检查的设施（nextInspectionDate 为 null 或已过期）
     */
    public function testFacilitiesNeedInspection(): void
    {
        $this->clearTestData();
        $institution = $this->createTestInstitution('测试机构', 'TEST001');
        // 创建一个需要检查的设施（nextInspectionDate 为 null）
        $this->createTestFacility($institution, null);

        $this->commandTester->execute(['--interval' => '30']);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('测试机构', $output);
        self::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}