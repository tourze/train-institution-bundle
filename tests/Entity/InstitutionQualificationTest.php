<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * InstitutionQualification 实体单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionQualification::class)]
final class InstitutionQualificationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new InstitutionQualification();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'institution' => ['institution', null],
            'qualificationType' => ['qualificationType', 'test_value'],
            'qualificationName' => ['qualificationName', 'test_value'],
            'certificateNumber' => ['certificateNumber', 'test_value'],
            'issuingAuthority' => ['issuingAuthority', 'test_value'],
            'qualificationScope' => ['qualificationScope', ['key' => 'value']],
            'qualificationStatus' => ['qualificationStatus', 'test_value'],
            'attachments' => ['attachments', ['key' => 'value']],
        ];
    }

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::create(
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
     * 测试构造函数
     */
    public function testConstructorSetsDefaultValues(): void
    {
        $qualification = new InstitutionQualification();

        self::assertNotEmpty($qualification->getId());
        self::assertEquals('有效', $qualification->getQualificationStatus());
        self::assertEquals([], $qualification->getQualificationScope());
        self::assertEquals([], $qualification->getAttachments());
        self::assertNull($qualification->getCreateTime());
        self::assertNull($qualification->getUpdateTime());
    }

    /**
     * 测试create静态方法
     */
    public function testCreateWithValidData(): void
    {
        $issueDate = new \DateTimeImmutable('2023-01-01');
        $validFrom = new \DateTimeImmutable('2023-01-01');
        $validTo = new \DateTimeImmutable('2026-01-01');
        $scope = ['特种作业培训', '安全管理培训'];
        $attachments = ['certificate' => 'cert.pdf'];

        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '国家安全监管总局',
            $issueDate,
            $validFrom,
            $validTo,
            $scope,
            '有效',
            $attachments
        );

        self::assertSame($this->institution, $qualification->getInstitution());
        self::assertEquals('安全培训资质', $qualification->getQualificationType());
        self::assertEquals('安全生产培训机构资质证书', $qualification->getQualificationName());
        self::assertEquals('CERT001', $qualification->getCertificateNumber());
        self::assertEquals('国家安全监管总局', $qualification->getIssuingAuthority());
        self::assertSame($issueDate, $qualification->getIssueDate());
        self::assertSame($validFrom, $qualification->getValidFrom());
        self::assertSame($validTo, $qualification->getValidTo());
        self::assertEquals($scope, $qualification->getQualificationScope());
        self::assertEquals('有效', $qualification->getQualificationStatus());
        self::assertEquals($attachments, $qualification->getAttachments());
    }

    /**
     * 测试create静态方法使用默认参数
     */
    public function testCreateWithDefaultParameters(): void
    {
        $issueDate = new \DateTimeImmutable('2023-01-01');
        $validFrom = new \DateTimeImmutable('2023-01-01');
        $validTo = new \DateTimeImmutable('2026-01-01');

        $qualification = InstitutionQualification::create(
            $this->institution,
            '办学许可证',
            '民办学校办学许可证',
            'LICENSE001',
            '教育局',
            $issueDate,
            $validFrom,
            $validTo
        );

        self::assertEquals([], $qualification->getQualificationScope());
        self::assertEquals('有效', $qualification->getQualificationStatus());
        self::assertEquals([], $qualification->getAttachments());
    }

    /**
     * 测试设置和获取机构
     */
    public function testSetInstitutionUpdatesInstitution(): void
    {
        $qualification = new InstitutionQualification();

        $qualification->setInstitution($this->institution);

        self::assertSame($this->institution, $qualification->getInstitution());
    }

    /**
     * 测试设置和获取资质类型
     */
    public function testSetQualificationTypeUpdatesType(): void
    {
        $qualification = new InstitutionQualification();

        $qualification->setQualificationType('特种作业培训资质');

        self::assertEquals('特种作业培训资质', $qualification->getQualificationType());
    }

    /**
     * 测试设置和获取资质名称
     */
    public function testSetQualificationNameUpdatesName(): void
    {
        $qualification = new InstitutionQualification();

        $qualification->setQualificationName('特种作业人员安全技术培训机构资质证书');

        self::assertEquals('特种作业人员安全技术培训机构资质证书', $qualification->getQualificationName());
    }

    /**
     * 测试设置和获取证书编号
     */
    public function testSetCertificateNumberUpdatesNumber(): void
    {
        $qualification = new InstitutionQualification();

        $qualification->setCertificateNumber('CERT2023001');

        self::assertEquals('CERT2023001', $qualification->getCertificateNumber());
    }

    /**
     * 测试设置和获取发证机关
     */
    public function testSetIssuingAuthorityUpdatesAuthority(): void
    {
        $qualification = new InstitutionQualification();

        $qualification->setIssuingAuthority('应急管理部');

        self::assertEquals('应急管理部', $qualification->getIssuingAuthority());
    }

    /**
     * 测试设置和获取发证日期
     */
    public function testSetIssueDateUpdatesDate(): void
    {
        $qualification = new InstitutionQualification();
        $issueDate = new \DateTimeImmutable('2023-06-15');

        $qualification->setIssueDate($issueDate);

        self::assertSame($issueDate, $qualification->getIssueDate());
    }

    /**
     * 测试设置和获取有效期开始日期
     */
    public function testSetValidFromUpdatesDate(): void
    {
        $qualification = new InstitutionQualification();
        $validFrom = new \DateTimeImmutable('2023-07-01');

        $qualification->setValidFrom($validFrom);

        self::assertSame($validFrom, $qualification->getValidFrom());
    }

    /**
     * 测试设置和获取有效期结束日期
     */
    public function testSetValidToUpdatesDate(): void
    {
        $qualification = new InstitutionQualification();
        $validTo = new \DateTimeImmutable('2026-07-01');

        $qualification->setValidTo($validTo);

        self::assertSame($validTo, $qualification->getValidTo());
    }

    /**
     * 测试设置和获取资质范围
     */
    public function testSetQualificationScopeUpdatesScope(): void
    {
        $qualification = new InstitutionQualification();
        $scope = ['电工作业', '焊接作业', '高处作业'];

        $qualification->setQualificationScope($scope);

        self::assertEquals($scope, $qualification->getQualificationScope());
    }

    /**
     * 测试设置和获取资质状态
     */
    public function testSetQualificationStatusUpdatesStatus(): void
    {
        $qualification = new InstitutionQualification();

        $qualification->setQualificationStatus('暂停');

        self::assertEquals('暂停', $qualification->getQualificationStatus());
    }

    /**
     * 测试设置和获取附件
     */
    public function testSetAttachmentsUpdatesAttachments(): void
    {
        $qualification = new InstitutionQualification();
        $attachments = ['certificate' => 'cert.pdf', 'license' => 'license.jpg'];

        $qualification->setAttachments($attachments);

        self::assertEquals($attachments, $qualification->getAttachments());
    }

    /**
     * 测试有效资质检查
     */
    public function testIsValidWithValidQualification(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            ['特种作业培训'],
            '有效'
        );

        self::assertTrue($qualification->isValid());
    }

    /**
     * 测试已过期资质检查
     */
    public function testIsValidWithExpiredQualification(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year'),
            ['特种作业培训'],
            '有效'
        );

        self::assertFalse($qualification->isValid());
    }

    /**
     * 测试状态为非有效的资质检查
     */
    public function testIsValidWithInvalidStatus(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            ['特种作业培训'],
            '暂停'
        );

        self::assertFalse($qualification->isValid());
    }

    /**
     * 测试尚未生效的资质检查
     */
    public function testIsValidWithFutureValidFrom(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('now'),
            new \DateTimeImmutable('+1 month'),
            new \DateTimeImmutable('+1 year'),
            ['特种作业培训'],
            '有效'
        );

        self::assertFalse($qualification->isValid());
    }

    /**
     * 测试即将到期检查 - 默认30天
     */
    public function testIsExpiringSoonWithDefaultDays(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+15 days'),
            ['特种作业培训'],
            '有效'
        );

        self::assertTrue($qualification->isExpiringSoon());
    }

    /**
     * 测试即将到期检查 - 自定义天数
     */
    public function testIsExpiringSoonWithCustomDays(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+45 days'),
            ['特种作业培训'],
            '有效'
        );

        self::assertTrue($qualification->isExpiringSoon(60));
        self::assertFalse($qualification->isExpiringSoon(30));
    }

    /**
     * 测试无效资质不会被认为即将到期
     */
    public function testIsExpiringSoonWithInvalidQualification(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+15 days'),
            ['特种作业培训'],
            '暂停'
        );

        self::assertFalse($qualification->isExpiringSoon());
    }

    /**
     * 测试获取剩余天数 - 有效期内
     */
    public function testGetRemainingDaysWithValidQualification(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+30 days'),
            ['特种作业培训'],
            '有效'
        );

        $remainingDays = $qualification->getRemainingDays();
        self::assertGreaterThanOrEqual(29, $remainingDays);
        self::assertLessThanOrEqual(30, $remainingDays);
    }

    /**
     * 测试获取剩余天数 - 已过期
     */
    public function testGetRemainingDaysWithExpiredQualification(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year'),
            ['特种作业培训'],
            '有效'
        );

        self::assertEquals(0, $qualification->getRemainingDays());
    }

    /**
     * 测试培训类型覆盖检查 - 包含
     */
    public function testCoversTrainingTypeWithCoveredType(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '特种作业培训资质',
            '特种作业人员安全技术培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            ['电工作业', '焊接作业', '高处作业'],
            '有效'
        );

        self::assertTrue($qualification->coversTrainingType('电工作业'));
        self::assertTrue($qualification->coversTrainingType('焊接作业'));
        self::assertTrue($qualification->coversTrainingType('高处作业'));
    }

    /**
     * 测试培训类型覆盖检查 - 不包含
     */
    public function testCoversTrainingTypeWithUncoveredType(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '特种作业培训资质',
            '特种作业人员安全技术培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            ['电工作业', '焊接作业'],
            '有效'
        );

        self::assertFalse($qualification->coversTrainingType('高处作业'));
        self::assertFalse($qualification->coversTrainingType('起重机械作业'));
    }

    /**
     * 测试空范围的培训类型覆盖检查
     */
    public function testCoversTrainingTypeWithEmptyScope(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '办学许可证',
            '民办学校办学许可证',
            'LICENSE001',
            '教育局',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            [],
            '有效'
        );

        self::assertFalse($qualification->coversTrainingType('任何培训类型'));
    }

    /**
     * 测试资质续期 - 仅更新有效期
     */
    public function testRenewWithOnlyNewValidTo(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 month'),
            ['特种作业培训'],
            '有效'
        );

        $originalCertNumber = $qualification->getCertificateNumber();
        $newValidTo = new \DateTimeImmutable('+3 years');

        $result = $qualification->renew($newValidTo);

        self::assertSame($qualification, $result);
        self::assertSame($newValidTo, $qualification->getValidTo());
        self::assertEquals($originalCertNumber, $qualification->getCertificateNumber());
        self::assertEquals('有效', $qualification->getQualificationStatus());
    }

    /**
     * 测试资质续期 - 更新有效期和证书编号
     */
    public function testRenewWithNewValidToAndCertificateNumber(): void
    {
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 month'),
            ['特种作业培训'],
            '暂停'
        );

        $newValidTo = new \DateTimeImmutable('+3 years');
        $newCertNumber = 'CERT002';

        $result = $qualification->renew($newValidTo, $newCertNumber);

        self::assertSame($qualification, $result);
        self::assertSame($newValidTo, $qualification->getValidTo());
        self::assertEquals($newCertNumber, $qualification->getCertificateNumber());
        self::assertEquals('有效', $qualification->getQualificationStatus());
    }

    /**
     * 测试边界条件 - 当天到期
     */
    public function testIsValidWithExpiringToday(): void
    {
        $today = new \DateTimeImmutable('today');
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            $today,
            ['特种作业培训'],
            '有效'
        );

        self::assertFalse($qualification->isValid());
    }

    /**
     * 测试边界条件 - 当天生效
     */
    public function testIsValidWithValidFromToday(): void
    {
        $today = new \DateTimeImmutable('today');
        $qualification = InstitutionQualification::create(
            $this->institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            $today,
            new \DateTimeImmutable('+1 year'),
            ['特种作业培训'],
            '有效'
        );

        self::assertTrue($qualification->isValid());
    }
}
