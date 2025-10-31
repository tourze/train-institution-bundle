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
use Tourze\TrainInstitutionBundle\Command\FacilityInspectionScheduleCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Service\FacilityService;

/**
 * @internal
 */
#[CoversClass(FacilityInspectionScheduleCommand::class)]
#[RunTestsInSeparateProcesses]
final class FacilityInspectionScheduleCommandTest extends AbstractCommandTestCase
{
    private MockObject&FacilityService $facilityService;

    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        // 创建Mock服务并注册到容器
        $this->facilityService = $this->createMock(FacilityService::class);
        self::getContainer()->set(FacilityService::class, $this->facilityService);

        // 从容器获取命令实例
        $command = self::getService(FacilityInspectionScheduleCommand::class);

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
    private function createTestInstitution(): Institution
    {
        return Institution::create(
            '测试培训机构',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区测试路123号',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456789'
        );
    }

    /**
     * 创建测试设施
     */
    private function createTestFacility(Institution $institution, string $name, string $type): MockObject&InstitutionFacility
    {
        /*
         * 使用具体类 InstitutionFacility 进行 Mock：
         * 1) 为什么必须使用具体类：InstitutionFacility 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，在测试中需要创建可控的实体对象，避免依赖数据库持久化
         * 3) 更好的替代方案：可以使用 Entity 工厂或 Builder 模式，但 Mock 在单元测试中更加灵活
         */
        $facility = $this->createMock(InstitutionFacility::class);
        $facility->method('getId')->willReturn('facility-' . uniqid());
        $facility->method('getInstitution')->willReturn($institution);
        $facility->method('getFacilityName')->willReturn($name);
        $facility->method('getFacilityType')->willReturn($type);
        $facility->method('getFacilityStatus')->willReturn('正常');
        $facility->method('getLastInspectionDate')->willReturn(new \DateTimeImmutable('-30 days'));
        $facility->method('getNextInspectionDate')->willReturn(null);

        return $facility;
    }

    /**
     * 测试命令配置
     */
    public function testCommandConfiguration(): void
    {
        $command = self::getService(FacilityInspectionScheduleCommand::class);
        self::assertEquals(FacilityInspectionScheduleCommand::NAME, $command->getName());
        self::assertEquals('安排培训设施检查', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('start-date'));
        self::assertTrue($definition->hasOption('interval'));
        self::assertTrue($definition->hasOption('dry-run'));
        self::assertTrue($definition->hasOption('auto-schedule'));
    }

    /**
     * 测试没有需要检查的设施
     */
    public function testExecuteWithNoFacilitiesNeedingInspectionReturnsSuccess(): void
    {
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('当前没有需要检查的设施', $output);
    }

    /**
     * 测试有设施需要检查但用户取消
     */
    public function testExecuteWithFacilitiesButUserCancelsReturnsSuccess(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility])
        ;

        // 模拟用户输入 'no'
        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('发现 1 个需要检查的设施', $output);
        self::assertStringContainsString('用户取消了检查安排', $output);
    }

    /**
     * 测试自动安排模式
     */
    public function testExecuteWithAutoScheduleOptionSchedulesInspections(): void
    {
        $institution = $this->createTestInstitution();
        $facility1 = $this->createTestFacility($institution, '培训教室1', '教学设施');
        $facility2 = $this->createTestFacility($institution, '实验室1', '实验设施');

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility1, $facility2])
        ;

        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->with(
                self::isArray(),
                self::isInstanceOf(\DateTimeImmutable::class),
                7
            )
            ->willReturn([
                [
                    'facility_id' => $facility1->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable('tomorrow'),
                ],
                [
                    'facility_id' => $facility2->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable('+8 days'),
                ],
            ])
        ;

        $exitCode = $this->commandTester->execute(['--auto-schedule' => true]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('发现 2 个需要检查的设施', $output);
        self::assertStringContainsString('成功安排了 2 个设施的检查', $output);
    }

    /**
     * 测试干运行模式
     */
    public function testExecuteWithDryRunOptionShowsPlanOnly(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility])
        ;

        // 不应该调用实际的安排方法
        $this->facilityService
            ->expects($this->never())
            ->method('batchScheduleInspections')
        ;

        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
            '--auto-schedule' => true,
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('运行在干运行模式', $output);
        self::assertStringContainsString('干运行模式 - 以下是安排计划', $output);
    }

    /**
     * 测试自定义开始日期
     */
    public function testExecuteWithCustomStartDateUsesSpecifiedDate(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility])
        ;

        $customDate = '2024-01-15';
        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->with(
                self::isArray(),
                self::callback(function ($date) use ($customDate) {
                    return $date instanceof \DateTimeImmutable
                           && $date->format('Y-m-d') === $customDate;
                }),
                7
            )
            ->willReturn([
                [
                    'facility_id' => $facility->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable($customDate),
                ],
            ])
        ;

        $exitCode = $this->commandTester->execute([
            '--start-date' => $customDate,
            '--auto-schedule' => true,
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString($customDate, $output);
    }

    /**
     * 测试自定义检查间隔
     */
    public function testExecuteWithCustomIntervalUsesSpecifiedInterval(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility])
        ;

        $customInterval = 14;
        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->with(
                self::isArray(),
                self::isInstanceOf(\DateTimeImmutable::class),
                $customInterval
            )
            ->willReturn([
                [
                    'facility_id' => $facility->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable('tomorrow'),
                ],
            ])
        ;

        $exitCode = $this->commandTester->execute([
            '--interval' => (string) $customInterval,
            '--auto-schedule' => true,
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString($customInterval . '天', $output);
    }

    /**
     * 测试部分成功的批量安排
     */
    public function testExecuteWithMixedResultsShowsBothSuccessAndFailure(): void
    {
        $institution = $this->createTestInstitution();
        $facility1 = $this->createTestFacility($institution, '培训教室1', '教学设施');
        $facility2 = $this->createTestFacility($institution, '实验室1', '实验设施');

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility1, $facility2])
        ;

        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->willReturn([
                [
                    'facility_id' => $facility1->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable('tomorrow'),
                ],
                [
                    'facility_id' => $facility2->getId(),
                    'success' => false,
                    'error' => '设施状态不允许安排检查',
                ],
            ])
        ;

        $exitCode = $this->commandTester->execute(['--auto-schedule' => true]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('成功安排了 1 个设施的检查', $output);
        self::assertStringContainsString('有 1 个设施安排失败', $output);
        self::assertStringContainsString('设施状态不允许安排检查', $output);
    }

    /**
     * 测试用户确认安排
     */
    public function testExecuteWithUserConfirmationSchedulesInspections(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility])
        ;

        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->willReturn([
                [
                    'facility_id' => $facility->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable('tomorrow'),
                ],
            ])
        ;

        // 模拟用户输入 'yes'
        $this->commandTester->setInputs(['yes']);
        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('成功安排了 1 个设施的检查', $output);
    }

    /**
     * 测试异常处理
     */
    public function testExecuteWithExceptionReturnsFailure(): void
    {
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willThrowException(new \RuntimeException('数据库连接失败'))
        ;

        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('执行过程中发生错误', $output);
        self::assertStringContainsString('数据库连接失败', $output);
    }

    /**
     * 测试无效日期格式
     */
    public function testExecuteWithInvalidDateFormatReturnsFailure(): void
    {
        $exitCode = $this->commandTester->execute([
            '--start-date' => 'invalid-date',
            '--auto-schedule' => true,
        ]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('执行过程中发生错误', $output);
    }

    /**
     * 测试设施信息显示
     */
    public function testExecuteDisplaysCorrectFacilityInformation(): void
    {
        $institution = $this->createTestInstitution();

        // 创建特定的设施Mock，不使用通用方法
        /*
         * 使用具体类 InstitutionFacility 进行 Mock：
         * 1) 为什么必须使用具体类：InstitutionFacility 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试需要验证特定的设施信息显示，使用 Mock 可以精确控制返回数据
         * 3) 更好的替代方案：可以创建真实的 Entity 实例，但会增加测试的复杂性和数据管理成本
         */
        $facility = $this->createMock(InstitutionFacility::class);
        $facility->method('getId')->willReturn('facility-test');
        $facility->method('getInstitution')->willReturn($institution);
        $facility->method('getFacilityName')->willReturn('培训教室1');
        $facility->method('getFacilityType')->willReturn('教学设施');
        $facility->method('getFacilityStatus')->willReturn('正常');
        $facility->method('getLastInspectionDate')->willReturn(new \DateTimeImmutable('2023-12-01'));
        $facility->method('getNextInspectionDate')->willReturn(new \DateTimeImmutable('2024-01-01'));

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility])
        ;

        // 模拟用户取消
        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();

        // 验证表格中显示的信息
        self::assertStringContainsString('测试培训机构', $output);
        self::assertStringContainsString('培训教室1', $output);
        self::assertStringContainsString('教学设施', $output);
        self::assertStringContainsString('2023-12-01', $output);
        self::assertStringContainsString('2024-01-01', $output);
        self::assertStringContainsString('正常', $output);
    }

    /**
     * 测试从未检查过的设施
     */
    public function testExecuteWithNeverInspectedFacilityShowsCorrectStatus(): void
    {
        $institution = $this->createTestInstitution();

        // 创建特定的设施Mock，设置为从未检查过
        /*
         * 使用具体类 InstitutionFacility 进行 Mock：
         * 1) 为什么必须使用具体类：InstitutionFacility 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试特殊情况（从未检查过的设施），Mock 能提供精确的空值状态
         * 3) 更好的替代方案：使用测试数据构建器或工厂模式，但当前场景下 Mock 更简洁有效
         */
        $facility = $this->createMock(InstitutionFacility::class);
        $facility->method('getId')->willReturn('facility-never-inspected');
        $facility->method('getInstitution')->willReturn($institution);
        $facility->method('getFacilityName')->willReturn('新设施');
        $facility->method('getFacilityType')->willReturn('教学设施');
        $facility->method('getFacilityStatus')->willReturn('正常');
        $facility->method('getLastInspectionDate')->willReturn(null);
        $facility->method('getNextInspectionDate')->willReturn(null);

        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility])
        ;

        // 模拟用户取消
        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('从未检查', $output);
        self::assertStringContainsString('未安排', $output);
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
}
