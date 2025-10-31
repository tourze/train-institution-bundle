<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\DuplicateCertificateNumberException;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;
use Tourze\TrainInstitutionBundle\Exception\QualificationExpiredException;
use Tourze\TrainInstitutionBundle\Exception\QualificationNotFoundException;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * QualificationService 集成测试
 *
 * @internal
 */
#[CoversClass(QualificationService::class)]
#[RunTestsInSeparateProcesses]
final class QualificationServiceTest extends AbstractIntegrationTestCase
{
    private QualificationService $service;

    public function testServiceExists(): void
    {
        // 验证服务能正常执行基本操作（类型系统已保证为QualificationService）
        $this->expectException(InvalidQualificationDataException::class);
        $this->service->addQualification('non-existent-id', []);
    }

    public function testAddQualification(): void
    {
        // 创建一个真实的机构用于测试
        $institutionData = [
            'institutionName' => '测试机构_资质测试',
            'institutionCode' => 'TEST_QUAL_' . uniqid(),
            'registrationNumber' => 'REG_QUAL_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ];

        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution($institutionData);

        $qualificationData = $this->getValidQualificationData();
        // 使用唯一的证书号
        $qualificationData['certificateNumber'] = 'CERT-2024-' . uniqid();

        $result = $this->service->addQualification($institution->getId(), $qualificationData);

        // 类型系统保证返回InstitutionQualification，验证业务逻辑
        self::assertSame($qualificationData['qualificationType'], $result->getQualificationType());
        self::assertSame($qualificationData['certificateNumber'], $result->getCertificateNumber());
        $resultInstitution = $result->getInstitution();
        self::assertNotNull($resultInstitution);
        self::assertSame($institution->getId(), $resultInstitution->getId());
    }

    public function testAddQualificationWithInvalidInstitution(): void
    {
        $institutionId = 'invalid-institution-id';
        $qualificationData = $this->getValidQualificationData();

        $this->expectException(InstitutionNotFoundException::class);

        $this->service->addQualification($institutionId, $qualificationData);
    }

    /**
     * @param array<string, mixed> $qualificationData
     */
    #[DataProvider('invalidQualificationDataProvider')]
    public function testAddQualificationWithInvalidData(array $qualificationData): void
    {
        $this->expectException(InvalidQualificationDataException::class);

        $this->service->addQualification('dummy-id', $qualificationData);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function invalidQualificationDataProvider(): array
    {
        return [
            'missing qualificationType' => [
                [
                    'qualificationName' => '安全生产培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                ],
            ],
            'missing certificateNumber' => [
                [
                    'qualificationType' => '职业技能培训资质',
                    'qualificationName' => '安全生产培训资质',
                    'issuingAuthority' => '应急管理部',
                ],
            ],
            'invalid date range' => [
                [
                    'qualificationType' => '职业技能培训资质',
                    'qualificationName' => '安全生产培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                    'validFrom' => new \DateTimeImmutable('2025-01-01'),
                    'validTo' => new \DateTimeImmutable('2024-01-01'), // 结束日期早于开始日期
                ],
            ],
        ];
    }

    protected function onSetUp(): void
    {
        $this->service = self::getService(QualificationService::class);
    }

    public function testCheckQualificationExpiry(): void
    {
        // 创建机构和多个资质（不同到期状态）
        $institution = $this->createTestInstitution();

        // 有效资质（距离到期>60天）
        $validQual = $this->createTestQualification($institution, '2025-12-31');
        // 即将到期资质（距离到期<=30天）
        $expiringSoonQual = $this->createTestQualification($institution, '2025-10-15');
        // 警告状态资质（距离到期31-60天）
        $warningQual = $this->createTestQualification($institution, '2025-11-20');

        $result = $this->service->checkQualificationExpiry($institution->getId());

        // 验证返回数组结构
        self::assertCount(3, $result);

        // 验证每个元素包含必要字段
        foreach ($result as $info) {
            self::assertArrayHasKey('qualification', $info);
            self::assertArrayHasKey('remaining_days', $info);
            self::assertArrayHasKey('status', $info);
            self::assertArrayHasKey('is_valid', $info);
            self::assertInstanceOf(InstitutionQualification::class, $info['qualification']);
        }
    }

    public function testCheckQualificationExpiryWithInvalidInstitution(): void
    {
        $this->expectException(InstitutionNotFoundException::class);

        $this->service->checkQualificationExpiry('invalid-id');
    }

    public function testRenewQualification(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2025-10-31');

        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-10-31'),
        ];

        $result = $this->service->renewQualification($qualification->getId(), $renewalData);

        // 验证续期成功
        self::assertSame($qualification->getId(), $result->getId());
        self::assertSame('2027-10-31', $result->getValidTo()->format('Y-m-d'));
        self::assertSame('有效', $result->getQualificationStatus());
    }

    public function testRenewQualificationWithNewCertificateNumber(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2025-10-31');

        $newCertNumber = 'RENEWED-CERT-' . uniqid();
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-10-31'),
            'newCertificateNumber' => $newCertNumber,
        ];

        $result = $this->service->renewQualification($qualification->getId(), $renewalData);

        self::assertSame($newCertNumber, $result->getCertificateNumber());
        self::assertSame('有效', $result->getQualificationStatus());
    }

    public function testRenewQualificationWithInvalidId(): void
    {
        $this->expectException(QualificationNotFoundException::class);

        $this->service->renewQualification('invalid-id', [
            'newValidTo' => new \DateTimeImmutable('2027-12-31'),
        ]);
    }

    public function testRenewQualificationWithMissingValidTo(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2025-10-31');

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('新的有效期结束日期不能为空');

        $this->service->renewQualification($qualification->getId(), []);
    }

    public function testRenewQualificationWithPastValidTo(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2025-10-31');

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('新的有效期结束日期必须是未来日期');

        $this->service->renewQualification($qualification->getId(), [
            'newValidTo' => new \DateTimeImmutable('2020-01-01'),
        ]);
    }

    public function testRenewQualificationWithDuplicateCertificateNumber(): void
    {
        $institution = $this->createTestInstitution();
        $qualification1 = $this->createTestQualification($institution, '2025-10-31', 'CERT-EXISTING');
        $qualification2 = $this->createTestQualification($institution, '2025-10-31', 'CERT-TO-RENEW');

        $this->expectException(DuplicateCertificateNumberException::class);

        $this->service->renewQualification($qualification2->getId(), [
            'newValidTo' => new \DateTimeImmutable('2027-12-31'),
            'newCertificateNumber' => 'CERT-EXISTING',
        ]);
    }

    public function testRestoreQualification(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2026-12-31');

        // 先暂停资质
        $this->service->suspendQualification($qualification->getId(), '测试暂停');

        // 验证已暂停
        $entityManager = self::getService(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->refresh($qualification);
        self::assertSame('暂停', $qualification->getQualificationStatus());

        // 恢复资质
        $result = $this->service->restoreQualification($qualification->getId());

        self::assertSame('有效', $result->getQualificationStatus());
    }

    public function testRestoreQualificationWithExpiredQualification(): void
    {
        $institution = $this->createTestInstitution();
        // 创建已过期资质（有效期到昨天）
        $qualification = $this->createTestQualification(
            $institution,
            '2020-12-31',
            null,
            '2020-01-01'
        );

        $this->expectException(QualificationExpiredException::class);

        $this->service->restoreQualification($qualification->getId());
    }

    public function testRestoreQualificationWithInvalidId(): void
    {
        $this->expectException(QualificationNotFoundException::class);

        $this->service->restoreQualification('invalid-id');
    }

    public function testRevokeQualification(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2026-12-31');

        $result = $this->service->revokeQualification($qualification->getId(), '违规撤销');

        self::assertSame('已撤销', $result->getQualificationStatus());
    }

    public function testRevokeQualificationWithInvalidId(): void
    {
        $this->expectException(QualificationNotFoundException::class);

        $this->service->revokeQualification('invalid-id', '测试原因');
    }

    public function testSuspendQualification(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2026-12-31');

        $result = $this->service->suspendQualification($qualification->getId(), '正在审查');

        self::assertSame('暂停', $result->getQualificationStatus());
    }

    public function testSuspendQualificationWithInvalidId(): void
    {
        $this->expectException(QualificationNotFoundException::class);

        $this->service->suspendQualification('invalid-id', '测试原因');
    }

    public function testUpdateQualification(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2026-12-31');

        $updateData = [
            'qualificationName' => '更新后的资质名称',
            'issuingAuthority' => '新的发证机关',
            'qualificationScope' => ['新培训类型1', '新培训类型2'],
        ];

        $result = $this->service->updateQualification($qualification->getId(), $updateData);

        self::assertSame('更新后的资质名称', $result->getQualificationName());
        self::assertSame('新的发证机关', $result->getIssuingAuthority());
        self::assertCount(2, $result->getQualificationScope());
        self::assertContains('新培训类型1', $result->getQualificationScope());
    }

    public function testUpdateQualificationWithInvalidId(): void
    {
        $this->expectException(QualificationNotFoundException::class);

        $this->service->updateQualification('invalid-id', [
            'qualificationName' => '测试名称',
        ]);
    }

    public function testUpdateQualificationWithDuplicateCertificateNumber(): void
    {
        $institution = $this->createTestInstitution();
        $qualification1 = $this->createTestQualification($institution, '2026-12-31', 'CERT-EXISTING');
        $qualification2 = $this->createTestQualification($institution, '2026-12-31', 'CERT-TO-UPDATE');

        $this->expectException(DuplicateCertificateNumberException::class);

        $this->service->updateQualification($qualification2->getId(), [
            'certificateNumber' => 'CERT-EXISTING',
        ]);
    }

    public function testUpdateQualificationWithInvalidDateRange(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2026-12-31');

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('有效期开始日期必须早于结束日期');

        $this->service->updateQualification($qualification->getId(), [
            'validFrom' => new \DateTimeImmutable('2026-12-01'),
            'validTo' => new \DateTimeImmutable('2026-01-01'),
        ]);
    }

    public function testValidateQualificationScope(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2026-12-31');

        // 资质范围包含：特种作业人员培训、安全管理人员培训、危险化学品培训
        $scope = [
            'training_types' => ['特种作业人员培训', '安全管理人员培训'],
        ];

        $result = $this->service->validateQualificationScope($qualification->getId(), $scope);

        self::assertTrue($result);
    }

    public function testValidateQualificationScopeWithUncoveredType(): void
    {
        $institution = $this->createTestInstitution();
        $qualification = $this->createTestQualification($institution, '2026-12-31');

        $scope = [
            'training_types' => ['特种作业人员培训', '不存在的培训类型'],
        ];

        $result = $this->service->validateQualificationScope($qualification->getId(), $scope);

        self::assertFalse($result);
    }

    public function testValidateQualificationScopeWithInvalidQualification(): void
    {
        $institution = $this->createTestInstitution();
        // 创建已过期资质
        $qualification = $this->createTestQualification(
            $institution,
            '2020-12-31',
            null,
            '2020-01-01'
        );

        $scope = [
            'training_types' => ['特种作业人员培训'],
        ];

        $result = $this->service->validateQualificationScope($qualification->getId(), $scope);

        self::assertFalse($result);
    }

    public function testValidateQualificationScopeWithInvalidId(): void
    {
        $this->expectException(QualificationNotFoundException::class);

        $this->service->validateQualificationScope('invalid-id', [
            'training_types' => ['测试类型'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidQualificationData(): array
    {
        return [
            'qualificationType' => '职业技能培训资质',
            'qualificationName' => '安全生产培训资质',
            'certificateNumber' => 'CERT-2024-001',
            'issuingAuthority' => '应急管理部',
            'issueDate' => new \DateTimeImmutable('2024-01-01'),
            'validFrom' => new \DateTimeImmutable('2024-01-01'),
            'validTo' => new \DateTimeImmutable('2026-01-01'),
            'qualificationScope' => [
                '特种作业人员培训',
                '安全管理人员培训',
                '危险化学品培训',
            ],
            'qualificationStatus' => '有效',
            'attachments' => [
                ['name' => '资质证书.pdf', 'path' => '/uploads/cert_2024_001.pdf'],
                ['name' => '资质授权书.pdf', 'path' => '/uploads/auth_2024_001.pdf'],
            ],
        ];
    }

    private function createTestInstitution(): Institution
    {
        $institutionData = [
            'institutionName' => '测试机构_' . uniqid(),
            'institutionCode' => 'TEST_' . uniqid(),
            'registrationNumber' => 'REG_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ];

        $institutionService = self::getService(InstitutionService::class);

        return $institutionService->createInstitution($institutionData);
    }

    private function createTestQualification(
        Institution $institution,
        string $validTo,
        ?string $certNumber = null,
        ?string $validFrom = null,
    ): InstitutionQualification {
        $qualificationData = $this->getValidQualificationData();
        $qualificationData['certificateNumber'] = $certNumber ?? 'CERT-' . uniqid();
        $qualificationData['validTo'] = new \DateTimeImmutable($validTo);

        if (null !== $validFrom) {
            $qualificationData['validFrom'] = new \DateTimeImmutable($validFrom);
            $qualificationData['issueDate'] = new \DateTimeImmutable($validFrom);
        }

        return $this->service->addQualification($institution->getId(), $qualificationData);
    }
}
