<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * QualificationService 单元测试
 */
class QualificationServiceTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&\Tourze\TrainInstitutionBundle\Repository\InstitutionRepository $institutionRepository;
    private MockObject&InstitutionQualificationRepository $qualificationRepository;
    private QualificationService $qualificationService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->institutionRepository = $this->createMock(\Tourze\TrainInstitutionBundle\Repository\InstitutionRepository::class);
        $this->qualificationRepository = $this->createMock(InstitutionQualificationRepository::class);

        $this->qualificationService = new QualificationService(
            $this->entityManager,
            $this->institutionRepository,
            $this->qualificationRepository
        );
    }

    /**
     * 测试添加资质
     */
    public function testAddQualification(): void
    {
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn('institution-id');

        $qualificationData = [
            'id' => 'qualification-id',
            'qualificationType' => '安全培训资质',
            'qualificationName' => '安全生产培训机构资质证书',
            'certificateNumber' => 'CERT001',
            'issuingAuthority' => '国家安全监管总局',
            'issueDate' => new \DateTimeImmutable('2023-01-01'),
            'validFrom' => new \DateTimeImmutable('2023-01-01'),
            'validTo' => new \DateTimeImmutable('2026-01-01'),
            'qualificationScope' => ['特种作业培训'],
        ];

        // 模拟获取机构
        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with('institution-id')
            ->willReturn($institution);

        // 模拟检查证书编号唯一性
        $this->qualificationRepository
            ->expects($this->once())
            ->method('isCertificateNumberExists')
            ->with('CERT001')
            ->willReturn(false);

        // 模拟保存操作
        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $qualification = $this->qualificationService->addQualification('institution-id', $qualificationData);

        $this->assertInstanceOf(InstitutionQualification::class, $qualification);
    }

    /**
     * 测试证书编号重复
     */
    public function testAddQualificationWithDuplicateCertificateNumber(): void
    {
        $qualificationData = [
            'qualificationType' => '安全培训资质',
            'qualificationName' => '安全生产培训机构资质证书',
            'certificateNumber' => 'CERT001',
            'issuingAuthority' => '国家安全监管总局',
            'issueDate' => new \DateTimeImmutable('2023-01-01'),
            'validFrom' => new \DateTimeImmutable('2023-01-01'),
            'validTo' => new \DateTimeImmutable('2026-01-01'),
            'qualificationScope' => ['特种作业培训'],
        ];

        // 模拟获取机构
        $institution = $this->createMock(Institution::class);
        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with('institution-id')
            ->willReturn($institution);

        // 模拟证书编号已存在
        $this->qualificationRepository
            ->expects($this->once())
            ->method('isCertificateNumberExists')
            ->with('CERT001')
            ->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('证书编号已存在');

        $this->qualificationService->addQualification('institution-id', $qualificationData);
    }

    /**
     * 测试检查资质到期
     */
    public function testCheckQualificationExpiry(): void
    {
        $institutionId = 'institution-id';
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn($institutionId);

        // 模拟获取机构
        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $qualification1 = $this->createMock(InstitutionQualification::class);
        $qualification1->method('getRemainingDays')->willReturn(15);
        $qualification1->method('isValid')->willReturn(true);

        $qualification2 = $this->createMock(InstitutionQualification::class);
        $qualification2->method('getRemainingDays')->willReturn(5);
        $qualification2->method('isValid')->willReturn(true);

        $expiringQualifications = [$qualification1, $qualification2];

        $this->qualificationRepository
            ->expects($this->once())
            ->method('findByInstitution')
            ->with($institution)
            ->willReturn($expiringQualifications);

        $result = $this->qualificationService->checkQualificationExpiry($institutionId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * 测试获取即将到期的资质
     */
    public function testGetExpiringQualifications(): void
    {
        $days = 15;
        $expiringQualifications = [
            $this->createMock(InstitutionQualification::class),
        ];

        $this->qualificationRepository
            ->expects($this->once())
            ->method('findExpiringSoon')
            ->with($days)
            ->willReturn($expiringQualifications);

        $result = $this->qualificationService->getExpiringQualifications($days);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * 测试续期资质
     */
    public function testRenewQualification(): void
    {
        $qualificationId = 'qualification-id';
        $qualification = $this->createMock(InstitutionQualification::class);

        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
            'newCertificateNumber' => 'CERT002',
        ];

        $this->qualificationRepository
            ->expects($this->once())
            ->method('find')
            ->with($qualificationId)
            ->willReturn($qualification);

        $qualification
            ->expects($this->once())
            ->method('renew')
            ->with($renewalData['newValidTo'], $renewalData['newCertificateNumber']);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->qualificationService->renewQualification($qualificationId, $renewalData);

        $this->assertSame($qualification, $result);
    }

    /**
     * 测试资质不存在的情况
     */
    public function testRenewNonExistentQualification(): void
    {
        $qualificationId = 'non-existent-id';
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
        ];

        $this->qualificationRepository
            ->expects($this->once())
            ->method('find')
            ->with($qualificationId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('资质不存在');

        $this->qualificationService->renewQualification($qualificationId, $renewalData);
    }

    /**
     * 测试验证资质范围
     */
    public function testValidateQualificationScope(): void
    {
        $qualificationId = 'qualification-id';
        $qualification = $this->createMock(InstitutionQualification::class);
        $scope = ['特种作业培训'];

        $this->qualificationRepository
            ->expects($this->once())
            ->method('find')
            ->with($qualificationId)
            ->willReturn($qualification);

        $qualification
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $qualification
            ->expects($this->once())
            ->method('coversTrainingType')
            ->with('特种作业培训')
            ->willReturn(true);

        $result = $this->qualificationService->validateQualificationScope($qualificationId, $scope);

        $this->assertTrue($result);
    }
} 