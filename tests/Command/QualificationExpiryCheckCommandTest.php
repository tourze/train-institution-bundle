<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainInstitutionBundle\Command\QualificationExpiryCheckCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * QualificationExpiryCheckCommand 单元测试
 *
 * @internal
 */
#[CoversClass(QualificationExpiryCheckCommand::class)]
#[RunTestsInSeparateProcesses]
final class QualificationExpiryCheckCommandTest extends AbstractCommandTestCase
{
    private MockObject&QualificationService $qualificationService;

    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        // 创建Mock服务并注册到容器
        $this->qualificationService = $this->createMock(QualificationService::class);
        self::getContainer()->set(QualificationService::class, $this->qualificationService);

        // 从容器获取命令实例
        $command = self::getService(QualificationExpiryCheckCommand::class);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    /**
     * 测试没有即将到期的资质
     */
    public function testNoExpiringQualifications(): void
    {
        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->with(30)
            ->willReturn([])
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('未发现30天内到期的资质', $output);
        self::assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试有即将到期的资质
     */
    public function testWithExpiringQualifications(): void
    {
        /*
         * 使用具体类 Institution 进行 Mock：
         * 1) 为什么必须使用具体类：Institution 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，在测试中需要创建可控的实体对象，避免依赖数据库持久化
         * 3) 更好的替代方案：可以使用 Entity 工厂或 Builder 模式，但 Mock 在单元测试中更加灵活可控
         */
        $institution = $this->createMock(Institution::class);
        $institution->method('getInstitutionName')->willReturn('测试机构');

        /*
         * 使用具体类 InstitutionQualification 进行 Mock：
         * 1) 为什么必须使用具体类：InstitutionQualification 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试中需要模拟资质实体的各种状态和属性
         * 3) 更好的替代方案：可以创建真实的 Entity 实例，但 Mock 提供更好的控制和隔离
         */
        $qualification1 = $this->createMock(InstitutionQualification::class);
        $qualification1->method('getInstitution')->willReturn($institution);
        $qualification1->method('getQualificationName')->willReturn('安全培训资质');
        $qualification1->method('getCertificateNumber')->willReturn('CERT001');
        $qualification1->method('getValidTo')->willReturn(new \DateTimeImmutable('+15 days'));
        $qualification1->method('getRemainingDays')->willReturn(15);

        /*
         * 使用具体类 InstitutionQualification 进行 Mock：
         * 1) 为什么必须使用具体类：InstitutionQualification 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试中需要模拟不同到期时间的资质实体
         * 3) 更好的替代方案：可以创建真实的 Entity 实例，但 Mock 提供更精确的测试控制
         */
        $qualification2 = $this->createMock(InstitutionQualification::class);
        $qualification2->method('getInstitution')->willReturn($institution);
        $qualification2->method('getQualificationName')->willReturn('特种作业培训资质');
        $qualification2->method('getCertificateNumber')->willReturn('CERT002');
        $qualification2->method('getValidTo')->willReturn(new \DateTimeImmutable('+5 days'));
        $qualification2->method('getRemainingDays')->willReturn(5);

        $expiringQualifications = [$qualification1, $qualification2];

        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->with(30)
            ->willReturn($expiringQualifications)
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('发现 2 个即将到期的资质', $output);
        self::assertStringContainsString('测试机构', $output);
        self::assertStringContainsString('安全培训资质', $output);
        self::assertStringContainsString('CERT001', $output);
        self::assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试自定义检查天数
     */
    public function testCustomDays(): void
    {
        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->with(60)
            ->willReturn([])
        ;

        $this->commandTester->execute(['--days' => '60']);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('未发现60天内到期的资质', $output);
        self::assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试JSON输出格式
     */
    public function testJsonFormat(): void
    {
        /*
         * 使用具体类 Institution 进行 Mock：
         * 1) 为什么必须使用具体类：Institution 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试JSON输出格式时需要可预测的数据结构
         * 3) 更好的替代方案：可以使用真实 Entity，但 Mock 能确保输出一致性和可预测性
         */
        $institution = $this->createMock(Institution::class);
        $institution->method('getInstitutionName')->willReturn('测试机构');

        /*
         * 使用具体类 InstitutionQualification 进行 Mock：
         * 1) 为什么必须使用具体类：InstitutionQualification 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试JSON输出格式时需要可控的资质数据结构
         * 3) 更好的替代方案：可以创建真实的 Entity 实例，但 Mock 提供更精确的测试控制
         */
        $qualification = $this->createMock(InstitutionQualification::class);
        $qualification->method('getInstitution')->willReturn($institution);
        $qualification->method('getQualificationName')->willReturn('安全培训资质');
        $qualification->method('getCertificateNumber')->willReturn('CERT001');
        $qualification->method('getValidTo')->willReturn(new \DateTimeImmutable('+15 days'));
        $qualification->method('getRemainingDays')->willReturn(15);

        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->with(30)
            ->willReturn([$qualification])
        ;

        $this->commandTester->execute(['--format' => 'json']);

        $output = $this->commandTester->getDisplay();

        // 提取JSON部分（从第一个{开始到最后一个}结束）
        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        if (false !== $jsonStart && false !== $jsonEnd) {
            $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
            self::assertJson($jsonString);

            /** @var array<string, mixed>|null $data */
            $data = json_decode($jsonString, true);
            self::assertNotNull($data);
            self::assertIsArray($data);
            self::assertArrayHasKey('summary', $data);
            self::assertArrayHasKey('qualifications', $data);
            self::assertIsArray($data['summary']);
            self::assertArrayHasKey('total', $data['summary']);
            self::assertEquals(1, $data['summary']['total']);
        }

        self::assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试CSV输出格式
     */
    public function testCsvFormat(): void
    {
        /*
         * 使用具体类 Institution 进行 Mock：
         * 1) 为什么必须使用具体类：Institution 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试CSV输出格式时需要可预测的数据
         * 3) 更好的替代方案：可以使用真实 Entity，但 Mock 能确保输出一致性
         */
        $institution = $this->createMock(Institution::class);
        $institution->method('getInstitutionName')->willReturn('测试机构');

        /*
         * 使用具体类 InstitutionQualification 进行 Mock：
         * 1) 为什么必须使用具体类：InstitutionQualification 是 Doctrine Entity，通常不定义接口
         * 2) 使用是否合理：合理，测试CSV输出格式时需要可控的资质数据
         * 3) 更好的替代方案：可以创建真实的 Entity 实例，但 Mock 提供更精确的测试控制
         */
        $qualification = $this->createMock(InstitutionQualification::class);
        $qualification->method('getInstitution')->willReturn($institution);
        $qualification->method('getQualificationName')->willReturn('安全培训资质');
        $qualification->method('getCertificateNumber')->willReturn('CERT001');
        $qualification->method('getValidTo')->willReturn(new \DateTimeImmutable('+15 days'));
        $qualification->method('getRemainingDays')->willReturn(15);

        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->with(30)
            ->willReturn([$qualification])
        ;

        $this->commandTester->execute(['--format' => 'csv']);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('机构名称,资质名称,证书编号,到期日期,剩余天数,状态', $output);
        self::assertStringContainsString('测试机构,安全培训资质,CERT001', $output);
        self::assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试干运行模式
     */
    public function testDryRunMode(): void
    {
        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->with(30)
            ->willReturn([])
        ;

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('运行在干运行模式', $output);
        self::assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试服务异常处理
     */
    public function testServiceException(): void
    {
        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->willThrowException(new \Exception('服务错误'))
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('执行过程中发生错误: 服务错误', $output);
        self::assertEquals(1, $this->commandTester->getStatusCode());
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
}
