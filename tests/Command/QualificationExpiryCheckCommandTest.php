<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainInstitutionBundle\Command\QualificationExpiryCheckCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * QualificationExpiryCheckCommand 集成测试
 *
 * @internal
 */
#[CoversClass(QualificationExpiryCheckCommand::class)]
#[RunTestsInSeparateProcesses]
final class QualificationExpiryCheckCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        // 从容器获取命令实例
        $command = self::getService(QualificationExpiryCheckCommand::class);

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
     * 创建测试资质
     */
    private function createTestQualification(
        Institution $institution,
        string $name,
        string $certificateNumber,
        \DateTimeImmutable $validTo,
    ): InstitutionQualification {
        $qualification = InstitutionQualification::create(
            $institution,
            '安全培训资质',                      // qualificationType
            $name,                              // qualificationName
            $certificateNumber,                 // certificateNumber
            '发证机关',                          // issuingAuthority
            new \DateTimeImmutable('2020-01-01'), // issueDate
            new \DateTimeImmutable('2020-01-01'), // validFrom
            $validTo                             // validTo
        );

        $entityManager = self::getEntityManager();
        $entityManager->persist($qualification);
        $entityManager->flush();

        return $qualification;
    }

    /**
     * 清理测试数据
     */
    private function clearTestData(): void
    {
        $entityManager = self::getEntityManager();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\InstitutionQualification')->execute();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\Institution')->execute();
        $entityManager->flush();
    }

    /**
     * 测试命令配置
     */
    public function testCommandConfiguration(): void
    {
        $command = self::getService(QualificationExpiryCheckCommand::class);
        self::assertEquals(QualificationExpiryCheckCommand::NAME, $command->getName());
        self::assertEquals('检查即将到期的培训机构资质证书', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('days'));
        self::assertTrue($definition->hasOption('dry-run'));
        self::assertTrue($definition->hasOption('format'));
    }

    /**
     * 测试 --days 选项
     */
    public function testOptionDays(): void
    {
        $command = self::getService(QualificationExpiryCheckCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('days'),
            'Command should have --days option'
        );
    }

    /**
     * 测试 --dry-run 选项
     */
    public function testOptionDryRun(): void
    {
        $command = self::getService(QualificationExpiryCheckCommand::class);
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
        $command = self::getService(QualificationExpiryCheckCommand::class);
        self::assertTrue(
            $command->getDefinition()->hasOption('format'),
            'Command should have --format option'
        );
    }

    /**
     * 测试没有即将到期的资质
     */
    public function testNoExpiringQualifications(): void
    {
        $this->clearTestData();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('未发现30天内到期的资质', $output);
        self::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * 测试有即将到期的资质
     */
    public function testWithExpiringQualifications(): void
    {
        $this->clearTestData();
        $institution = $this->createTestInstitution('测试机构', 'TEST001');
        $this->createTestQualification(
            $institution,
            '安全培训资质',
            'CERT001',
            new \DateTimeImmutable('+15 days')
        );

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('测试机构', $output);
        self::assertStringContainsString('安全培训资质', $output);
        self::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * 测试自定义天数参数
     */
    public function testWithCustomDays(): void
    {
        $this->clearTestData();
        $institution = $this->createTestInstitution('测试机构', 'TEST001');
        $this->createTestQualification(
            $institution,
            '资质证书',
            'CERT002',
            new \DateTimeImmutable('+5 days')
        );

        $this->commandTester->execute(['--days' => '10']);

        $output = $this->commandTester->getDisplay();
        // +5 天到期的资质会在 --days 10 范围内被检测到
        self::assertStringContainsString('资质证书', $output);
        self::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
