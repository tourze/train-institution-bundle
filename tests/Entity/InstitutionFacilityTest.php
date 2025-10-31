<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * InstitutionFacility 实体单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionFacility::class)]
final class InstitutionFacilityTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new InstitutionFacility();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'institution' => ['institution', null],
            'facilityType' => ['facilityType', 'test_value'],
            'facilityName' => ['facilityName', 'test_value'],
            'facilityLocation' => ['facilityLocation', 'test_value'],
            'facilityArea' => ['facilityArea', 123.45],
            'capacity' => ['capacity', 123],
            'equipmentList' => ['equipmentList', ['key' => 'value']],
            'safetyEquipment' => ['safetyEquipment', ['key' => 'value']],
            'facilityStatus' => ['facilityStatus', 'test_value'],
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
        $facility = new InstitutionFacility();

        self::assertNotEmpty($facility->getId());
        self::assertEquals('正常使用', $facility->getFacilityStatus());
        self::assertEquals([], $facility->getEquipmentList());
        self::assertEquals([], $facility->getSafetyEquipment());
        self::assertNull($facility->getLastInspectionDate());
        self::assertNull($facility->getNextInspectionDate());
        self::assertNull($facility->getCreateTime());
        self::assertNull($facility->getUpdateTime());
    }

    /**
     * 测试create静态方法
     */
    public function testCreateWithValidData(): void
    {
        $equipmentList = [
            ['name' => '投影仪', 'model' => 'EPSON-001', 'quantity' => 1],
            ['name' => '音响设备', 'model' => 'BOSE-002', 'quantity' => 1],
        ];
        $safetyEquipment = [
            ['name' => '灭火器', 'type' => '干粉灭火器', 'quantity' => 2],
            ['name' => '烟雾报警器', 'type' => '光电式', 'quantity' => 4],
        ];

        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '多媒体教室A101',
            '教学楼1层101室',
            80.5,
            50,
            $equipmentList,
            $safetyEquipment,
            '正常使用'
        );

        self::assertSame($this->institution, $facility->getInstitution());
        self::assertEquals('教室', $facility->getFacilityType());
        self::assertEquals('多媒体教室A101', $facility->getFacilityName());
        self::assertEquals('教学楼1层101室', $facility->getFacilityLocation());
        self::assertEquals(80.5, $facility->getFacilityArea());
        self::assertEquals(50, $facility->getCapacity());
        self::assertEquals($equipmentList, $facility->getEquipmentList());
        self::assertEquals($safetyEquipment, $facility->getSafetyEquipment());
        self::assertEquals('正常使用', $facility->getFacilityStatus());
    }

    /**
     * 测试create静态方法使用默认参数
     */
    public function testCreateWithDefaultParameters(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '办公区域',
            '行政办公室',
            '办公楼2层201室',
            25.0,
            10
        );

        self::assertEquals([], $facility->getEquipmentList());
        self::assertEquals([], $facility->getSafetyEquipment());
        self::assertEquals('正常使用', $facility->getFacilityStatus());
    }

    /**
     * 测试设置和获取机构
     */
    public function testSetInstitutionUpdatesInstitution(): void
    {
        $facility = new InstitutionFacility();

        $facility->setInstitution($this->institution);

        self::assertSame($this->institution, $facility->getInstitution());
    }

    /**
     * 测试设置和获取设施类型
     */
    public function testSetFacilityTypeUpdatesType(): void
    {
        $facility = new InstitutionFacility();

        $facility->setFacilityType('实训场地');

        self::assertEquals('实训场地', $facility->getFacilityType());
    }

    /**
     * 测试设置和获取设施名称
     */
    public function testSetFacilityNameUpdatesName(): void
    {
        $facility = new InstitutionFacility();

        $facility->setFacilityName('电工实训室');

        self::assertEquals('电工实训室', $facility->getFacilityName());
    }

    /**
     * 测试设置和获取设施位置
     */
    public function testSetFacilityLocationUpdatesLocation(): void
    {
        $facility = new InstitutionFacility();

        $facility->setFacilityLocation('实训楼3层301室');

        self::assertEquals('实训楼3层301室', $facility->getFacilityLocation());
    }

    /**
     * 测试设置和获取设施面积
     */
    public function testSetFacilityAreaUpdatesArea(): void
    {
        $facility = new InstitutionFacility();

        $facility->setFacilityArea(120.5);

        self::assertEquals(120.5, $facility->getFacilityArea());
    }

    /**
     * 测试设置和获取容纳人数
     */
    public function testSetCapacityUpdatesCapacity(): void
    {
        $facility = new InstitutionFacility();

        $facility->setCapacity(30);

        self::assertEquals(30, $facility->getCapacity());
    }

    /**
     * 测试设置和获取设备清单
     */
    public function testSetEquipmentListUpdatesList(): void
    {
        $facility = new InstitutionFacility();
        $equipmentList = [
            ['name' => '电脑', 'quantity' => 20],
            ['name' => '桌椅', 'quantity' => 20],
        ];

        $facility->setEquipmentList($equipmentList);

        self::assertEquals($equipmentList, $facility->getEquipmentList());
    }

    /**
     * 测试设置和获取安全设备
     */
    public function testSetSafetyEquipmentUpdatesEquipment(): void
    {
        $facility = new InstitutionFacility();
        $safetyEquipment = [
            ['name' => '灭火器', 'quantity' => 2],
            ['name' => '应急照明', 'quantity' => 4],
        ];

        $facility->setSafetyEquipment($safetyEquipment);

        self::assertEquals($safetyEquipment, $facility->getSafetyEquipment());
    }

    /**
     * 测试设置和获取设施状态
     */
    public function testSetFacilityStatusUpdatesStatus(): void
    {
        $facility = new InstitutionFacility();

        $facility->setFacilityStatus('维修中');

        self::assertEquals('维修中', $facility->getFacilityStatus());
    }

    /**
     * 测试设置和获取最后检查日期
     */
    public function testSetLastInspectionDateUpdatesDate(): void
    {
        $facility = new InstitutionFacility();
        $inspectionDate = new \DateTimeImmutable('2023-06-15');

        $facility->setLastInspectionDate($inspectionDate);

        self::assertSame($inspectionDate, $facility->getLastInspectionDate());
    }

    /**
     * 测试设置和获取下次检查日期
     */
    public function testSetNextInspectionDateUpdatesDate(): void
    {
        $facility = new InstitutionFacility();
        $nextInspectionDate = new \DateTimeImmutable('2024-06-15');

        $facility->setNextInspectionDate($nextInspectionDate);

        self::assertSame($nextInspectionDate, $facility->getNextInspectionDate());
    }

    /**
     * 测试安全设备检查 - 数组格式设备
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testHasSafetyEquipmentWithArrayFormat(): void
    {
        self::markTestSkipped('业务逻辑方法hasSafetyEquipment不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试安全设备检查 - 字符串格式设备
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testHasSafetyEquipmentWithStringFormat(): void
    {
        self::markTestSkipped('业务逻辑方法hasSafetyEquipment不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试检查需求判断 - 无下次检查日期
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testNeedsInspectionWithNoNextInspectionDate(): void
    {
        self::markTestSkipped('业务逻辑方法needsInspection不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试检查需求判断 - 检查日期已过
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testNeedsInspectionWithPastInspectionDate(): void
    {
        self::markTestSkipped('业务逻辑方法needsInspection不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试检查需求判断 - 检查日期未到
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testNeedsInspectionWithFutureInspectionDate(): void
    {
        self::markTestSkipped('业务逻辑方法needsInspection不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试利用率计算
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testCalculateUtilizationRateReturnsZero(): void
    {
        self::markTestSkipped('业务逻辑方法calculateUtilizationRate不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试添加设备
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testAddEquipmentAddsToList(): void
    {
        self::markTestSkipped('业务逻辑方法addEquipment不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试添加多个设备
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testAddEquipmentMultipleEquipment(): void
    {
        self::markTestSkipped('业务逻辑方法addEquipment不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试添加安全设备
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testAddSafetyEquipmentAddsToList(): void
    {
        self::markTestSkipped('业务逻辑方法addSafetyEquipment不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试安排检查
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testScheduleInspectionSetsDate(): void
    {
        self::markTestSkipped('业务逻辑方法scheduleInspection不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试完成检查
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testCompleteInspectionSetsDates(): void
    {
        self::markTestSkipped('业务逻辑方法completeInspection不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试边界条件 - 零面积
     */
    public function testSetFacilityAreaWithZeroArea(): void
    {
        $facility = new InstitutionFacility();

        $facility->setFacilityArea(0.0);

        self::assertEquals(0.0, $facility->getFacilityArea());
    }

    /**
     * 测试边界条件 - 零容量
     */
    public function testSetCapacityWithZeroCapacity(): void
    {
        $facility = new InstitutionFacility();

        $facility->setCapacity(0);

        self::assertEquals(0, $facility->getCapacity());
    }

    /**
     * 测试边界条件 - 空设备列表
     *
     * @deprecated 业务逻辑方法应该在服务层，不应在贫血实体中
     */
    public function testHasSafetyEquipmentWithEmptyList(): void
    {
        self::markTestSkipped('业务逻辑方法hasSafetyEquipment不应在贫血实体中，应在服务层实现');
    }

    /**
     * 测试边界条件 - 设置null检查日期
     */
    public function testSetInspectionDatesWithNull(): void
    {
        $facility = new InstitutionFacility();

        $facility->setLastInspectionDate(null);
        $facility->setNextInspectionDate(null);

        self::assertNull($facility->getLastInspectionDate());
        self::assertNull($facility->getNextInspectionDate());
        // needsInspection()方法应该在服务层，这里只测试getter/setter
    }
}
