<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TrainInstitutionBundle\Command\QualificationExpiryCheckCommand;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * QualificationExpiryCheckCommand 单元测试
 */
class QualificationExpiryCheckCommandTest extends TestCase
{
    private MockObject&QualificationService $qualificationService;
    private QualificationExpiryCheckCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->qualificationService = $this->createMock(QualificationService::class);
        
        $this->command = new QualificationExpiryCheckCommand($this->qualificationService);
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
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
            ->willReturn([]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('未发现30天内到期的资质', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试有即将到期的资质
     */
    public function testWithExpiringQualifications(): void
    {
        $institution = $this->createMock(Institution::class);
        $institution->method('getInstitutionName')->willReturn('测试机构');

        $qualification1 = $this->createMock(InstitutionQualification::class);
        $qualification1->method('getInstitution')->willReturn($institution);
        $qualification1->method('getQualificationName')->willReturn('安全培训资质');
        $qualification1->method('getCertificateNumber')->willReturn('CERT001');
        $qualification1->method('getValidTo')->willReturn(new \DateTimeImmutable('+15 days'));
        $qualification1->method('getRemainingDays')->willReturn(15);

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
            ->willReturn($expiringQualifications);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('发现 2 个即将到期的资质', $output);
        $this->assertStringContainsString('测试机构', $output);
        $this->assertStringContainsString('安全培训资质', $output);
        $this->assertStringContainsString('CERT001', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
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
            ->willReturn([]);

        $this->commandTester->execute(['--days' => '60']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('未发现60天内到期的资质', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试JSON输出格式
     */
    public function testJsonFormat(): void
    {
        $institution = $this->createMock(Institution::class);
        $institution->method('getInstitutionName')->willReturn('测试机构');

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
            ->willReturn([$qualification]);

        $this->commandTester->execute(['--format' => 'json']);

        $output = $this->commandTester->getDisplay();
        
        // 提取JSON部分（从第一个{开始到最后一个}结束）
        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
            $this->assertJson($jsonString);
            
            $data = json_decode($jsonString, true);
            $this->assertArrayHasKey('summary', $data);
            $this->assertArrayHasKey('qualifications', $data);
            $this->assertEquals(1, $data['summary']['total']);
        }
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试CSV输出格式
     */
    public function testCsvFormat(): void
    {
        $institution = $this->createMock(Institution::class);
        $institution->method('getInstitutionName')->willReturn('测试机构');

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
            ->willReturn([$qualification]);

        $this->commandTester->execute(['--format' => 'csv']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('机构名称,资质名称,证书编号,到期日期,剩余天数,状态', $output);
        $this->assertStringContainsString('测试机构,安全培训资质,CERT001', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
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
            ->willReturn([]);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('运行在干运行模式', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * 测试服务异常处理
     */
    public function testServiceException(): void
    {
        $this->qualificationService
            ->expects($this->once())
            ->method('getExpiringQualifications')
            ->willThrowException(new \Exception('服务错误'));

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('执行过程中发生错误: 服务错误', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }
} 