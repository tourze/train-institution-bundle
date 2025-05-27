<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TrainInstitutionBundle\Command\FacilityInspectionScheduleCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Service\FacilityService;

/**
 * FacilityInspectionScheduleCommand 单元测试
 */
class FacilityInspectionScheduleCommandTest extends TestCase
{
    private FacilityInspectionScheduleCommand $command;
    private MockObject&FacilityService $facilityService;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->facilityService = $this->createMock(FacilityService::class);
        $this->command = new FacilityInspectionScheduleCommand($this->facilityService);

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
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
    public function test_commandConfiguration(): void
    {
        $this->assertEquals(FacilityInspectionScheduleCommand::NAME, $this->command->getName());
        $this->assertEquals('安排培训设施检查', $this->command->getDescription());
        
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('start-date'));
        $this->assertTrue($definition->hasOption('interval'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('auto-schedule'));
    }

    /**
     * 测试没有需要检查的设施
     */
    public function test_execute_withNoFacilitiesNeedingInspection_returnsSuccess(): void
    {
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('当前没有需要检查的设施', $output);
    }

    /**
     * 测试有设施需要检查但用户取消
     */
    public function test_execute_withFacilitiesButUserCancels_returnsSuccess(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');
        
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility]);

        // 模拟用户输入 'no'
        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('发现 1 个需要检查的设施', $output);
        $this->assertStringContainsString('用户取消了检查安排', $output);
    }

    /**
     * 测试自动安排模式
     */
    public function test_execute_withAutoScheduleOption_schedulesInspections(): void
    {
        $institution = $this->createTestInstitution();
        $facility1 = $this->createTestFacility($institution, '培训教室1', '教学设施');
        $facility2 = $this->createTestFacility($institution, '实验室1', '实验设施');
        
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility1, $facility2]);

        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->with(
                $this->isType('array'),
                $this->isInstanceOf(\DateTimeImmutable::class),
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
            ]);

        $exitCode = $this->commandTester->execute(['--auto-schedule' => true]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('发现 2 个需要检查的设施', $output);
        $this->assertStringContainsString('成功安排了 2 个设施的检查', $output);
    }

    /**
     * 测试干运行模式
     */
    public function test_execute_withDryRunOption_showsPlanOnly(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');
        
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility]);

        // 不应该调用实际的安排方法
        $this->facilityService
            ->expects($this->never())
            ->method('batchScheduleInspections');

        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
            '--auto-schedule' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('运行在干运行模式', $output);
        $this->assertStringContainsString('干运行模式 - 以下是安排计划', $output);
    }

    /**
     * 测试自定义开始日期
     */
    public function test_execute_withCustomStartDate_usesSpecifiedDate(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');
        
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility]);

        $customDate = '2024-01-15';
        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->with(
                $this->isType('array'),
                $this->callback(function ($date) use ($customDate) {
                    return $date instanceof \DateTimeImmutable && 
                           $date->format('Y-m-d') === $customDate;
                }),
                7
            )
            ->willReturn([
                [
                    'facility_id' => $facility->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable($customDate),
                ],
            ]);

        $exitCode = $this->commandTester->execute([
            '--start-date' => $customDate,
            '--auto-schedule' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString($customDate, $output);
    }

    /**
     * 测试自定义检查间隔
     */
    public function test_execute_withCustomInterval_usesSpecifiedInterval(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');
        
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility]);

        $customInterval = 14;
        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->with(
                $this->isType('array'),
                $this->isInstanceOf(\DateTimeImmutable::class),
                $customInterval
            )
            ->willReturn([
                [
                    'facility_id' => $facility->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable('tomorrow'),
                ],
            ]);

        $exitCode = $this->commandTester->execute([
            '--interval' => (string)$customInterval,
            '--auto-schedule' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString($customInterval . '天', $output);
    }

    /**
     * 测试部分成功的批量安排
     */
    public function test_execute_withMixedResults_showsBothSuccessAndFailure(): void
    {
        $institution = $this->createTestInstitution();
        $facility1 = $this->createTestFacility($institution, '培训教室1', '教学设施');
        $facility2 = $this->createTestFacility($institution, '实验室1', '实验设施');
        
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility1, $facility2]);

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
            ]);

        $exitCode = $this->commandTester->execute(['--auto-schedule' => true]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功安排了 1 个设施的检查', $output);
        $this->assertStringContainsString('有 1 个设施安排失败', $output);
        $this->assertStringContainsString('设施状态不允许安排检查', $output);
    }

    /**
     * 测试用户确认安排
     */
    public function test_execute_withUserConfirmation_schedulesInspections(): void
    {
        $institution = $this->createTestInstitution();
        $facility = $this->createTestFacility($institution, '培训教室1', '教学设施');
        
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willReturn([$facility]);

        $this->facilityService
            ->expects($this->once())
            ->method('batchScheduleInspections')
            ->willReturn([
                [
                    'facility_id' => $facility->getId(),
                    'success' => true,
                    'scheduled_date' => new \DateTimeImmutable('tomorrow'),
                ],
            ]);

        // 模拟用户输入 'yes'
        $this->commandTester->setInputs(['yes']);
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功安排了 1 个设施的检查', $output);
    }

    /**
     * 测试异常处理
     */
    public function test_execute_withException_returnsFailure(): void
    {
        $this->facilityService
            ->expects($this->once())
            ->method('getFacilitiesNeedingInspection')
            ->willThrowException(new \RuntimeException('数据库连接失败'));

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('执行过程中发生错误', $output);
        $this->assertStringContainsString('数据库连接失败', $output);
    }

    /**
     * 测试无效日期格式
     */
    public function test_execute_withInvalidDateFormat_returnsFailure(): void
    {
        $exitCode = $this->commandTester->execute([
            '--start-date' => 'invalid-date',
            '--auto-schedule' => true,
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('执行过程中发生错误', $output);
    }

    /**
     * 测试设施信息显示
     */
    public function test_execute_displaysCorrectFacilityInformation(): void
    {
        $institution = $this->createTestInstitution();
        
        // 创建特定的设施Mock，不使用通用方法
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
            ->willReturn([$facility]);

        // 模拟用户取消
        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        
        // 验证表格中显示的信息
        $this->assertStringContainsString('测试培训机构', $output);
        $this->assertStringContainsString('培训教室1', $output);
        $this->assertStringContainsString('教学设施', $output);
        $this->assertStringContainsString('2023-12-01', $output);
        $this->assertStringContainsString('2024-01-01', $output);
        $this->assertStringContainsString('正常', $output);
    }

    /**
     * 测试从未检查过的设施
     */
    public function test_execute_withNeverInspectedFacility_showsCorrectStatus(): void
    {
        $institution = $this->createTestInstitution();
        
        // 创建特定的设施Mock，设置为从未检查过
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
            ->willReturn([$facility]);

        // 模拟用户取消
        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        
        $this->assertStringContainsString('从未检查', $output);
        $this->assertStringContainsString('未安排', $output);
    }
} 