<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * Institution 实体单元测试
 *
 * @internal
 */
#[CoversClass(Institution::class)]
final class InstitutionTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Institution();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'institutionName' => ['institutionName', 'test_value'],
            'institutionCode' => ['institutionCode', 'test_value'],
            'institutionType' => ['institutionType', 'test_value'],
            'legalPerson' => ['legalPerson', 'test_value'],
            'contactPerson' => ['contactPerson', 'test_value'],
            'contactPhone' => ['contactPhone', 'test_value'],
            'contactEmail' => ['contactEmail', 'test_value'],
            'address' => ['address', 'test_value'],
            'businessScope' => ['businessScope', 'test_value'],
            'registrationNumber' => ['registrationNumber', 'test_value'],
            'institutionStatus' => ['institutionStatus', 'test_value'],
            'organizationStructure' => ['organizationStructure', ['key' => 'value']],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        // 集成测试的初始化逻辑
    }

    /**
     * 测试实体创建
     */
    public function testCreateInstitution(): void
    {
        $establishDate = new \DateTimeImmutable('2020-01-01');

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
            $establishDate,
            'REG123456789',
            '正常运营',
            ['departments' => ['部门1', '部门2'], 'structure' => 'hierarchical']
        );

        // assertInstanceOf已由类型系统保证，无需测试
        self::assertNotEmpty($institution->getId());
        self::assertEquals('测试培训机构', $institution->getInstitutionName());
        self::assertEquals('TEST001', $institution->getInstitutionCode());
        self::assertEquals('企业培训机构', $institution->getInstitutionType());
        self::assertEquals('张三', $institution->getLegalPerson());
        self::assertEquals('李四', $institution->getContactPerson());
        self::assertEquals('13800138000', $institution->getContactPhone());
        self::assertEquals('test@example.com', $institution->getContactEmail());
        self::assertEquals('北京市朝阳区测试路123号', $institution->getAddress());
        self::assertEquals('安全生产培训', $institution->getBusinessScope());
        self::assertEquals($establishDate, $institution->getEstablishDate());
        self::assertEquals('REG123456789', $institution->getRegistrationNumber());
        self::assertEquals('正常运营', $institution->getInstitutionStatus());
        self::assertEquals(['departments' => ['部门1', '部门2'], 'structure' => 'hierarchical'], $institution->getOrganizationStructure());
        self::assertNull($institution->getCreateTime());
        self::assertNull($institution->getUpdateTime());
    }

    /**
     * 测试默认构造函数
     */
    public function testDefaultConstructor(): void
    {
        $institution = new Institution();

        self::assertNotEmpty($institution->getId());
        self::assertEquals('正常运营', $institution->getInstitutionStatus());
        self::assertEquals([], $institution->getOrganizationStructure());
        self::assertNull($institution->getCreateTime());
        self::assertNull($institution->getUpdateTime());
        self::assertCount(0, $institution->getQualifications());
        self::assertCount(0, $institution->getFacilities());
        self::assertCount(0, $institution->getChangeRecords());
    }

    /**
     * 测试属性设置和获取
     */
    public function testSettersAndGetters(): void
    {
        $institution = new Institution();
        $newEstablishDate = new \DateTimeImmutable('2021-01-01');

        $institution->setInstitutionName('新机构名称');
        $institution->setInstitutionCode('NEW001');
        $institution->setInstitutionType('社会培训机构');
        $institution->setLegalPerson('王五');
        $institution->setContactPerson('赵六');
        $institution->setContactPhone('13900139000');
        $institution->setContactEmail('new@example.com');
        $institution->setAddress('上海市浦东新区新地址');
        $institution->setBusinessScope('新业务范围');
        $institution->setEstablishDate($newEstablishDate);
        $institution->setRegistrationNumber('NEWREG123');
        $institution->setInstitutionStatus('暂停运营');
        $institution->setOrganizationStructure(['departments' => ['新部门1', '新部门2'], 'structure' => 'flat']);

        self::assertEquals('新机构名称', $institution->getInstitutionName());
        self::assertEquals('NEW001', $institution->getInstitutionCode());
        self::assertEquals('社会培训机构', $institution->getInstitutionType());
        self::assertEquals('王五', $institution->getLegalPerson());
        self::assertEquals('赵六', $institution->getContactPerson());
        self::assertEquals('13900139000', $institution->getContactPhone());
        self::assertEquals('new@example.com', $institution->getContactEmail());
        self::assertEquals('上海市浦东新区新地址', $institution->getAddress());
        self::assertEquals('新业务范围', $institution->getBusinessScope());
        self::assertEquals($newEstablishDate, $institution->getEstablishDate());
        self::assertEquals('NEWREG123', $institution->getRegistrationNumber());
        self::assertEquals('暂停运营', $institution->getInstitutionStatus());
        self::assertEquals(['departments' => ['新部门1', '新部门2'], 'structure' => 'flat'], $institution->getOrganizationStructure());
    }

    /**
     * 测试资质管理
     */
    public function testQualificationManagement(): void
    {
        $institution = new Institution();

        // 创建真实的资质对象
        $qualification1 = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('+60 days'),
            ['特种作业培训']
        );

        $qualification2 = InstitutionQualification::create(
            $institution,
            '特种作业培训资质',
            '特种作业培训资质证书',
            'CERT002',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('+15 days'),
            ['电工作业培训']
        );

        $expiredQualification = InstitutionQualification::create(
            $institution,
            '过期资质',
            '已过期的资质证书',
            'CERT003',
            '国家安全监管总局',
            new \DateTimeImmutable('2020-01-01'),
            new \DateTimeImmutable('2020-01-01'),
            new \DateTimeImmutable('-10 days'),
            ['已过期培训']
        );

        // 添加资质
        $institution->addQualification($qualification1);
        $institution->addQualification($qualification2);
        $institution->addQualification($expiredQualification);

        self::assertCount(3, $institution->getQualifications());

        // 业务逻辑方法getValidQualifications/getExpiringQualifications应该在服务层
        // 这里只测试基本的关系管理

        // 移除资质
        $institution->removeQualification($qualification1);
        self::assertCount(2, $institution->getQualifications());
    }

    /**
     * 测试设施管理
     */
    public function testFacilityManagement(): void
    {
        $institution = new Institution();

        $facility1 = InstitutionFacility::create(
            $institution,
            '教室',
            '多媒体教室1',
            '一楼东侧',
            120.5,
            50,
            ['投影仪', '音响设备', '白板'],
            ['灭火器', '应急照明', '安全出口标识']
        );

        $facility2 = InstitutionFacility::create(
            $institution,
            '实训场地',
            '安全实训场地',
            '二楼西侧',
            200.0,
            30,
            ['实训设备', '安全防护用品'],
            ['灭火器', '应急照明', '安全出口标识', '急救箱']
        );

        // 添加设施
        $institution->addFacility($facility1);
        $institution->addFacility($facility2);

        self::assertCount(2, $institution->getFacilities());

        // 移除设施
        $institution->removeFacility($facility1);
        self::assertCount(1, $institution->getFacilities());
    }

    /**
     * 测试变更记录管理
     */
    public function testChangeRecordManagement(): void
    {
        $institution = new Institution();

        $changeRecord1 = InstitutionChangeRecord::create(
            $institution,
            '基本信息变更',
            ['summary' => '更新联系方式'],
            ['contactPhone' => '13800138000'],
            ['contactPhone' => '13900139000'],
            '联系方式变更',
            'admin'
        );

        $changeRecord2 = InstitutionChangeRecord::create(
            $institution,
            '状态变更',
            ['summary' => '机构状态变更'],
            ['status' => '正常运营'],
            ['status' => '暂停运营'],
            '设备维护',
            'supervisor'
        );

        // 添加变更记录
        $institution->addChangeRecord($changeRecord1);
        $institution->addChangeRecord($changeRecord2);

        self::assertCount(2, $institution->getChangeRecords());

        // 移除变更记录
        $institution->removeChangeRecord($changeRecord1);
        self::assertCount(1, $institution->getChangeRecords());
    }

    /**
     * 测试重复添加不会增加数量
     */
    public function testNoDuplicateAdditions(): void
    {
        $institution = new Institution();

        $qualification = InstitutionQualification::create(
            $institution,
            '测试资质',
            '测试资质证书',
            'TEST001',
            '测试机关',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('+365 days'),
            ['测试范围']
        );

        $facility = InstitutionFacility::create(
            $institution,
            '测试设施',
            '测试设施名称',
            '测试位置',
            100.0,
            20,
            ['测试设备'],
            ['测试安全设备']
        );

        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '测试变更',
            ['summary' => '测试变更'],
            ['old' => 'value'],
            ['new' => 'value'],
            '测试原因',
            'test_user'
        );

        // 添加两次相同的对象
        $institution->addQualification($qualification);
        $institution->addQualification($qualification);
        self::assertCount(1, $institution->getQualifications());

        $institution->addFacility($facility);
        $institution->addFacility($facility);
        self::assertCount(1, $institution->getFacilities());

        $institution->addChangeRecord($changeRecord);
        $institution->addChangeRecord($changeRecord);
        self::assertCount(1, $institution->getChangeRecords());
    }
}
