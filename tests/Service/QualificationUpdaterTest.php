<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;
use Tourze\TrainInstitutionBundle\Service\QualificationUpdater;

/**
 * QualificationUpdater 单元测试
 *
 * @internal
 */
#[CoversClass(QualificationUpdater::class)]
final class QualificationUpdaterTest extends TestCase
{
    private QualificationUpdater $updater;

    protected function setUp(): void
    {
        $this->updater = new QualificationUpdater();
    }

    public function testApplyQualificationUpdates(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $updateData = [
            'qualificationType' => '新资质类型',
            'qualificationName' => '新资质名称',
            'certificateNumber' => 'NEW-CERT-001',
            'issuingAuthority' => '新发证机关',
            'issueDate' => new \DateTimeImmutable('2024-02-01'),
            'validFrom' => new \DateTimeImmutable('2024-02-01'),
            'validTo' => new \DateTimeImmutable('2026-02-01'),
            'qualificationScope' => ['新培训范围1', '新培训范围2'],
            'qualificationStatus' => '暂停',
            'attachments' => [['name' => '新附件.pdf', 'path' => '/new/path.pdf']],
        ];

        $qualification->expects(self::once())
            ->method('setQualificationType')
            ->with('新资质类型')
        ;

        $qualification->expects(self::once())
            ->method('setQualificationName')
            ->with('新资质名称')
        ;

        $qualification->expects(self::once())
            ->method('setCertificateNumber')
            ->with('NEW-CERT-001')
        ;

        $qualification->expects(self::once())
            ->method('setIssuingAuthority')
            ->with('新发证机关')
        ;

        $qualification->expects(self::once())
            ->method('setIssueDate')
            ->with(self::equalTo(new \DateTimeImmutable('2024-02-01')))
        ;

        $qualification->expects(self::once())
            ->method('setValidFrom')
            ->with(self::equalTo(new \DateTimeImmutable('2024-02-01')))
        ;

        $qualification->expects(self::once())
            ->method('setValidTo')
            ->with(self::equalTo(new \DateTimeImmutable('2026-02-01')))
        ;

        $qualification->expects(self::once())
            ->method('setQualificationScope')
            ->with(['新培训范围1', '新培训范围2'])
        ;

        $qualification->expects(self::once())
            ->method('setQualificationStatus')
            ->with('暂停')
        ;

        $qualification->expects(self::once())
            ->method('setAttachments')
            ->with([['name' => '新附件.pdf', 'path' => '/new/path.pdf']])
        ;

        $this->updater->applyQualificationUpdates($qualification, $updateData);
    }

    public function testApplyQualificationUpdatesWithPartialData(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $updateData = [
            'qualificationType' => '部分更新类型',
            'validTo' => new \DateTimeImmutable('2025-12-31'),
        ];

        $qualification->expects(self::once())
            ->method('setQualificationType')
            ->with('部分更新类型')
        ;

        $qualification->expects(self::once())
            ->method('setValidTo')
            ->with(self::equalTo(new \DateTimeImmutable('2025-12-31')))
        ;

        // 不应调用其他setter
        $qualification->expects(self::never())->method('setQualificationName');
        $qualification->expects(self::never())->method('setCertificateNumber');

        $this->updater->applyQualificationUpdates($qualification, $updateData);
    }

    public function testApplyRenewalUpdates(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
            'newCertificateNumber' => 'RENEWED-CERT-001',
            'issuingAuthority' => '续期发证机关',
            'issueDate' => new \DateTimeImmutable('2024-03-01'),
            'qualificationScope' => ['续期培训范围'],
            'attachments' => [['name' => '续期附件.pdf', 'path' => '/renewal/path.pdf']],
        ];

        $qualification->expects(self::once())
            ->method('renew')
            ->with(
                self::equalTo(new \DateTimeImmutable('2027-01-01')),
                'RENEWED-CERT-001'
            )
        ;

        $qualification->expects(self::once())
            ->method('setIssuingAuthority')
            ->with('续期发证机关')
        ;

        $qualification->expects(self::once())
            ->method('setIssueDate')
            ->with(self::equalTo(new \DateTimeImmutable('2024-03-01')))
        ;

        $qualification->expects(self::once())
            ->method('setQualificationScope')
            ->with(['续期培训范围'])
        ;

        $qualification->expects(self::once())
            ->method('setAttachments')
            ->with([['name' => '续期附件.pdf', 'path' => '/renewal/path.pdf']])
        ;

        $this->updater->applyRenewalUpdates($qualification, $renewalData);
    }

    public function testApplyRenewalUpdatesWithoutOptionalFields(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $renewalData = [
            'newValidTo' => new \DateTimeImmutable('2027-01-01'),
        ];

        $qualification->expects(self::once())
            ->method('renew')
            ->with(
                self::equalTo(new \DateTimeImmutable('2027-01-01')),
                null
            )
        ;

        // 不应调用可选字段的setter
        $qualification->expects(self::never())->method('setIssuingAuthority');
        $qualification->expects(self::never())->method('setIssueDate');
        $qualification->expects(self::never())->method('setQualificationScope');
        $qualification->expects(self::never())->method('setAttachments');

        $this->updater->applyRenewalUpdates($qualification, $renewalData);
    }

    public function testApplyRenewalUpdatesWithInvalidDate(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $renewalData = [
            'newValidTo' => 'invalid-date', // 非DateTimeImmutable
        ];

        $this->expectException(InvalidQualificationDataException::class);
        $this->expectExceptionMessage('新的有效期结束日期必须是有效的日期');

        $this->updater->applyRenewalUpdates($qualification, $renewalData);
    }

    #[DataProvider('stringDateProvider')]
    public function testUpdateFieldWithStringDate(string $fieldName, string $setterName): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $updateData = [
            $fieldName => '2024-06-01', // 字符串日期
        ];

        $qualification->expects(self::once())
            ->method($setterName)
            ->with(self::equalTo(new \DateTimeImmutable('2024-06-01')))
        ;

        $this->updater->applyQualificationUpdates($qualification, $updateData);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function stringDateProvider(): array
    {
        return [
            'issueDate' => ['issueDate', 'setIssueDate'],
            'validFrom' => ['validFrom', 'setValidFrom'],
            'validTo' => ['validTo', 'setValidTo'],
        ];
    }

    public function testUpdateQualificationScopeWithMixedTypes(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $updateData = [
            'qualificationScope' => [
                '有效范围1',
                '',  // 空字符串应被过滤
                123, // 数字应被转换
                '有效范围2',
            ],
        ];

        $qualification->expects(self::once())
            ->method('setQualificationScope')
            ->with(['有效范围1', '123', '有效范围2'])
        ;

        $this->updater->applyQualificationUpdates($qualification, $updateData);
    }

    public function testUpdateQualificationScopeWithInvalidType(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $updateData = [
            'qualificationScope' => 'not-an-array',
        ];

        // 当qualificationScope不是数组时，不应调用setter
        $qualification->expects(self::never())->method('setQualificationScope');

        $this->updater->applyQualificationUpdates($qualification, $updateData);
    }

    public function testUpdateAttachmentsWithInvalidType(): void
    {
        /** @var InstitutionQualification&MockObject $qualification */
        $qualification = $this->createMockQualification();

        $updateData = [
            'attachments' => 'not-an-array',
        ];

        $qualification->expects(self::once())
            ->method('setAttachments')
            ->with([])
        ;

        $this->updater->applyQualificationUpdates($qualification, $updateData);
    }

    private function createMockQualification(): InstitutionQualification
    {
        return $this->createMock(InstitutionQualification::class);
    }
}
