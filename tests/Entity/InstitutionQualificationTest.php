<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * InstitutionQualification 实体单元测试
 */
class InstitutionQualificationTest extends TestCase
{
    private Institution $institution;

    protected function setUp(): void
    {
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
    public function test_constructor_setsDefaultValues(): void
    {
        $qualification = new InstitutionQualification();

        $this->assertNotEmpty($qualification->getId());
        $this->assertEquals('有效', $qualification->getQualificationStatus());
        $this->assertEquals([], $qualification->getQualificationScope());
        $this->assertEquals([], $qualification->getAttachments());
        $this->assertInstanceOf(\DateTimeImmutable::class, $qualification->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $qualification->getUpdateTime());
    }

    /**
     * 测试create静态方法
     */
    public function test_create_withValidData(): void
    {
        $issueDate = new \DateTimeImmutable('2023-01-01');
        $validFrom = new \DateTimeImmutable('2023-01-01');
        $validTo = new \DateTimeImmutable('2026-01-01');
        $scope = ['特种作业培训', '安全管理培训'];
        $attachments = ['cert.pdf'];

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

        $this->assertSame($this->institution, $qualification->getInstitution());
        $this->assertEquals('安全培训资质', $qualification->getQualificationType());
        $this->assertEquals('安全生产培训机构资质证书', $qualification->getQualificationName());
        $this->assertEquals('CERT001', $qualification->getCertificateNumber());
        $this->assertEquals('国家安全监管总局', $qualification->getIssuingAuthority());
        $this->assertSame($issueDate, $qualification->getIssueDate());
        $this->assertSame($validFrom, $qualification->getValidFrom());
        $this->assertSame($validTo, $qualification->getValidTo());
        $this->assertEquals($scope, $qualification->getQualificationScope());
        $this->assertEquals('有效', $qualification->getQualificationStatus());
        $this->assertEquals($attachments, $qualification->getAttachments());
    }

    /**
     * 测试create静态方法使用默认参数
     */
    public function test_create_withDefaultParameters(): void
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

        $this->assertEquals([], $qualification->getQualificationScope());
        $this->assertEquals('有效', $qualification->getQualificationStatus());
        $this->assertEquals([], $qualification->getAttachments());
    }

    /**
     * 测试设置和获取机构
     */
    public function test_setInstitution_updatesInstitutionAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        
        // 等待一毫秒确保时间不同
        usleep(1000);
        
        $qualification->setInstitution($this->institution);

        $this->assertSame($this->institution, $qualification->getInstitution());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取资质类型
     */
    public function test_setQualificationType_updatesTypeAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        
        usleep(1000);
        
        $qualification->setQualificationType('特种作业培训资质');

        $this->assertEquals('特种作业培训资质', $qualification->getQualificationType());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取资质名称
     */
    public function test_setQualificationName_updatesNameAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        
        usleep(1000);
        
        $qualification->setQualificationName('特种作业人员安全技术培训机构资质证书');

        $this->assertEquals('特种作业人员安全技术培训机构资质证书', $qualification->getQualificationName());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取证书编号
     */
    public function test_setCertificateNumber_updatesNumberAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        
        usleep(1000);
        
        $qualification->setCertificateNumber('CERT2023001');

        $this->assertEquals('CERT2023001', $qualification->getCertificateNumber());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取发证机关
     */
    public function test_setIssuingAuthority_updatesAuthorityAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        
        usleep(1000);
        
        $qualification->setIssuingAuthority('应急管理部');

        $this->assertEquals('应急管理部', $qualification->getIssuingAuthority());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取发证日期
     */
    public function test_setIssueDate_updatesDateAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        $issueDate = new \DateTimeImmutable('2023-06-15');
        
        usleep(1000);
        
        $qualification->setIssueDate($issueDate);

        $this->assertSame($issueDate, $qualification->getIssueDate());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取有效期开始日期
     */
    public function test_setValidFrom_updatesDateAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        $validFrom = new \DateTimeImmutable('2023-07-01');
        
        usleep(1000);
        
        $qualification->setValidFrom($validFrom);

        $this->assertSame($validFrom, $qualification->getValidFrom());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取有效期结束日期
     */
    public function test_setValidTo_updatesDateAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        $validTo = new \DateTimeImmutable('2026-07-01');
        
        usleep(1000);
        
        $qualification->setValidTo($validTo);

        $this->assertSame($validTo, $qualification->getValidTo());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取资质范围
     */
    public function test_setQualificationScope_updatesScopeAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        $scope = ['电工作业', '焊接作业', '高处作业'];
        
        usleep(1000);
        
        $qualification->setQualificationScope($scope);

        $this->assertEquals($scope, $qualification->getQualificationScope());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取资质状态
     */
    public function test_setQualificationStatus_updatesStatusAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        
        usleep(1000);
        
        $qualification->setQualificationStatus('暂停');

        $this->assertEquals('暂停', $qualification->getQualificationStatus());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试设置和获取附件
     */
    public function test_setAttachments_updatesAttachmentsAndTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        $attachments = ['cert.pdf', 'license.jpg'];
        
        usleep(1000);
        
        $qualification->setAttachments($attachments);

        $this->assertEquals($attachments, $qualification->getAttachments());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试有效资质检查
     */
    public function test_isValid_withValidQualification(): void
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

        $this->assertTrue($qualification->isValid());
    }

    /**
     * 测试已过期资质检查
     */
    public function test_isValid_withExpiredQualification(): void
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

        $this->assertFalse($qualification->isValid());
    }

    /**
     * 测试状态为非有效的资质检查
     */
    public function test_isValid_withInvalidStatus(): void
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

        $this->assertFalse($qualification->isValid());
    }

    /**
     * 测试尚未生效的资质检查
     */
    public function test_isValid_withFutureValidFrom(): void
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

        $this->assertFalse($qualification->isValid());
    }

    /**
     * 测试即将到期检查 - 默认30天
     */
    public function test_isExpiringSoon_withDefaultDays(): void
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

        $this->assertTrue($qualification->isExpiringSoon());
    }

    /**
     * 测试即将到期检查 - 自定义天数
     */
    public function test_isExpiringSoon_withCustomDays(): void
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

        $this->assertTrue($qualification->isExpiringSoon(60));
        $this->assertFalse($qualification->isExpiringSoon(30));
    }

    /**
     * 测试无效资质不会被认为即将到期
     */
    public function test_isExpiringSoon_withInvalidQualification(): void
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

        $this->assertFalse($qualification->isExpiringSoon());
    }

    /**
     * 测试获取剩余天数 - 有效期内
     */
    public function test_getRemainingDays_withValidQualification(): void
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
        $this->assertGreaterThanOrEqual(29, $remainingDays);
        $this->assertLessThanOrEqual(30, $remainingDays);
    }

    /**
     * 测试获取剩余天数 - 已过期
     */
    public function test_getRemainingDays_withExpiredQualification(): void
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

        $this->assertEquals(0, $qualification->getRemainingDays());
    }

    /**
     * 测试培训类型覆盖检查 - 包含
     */
    public function test_coversTrainingType_withCoveredType(): void
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

        $this->assertTrue($qualification->coversTrainingType('电工作业'));
        $this->assertTrue($qualification->coversTrainingType('焊接作业'));
        $this->assertTrue($qualification->coversTrainingType('高处作业'));
    }

    /**
     * 测试培训类型覆盖检查 - 不包含
     */
    public function test_coversTrainingType_withUncoveredType(): void
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

        $this->assertFalse($qualification->coversTrainingType('高处作业'));
        $this->assertFalse($qualification->coversTrainingType('起重机械作业'));
    }

    /**
     * 测试空范围的培训类型覆盖检查
     */
    public function test_coversTrainingType_withEmptyScope(): void
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

        $this->assertFalse($qualification->coversTrainingType('任何培训类型'));
    }

    /**
     * 测试资质续期 - 仅更新有效期
     */
    public function test_renew_withOnlyNewValidTo(): void
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
        $originalUpdateTime = $qualification->getUpdateTime();
        $newValidTo = new \DateTimeImmutable('+3 years');
        
        usleep(1000);
        
        $result = $qualification->renew($newValidTo);

        $this->assertSame($qualification, $result);
        $this->assertSame($newValidTo, $qualification->getValidTo());
        $this->assertEquals($originalCertNumber, $qualification->getCertificateNumber());
        $this->assertEquals('有效', $qualification->getQualificationStatus());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试资质续期 - 更新有效期和证书编号
     */
    public function test_renew_withNewValidToAndCertificateNumber(): void
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
        $originalUpdateTime = $qualification->getUpdateTime();
        
        usleep(1000);
        
        $result = $qualification->renew($newValidTo, $newCertNumber);

        $this->assertSame($qualification, $result);
        $this->assertSame($newValidTo, $qualification->getValidTo());
        $this->assertEquals($newCertNumber, $qualification->getCertificateNumber());
        $this->assertEquals('有效', $qualification->getQualificationStatus());
        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试preUpdate生命周期回调
     */
    public function test_preUpdate_updatesUpdateTime(): void
    {
        $qualification = new InstitutionQualification();
        $originalUpdateTime = $qualification->getUpdateTime();
        
        usleep(1000);
        
        $qualification->preUpdate();

        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }

    /**
     * 测试边界条件 - 当天到期
     */
    public function test_isValid_withExpiringToday(): void
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

        $this->assertFalse($qualification->isValid());
    }

    /**
     * 测试边界条件 - 当天生效
     */
    public function test_isValid_withValidFromToday(): void
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

        $this->assertTrue($qualification->isValid());
    }
} 