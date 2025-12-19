<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\DuplicateCertificateNumberException;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;
use Tourze\TrainInstitutionBundle\Contract\InstitutionQualificationRepositoryInterface;
use Tourze\TrainInstitutionBundle\Service\QualificationValidator;

/**
 * QualificationValidator 集成测试
 *
 * @internal
 */
#[CoversClass(QualificationValidator::class)]
#[RunTestsInSeparateProcesses]
final class QualificationValidatorTest extends AbstractIntegrationTestCase
{
    private QualificationValidator $validator;

    private InstitutionQualificationRepositoryInterface $repository;

    protected function onSetUp(): void
    {
        $this->validator = self::getService(QualificationValidator::class);
        $this->repository = self::getService(InstitutionQualificationRepositoryInterface::class);
    }

    public function testValidateQualificationDataWithValidData(): void
    {
        $validData = $this->getValidQualificationData();

        // 有效数据不应抛出异常
        $this->validator->validateQualificationData($validData);

        // 如果没有异常抛出，测试通过
        $this->expectNotToPerformAssertions();
    }

    /**
     * @param array<string, mixed> $invalidData
     */
    #[DataProvider('invalidQualificationDataProvider')]
    public function testValidateQualificationDataWithInvalidData(array $invalidData, string $expectedMessage): void
    {
        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validateQualificationData($invalidData);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function invalidQualificationDataProvider(): array
    {
        return [
            'missing qualificationType' => [
                [
                    'qualificationName' => '安全生产培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                    'issueDate' => new \DateTimeImmutable('2024-01-01'),
                    'validFrom' => new \DateTimeImmutable('2024-01-01'),
                    'validTo' => new \DateTimeImmutable('2026-01-01'),
                ],
                '资质类型不能为空',
            ],
            'missing qualificationName' => [
                [
                    'qualificationType' => '职业技能培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                    'issueDate' => new \DateTimeImmutable('2024-01-01'),
                    'validFrom' => new \DateTimeImmutable('2024-01-01'),
                    'validTo' => new \DateTimeImmutable('2026-01-01'),
                ],
                '资质名称不能为空',
            ],
            'missing certificateNumber' => [
                [
                    'qualificationType' => '职业技能培训资质',
                    'qualificationName' => '安全生产培训资质',
                    'issuingAuthority' => '应急管理部',
                    'issueDate' => new \DateTimeImmutable('2024-01-01'),
                    'validFrom' => new \DateTimeImmutable('2024-01-01'),
                    'validTo' => new \DateTimeImmutable('2026-01-01'),
                ],
                '证书编号不能为空',
            ],
            'empty string qualificationType' => [
                [
                    'qualificationType' => '',
                    'qualificationName' => '安全生产培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                    'issueDate' => new \DateTimeImmutable('2024-01-01'),
                    'validFrom' => new \DateTimeImmutable('2024-01-01'),
                    'validTo' => new \DateTimeImmutable('2026-01-01'),
                ],
                '资质类型不能为空',
            ],
            'invalid issueDate format' => [
                [
                    'qualificationType' => '职业技能培训资质',
                    'qualificationName' => '安全生产培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                    'issueDate' => 'invalid-date',
                    'validFrom' => new \DateTimeImmutable('2024-01-01'),
                    'validTo' => new \DateTimeImmutable('2026-01-01'),
                ],
                '发证日期必须是有效的日期格式',
            ],
            'invalid validFrom format' => [
                [
                    'qualificationType' => '职业技能培训资质',
                    'qualificationName' => '安全生产培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                    'issueDate' => new \DateTimeImmutable('2024-01-01'),
                    'validFrom' => 123,
                    'validTo' => new \DateTimeImmutable('2026-01-01'),
                ],
                '有效期开始日期必须是有效的日期格式',
            ],
            'invalid validTo format' => [
                [
                    'qualificationType' => '职业技能培训资质',
                    'qualificationName' => '安全生产培训资质',
                    'certificateNumber' => 'CERT-2024-001',
                    'issuingAuthority' => '应急管理部',
                    'issueDate' => new \DateTimeImmutable('2024-01-01'),
                    'validFrom' => new \DateTimeImmutable('2024-01-01'),
                    'validTo' => null,
                ],
                '有效期结束日期不能为空',
            ],
        ];
    }

    public function testValidateDateRangeWithValidRange(): void
    {
        $validData = [
            'validFrom' => new \DateTimeImmutable('2024-01-01'),
            'validTo' => new \DateTimeImmutable('2026-01-01'),
        ];

        // 有效日期范围不应抛出异常
        $this->validator->validateDateRange($validData);

        $this->expectNotToPerformAssertions();
    }

    public function testValidateDateRangeWithInvalidRange(): void
    {
        $invalidData = [
            'validFrom' => new \DateTimeImmutable('2026-01-01'),
            'validTo' => new \DateTimeImmutable('2024-01-01'),
        ];

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('有效期开始日期必须早于结束日期');

        $this->validator->validateDateRange($invalidData);
    }

    public function testValidateDateRangeWithSameDate(): void
    {
        $invalidData = [
            'validFrom' => new \DateTimeImmutable('2024-01-01'),
            'validTo' => new \DateTimeImmutable('2024-01-01'),
        ];

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('有效期开始日期必须早于结束日期');

        $this->validator->validateDateRange($invalidData);
    }

    public function testValidateUpdatedQualificationWithValidDates(): void
    {
        // 创建一个真实的资质对象
        $institution = Institution::create(
            '测试机构',
            'TEST001',
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

        $qualification = new InstitutionQualification();
        $qualification->setInstitution($institution);
        $qualification->setQualificationType('职业技能培训资质');
        $qualification->setQualificationName('安全生产培训资质');
        $qualification->setCertificateNumber('CERT-2024-001');
        $qualification->setIssuingAuthority('应急管理部');
        $qualification->setIssueDate(new \DateTimeImmutable('2024-01-01'));
        $qualification->setValidFrom(new \DateTimeImmutable('2024-01-01'));
        $qualification->setValidTo(new \DateTimeImmutable('2026-01-01'));

        // 有效的资质对象不应抛出异常
        $this->validator->validateUpdatedQualification($qualification);

        $this->expectNotToPerformAssertions();
    }

    public function testValidateUpdatedQualificationWithInvalidDates(): void
    {
        // 创建一个真实的资质对象，但日期无效
        $institution = Institution::create(
            '测试机构',
            'TEST001',
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

        $qualification = new InstitutionQualification();
        $qualification->setInstitution($institution);
        $qualification->setQualificationType('职业技能培训资质');
        $qualification->setQualificationName('安全生产培训资质');
        $qualification->setCertificateNumber('CERT-2024-001');
        $qualification->setIssuingAuthority('应急管理部');
        $qualification->setIssueDate(new \DateTimeImmutable('2024-01-01'));
        $qualification->setValidFrom(new \DateTimeImmutable('2026-01-01'));
        $qualification->setValidTo(new \DateTimeImmutable('2024-01-01'));

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('有效期开始日期必须早于结束日期');

        $this->validator->validateUpdatedQualification($qualification);
    }

    public function testValidateRenewalDataWithValidData(): void
    {
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
        ];

        // 有效的续期数据不应抛出异常
        $this->validator->validateRenewalData($renewalData, 'test-qualification-id');

        $this->expectNotToPerformAssertions();
    }

    public function testValidateRenewalDataWithMissingNewValidTo(): void
    {
        $renewalData = [];

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('新的有效期结束日期不能为空');

        $this->validator->validateRenewalData($renewalData, 'test-qualification-id');
    }

    public function testValidateRenewalDataWithPastDate(): void
    {
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2020-01-01'), // 过去的日期
        ];

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('新的有效期结束日期必须是未来日期');

        $this->validator->validateRenewalData($renewalData, 'test-qualification-id');
    }

    public function testValidateRenewalDataWithDuplicateCertificateNumber(): void
    {
        // 首先创建一个包含目标证书编号的资质
        $institution = Institution::create(
            '测试机构',
            'TEST-DUP-' . uniqid(),
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG-' . uniqid()
        );

        $entityManager = self::getEntityManager();
        $entityManager->persist($institution);
        $entityManager->flush();

        $existingQualification = InstitutionQualification::create(
            $institution,
            '职业技能培训资质',
            '安全生产培训资质',
            'EXISTING-CERT-001',  // 这个证书编号会与续期数据冲突
            '应急管理部',
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2026-01-01')
        );
        $entityManager->persist($existingQualification);
        $entityManager->flush();

        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
            'newCertificateNumber' => 'EXISTING-CERT-001',  // 使用已存在的证书编号
        ];

        $this->expectException(DuplicateCertificateNumberException::class);

        // 使用不同的资质ID进行续期验证
        $this->validator->validateRenewalData($renewalData, 'different-qualification-id');
    }

    public function testValidateRenewalDataWithNonStringCertificateNumber(): void
    {
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
            'newCertificateNumber' => 123, // 非字符串
        ];

        // 有效的续期数据不应抛出异常
        $this->validator->validateRenewalData($renewalData, 'test-qualification-id');

        $this->expectNotToPerformAssertions();
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
        ];
    }
}
