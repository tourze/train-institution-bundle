<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * 基础集成测试
 * 
 * 测试实体之间的关系和基本功能
 */
class BasicIntegrationTest extends TestCase
{
    /**
     * 测试机构与资质的关系
     */
    public function testInstitutionQualificationRelationship(): void
    {
        $institution = Institution::create(
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

        $qualification = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训', '安全管理培训']
        );

        // 测试关系建立
        $institution->addQualification($qualification);
        
        $this->assertCount(1, $institution->getQualifications());
        $this->assertTrue($institution->getQualifications()->contains($qualification));
        $this->assertSame($institution, $qualification->getInstitution());
    }

    /**
     * 测试机构与设施的关系
     */
    public function testInstitutionFacilityRelationship(): void
    {
        $institution = Institution::create(
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

        $facility = InstitutionFacility::create(
            $institution,
            '教室',
            '多媒体教室1',
            '一楼东侧',
            120.5,
            50,
            ['投影仪', '音响设备', '白板'],
            ['灭火器', '应急照明', '安全出口标识']
        );

        // 测试关系建立
        $institution->addFacility($facility);
        
        $this->assertCount(1, $institution->getFacilities());
        $this->assertTrue($institution->getFacilities()->contains($facility));
        $this->assertSame($institution, $facility->getInstitution());
    }

    /**
     * 测试机构与变更记录的关系
     */
    public function testInstitutionChangeRecordRelationship(): void
    {
        $institution = Institution::create(
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

        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '基本信息变更',
            ['summary' => '更新联系方式'],
            ['contactPhone' => '13800138000'],
            ['contactPhone' => '13900139000'],
            '联系方式变更',
            'admin'
        );

        // 测试关系建立
        $institution->addChangeRecord($changeRecord);
        
        $this->assertCount(1, $institution->getChangeRecords());
        $this->assertTrue($institution->getChangeRecords()->contains($changeRecord));
        $this->assertSame($institution, $changeRecord->getInstitution());
    }

    /**
     * 测试完整的机构生命周期
     */
    public function testCompleteInstitutionLifecycle(): void
    {
        // 1. 创建机构
        $institution = Institution::create(
            '完整测试机构',
            'FULL001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区测试路123号',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456789',
            '正常运营'
        );

        $this->assertEquals('正常运营', $institution->getInstitutionStatus());

        // 2. 添加资质
        $qualification = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );

        $institution->addQualification($qualification);

        // 3. 添加设施
        $facility = InstitutionFacility::create(
            $institution,
            '教室',
            '理论教室',
            '二楼',
            100.0,
            40,
            ['投影仪', '音响'],
            ['灭火器', '应急灯']
        );

        $institution->addFacility($facility);

        // 4. 检查合规性
        $complianceIssues = $institution->checkAQ8011Compliance();
        $this->assertEmpty($complianceIssues, '机构应该符合基本合规要求');

        // 5. 获取有效资质
        $validQualifications = $institution->getValidQualifications();
        $this->assertCount(1, $validQualifications);

        // 6. 检查即将到期的资质（应该没有，因为还有3年）
        $expiringQualifications = $institution->getExpiringQualifications();
        $this->assertCount(0, $expiringQualifications);

        // 7. 更新机构信息
        $institution->setContactPhone('13900139000');
        $institution->setContactEmail('new@example.com');

        $this->assertEquals('13900139000', $institution->getContactPhone());
        $this->assertEquals('new@example.com', $institution->getContactEmail());

        // 8. 记录变更
        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '联系方式变更',
            ['summary' => '更新联系电话和邮箱'],
            [
                'contactPhone' => '13800138000',
                'contactEmail' => 'test@example.com'
            ],
            [
                'contactPhone' => '13900139000',
                'contactEmail' => 'new@example.com'
            ],
            '业务需要',
            'admin'
        );

        $institution->addChangeRecord($changeRecord);

        // 9. 验证最终状态
        $this->assertCount(1, $institution->getQualifications());
        $this->assertCount(1, $institution->getFacilities());
        $this->assertCount(1, $institution->getChangeRecords());
        $this->assertEquals('正常运营', $institution->getInstitutionStatus());
    }

    /**
     * 测试资质到期检查
     */
    public function testQualificationExpiryCheck(): void
    {
        $institution = Institution::create(
            '测试机构',
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

        // 创建即将到期的资质（15天后到期）
        $expiringQualification = InstitutionQualification::create(
            $institution,
            '即将到期资质',
            '即将到期的资质证书',
            'EXPIRING001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('+15 days'),
            ['即将到期培训']
        );

        // 创建有效期较长的资质（60天后到期）
        $validQualification = InstitutionQualification::create(
            $institution,
            '有效资质',
            '有效的资质证书',
            'VALID001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('+60 days'),
            ['有效培训']
        );

        // 创建已过期的资质
        $expiredQualification = InstitutionQualification::create(
            $institution,
            '已过期资质',
            '已过期的资质证书',
            'EXPIRED001',
            '国家安全监管总局',
            new \DateTimeImmutable('2020-01-01'),
            new \DateTimeImmutable('2020-01-01'),
            new \DateTimeImmutable('-10 days'),
            ['已过期培训']
        );

        $institution->addQualification($expiringQualification);
        $institution->addQualification($validQualification);
        $institution->addQualification($expiredQualification);

        // 测试获取有效资质（不包括已过期的）
        $validQualifications = $institution->getValidQualifications();
        $this->assertCount(2, $validQualifications);

        // 测试获取即将到期的资质（30天内）
        $expiringQualifications = $institution->getExpiringQualifications();
        $this->assertCount(1, $expiringQualifications);
    }

    /**
     * 测试设施合规检查
     */
    public function testFacilityComplianceCheck(): void
    {
        $institution = new Institution();

        // 创建符合要求的设施
        $compliantFacility = InstitutionFacility::create(
            $institution,
            '教室',
            '标准教室',
            '一楼',
            120.0, // 面积足够
            50,    // 容量合理
            ['投影仪', '音响设备', '白板', '空调'],
            ['灭火器', '应急照明', '安全出口标识', '烟雾报警器']
        );

        $institution->addFacility($compliantFacility);

        // 检查设施合规性
        $complianceIssues = $compliantFacility->checkAQ8011Compliance();
        $this->assertEmpty($complianceIssues, '标准设施应该符合合规要求');
    }

    /**
     * 测试变更记录审批流程
     */
    public function testChangeRecordApprovalFlow(): void
    {
        $institution = new Institution();

        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '状态变更',
            ['summary' => '机构状态变更申请'],
            ['status' => '正常运营'],
            ['status' => '暂停运营'],
            '设备维护',
            'admin'
        );

        // 初始状态应该是待审批
        $this->assertEquals('待审批', $changeRecord->getApprovalStatus());
        $this->assertNull($changeRecord->getApprover());
        $this->assertNull($changeRecord->getApprovalDate());

        // 审批通过
        $changeRecord->approve('supervisor');

        $this->assertEquals('已审批', $changeRecord->getApprovalStatus());
        $this->assertEquals('supervisor', $changeRecord->getApprover());
        $this->assertInstanceOf(\DateTimeImmutable::class, $changeRecord->getApprovalDate());

        // 创建另一个变更记录并拒绝
        $rejectedRecord = InstitutionChangeRecord::create(
            $institution,
            '信息变更',
            ['summary' => '不合理的变更申请'],
            ['name' => '原名称'],
            ['name' => '新名称'],
            '无正当理由',
            'user'
        );

        $rejectedRecord->reject('supervisor');

        $this->assertEquals('已拒绝', $rejectedRecord->getApprovalStatus());
        $this->assertEquals('supervisor', $rejectedRecord->getApprover());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rejectedRecord->getApprovalDate());
    }
} 