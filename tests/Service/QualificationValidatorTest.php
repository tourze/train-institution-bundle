<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\DuplicateCertificateNumberException;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;
use Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository;
use Tourze\TrainInstitutionBundle\Service\QualificationValidator;

/**
 * QualificationValidator 单元测试
 *
 * @internal
 */
#[CoversClass(QualificationValidator::class)]
final class QualificationValidatorTest extends TestCase
{
    private QualificationValidator $validator;

    private InstitutionQualificationRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(InstitutionQualificationRepository::class);
        $this->validator = new QualificationValidator($this->repository);
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
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();
        $qualification->method('getValidFrom')
            ->willReturn(new \DateTimeImmutable('2024-01-01'))
        ;
        $qualification->method('getValidTo')
            ->willReturn(new \DateTimeImmutable('2026-01-01'))
        ;

        // 有效的资质对象不应抛出异常
        $this->validator->validateUpdatedQualification($qualification);

        $this->expectNotToPerformAssertions();
    }

    public function testValidateUpdatedQualificationWithInvalidDates(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();
        $qualification->method('getValidFrom')
            ->willReturn(new \DateTimeImmutable('2026-01-01'))
        ;
        $qualification->method('getValidTo')
            ->willReturn(new \DateTimeImmutable('2024-01-01'))
        ;

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('有效期开始日期必须早于结束日期');

        $this->validator->validateUpdatedQualification($qualification);
    }

    public function testValidateRenewalDataWithValidData(): void
    {
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
        ];

        /** @var InstitutionQualificationRepository&MockObject $repository */
        $repository = $this->repository;
        $repository->method('isCertificateNumberExists')
            ->willReturn(false)
        ;

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
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
            'newCertificateNumber' => 'EXISTING-CERT-001',
        ];

        /** @var InstitutionQualificationRepository&MockObject $repository */
        $repository = $this->repository;
        $repository->method('isCertificateNumberExists')
            ->with('EXISTING-CERT-001', 'test-qualification-id')
            ->willReturn(true)
        ;

        $this->expectException(DuplicateCertificateNumberException::class);

        $this->validator->validateRenewalData($renewalData, 'test-qualification-id');
    }

    public function testValidateRenewalDataWithNonStringCertificateNumber(): void
    {
        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
            'newCertificateNumber' => 123, // 非字符串
        ];

        // 非字符串的证书编号不应调用重复检查
        /** @var InstitutionQualificationRepository&MockObject $repository */
        $repository = $this->repository;
        $repository->expects(self::never())
            ->method('isCertificateNumberExists')
        ;

        // 有效的续期数据不应抛出异常
        $this->validator->validateRenewalData($renewalData, 'test-qualification-id');

        // expects() already provides the assertion, so we don't need expectNotToPerformAssertions()
    }

    private function createMockQualification(): InstitutionQualification
    {
        return $this->createMock(InstitutionQualification::class);
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
