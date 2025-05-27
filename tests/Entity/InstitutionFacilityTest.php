<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * InstitutionFacility 实体单元测试
 */
class InstitutionFacilityTest extends TestCase
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
        $facility = new InstitutionFacility();

        $this->assertNotEmpty($facility->getId());
        $this->assertEquals('正常使用', $facility->getFacilityStatus());
        $this->assertEquals([], $facility->getEquipmentList());
        $this->assertEquals([], $facility->getSafetyEquipment());
        $this->assertNull($facility->getLastInspectionDate());
        $this->assertNull($facility->getNextInspectionDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $facility->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $facility->getUpdateTime());
    }

    /**
     * 测试create静态方法
     */
    public function test_create_withValidData(): void
    {
        $equipmentList = [
            ['name' => '投影仪', 'model' => 'EPSON-001', 'quantity' => 1],
            ['name' => '音响设备', 'model' => 'BOSE-002', 'quantity' => 1]
        ];
        $safetyEquipment = [
            ['name' => '灭火器', 'type' => '干粉灭火器', 'quantity' => 2],
            ['name' => '烟雾报警器', 'type' => '光电式', 'quantity' => 4]
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

        $this->assertSame($this->institution, $facility->getInstitution());
        $this->assertEquals('教室', $facility->getFacilityType());
        $this->assertEquals('多媒体教室A101', $facility->getFacilityName());
        $this->assertEquals('教学楼1层101室', $facility->getFacilityLocation());
        $this->assertEquals(80.5, $facility->getFacilityArea());
        $this->assertEquals(50, $facility->getCapacity());
        $this->assertEquals($equipmentList, $facility->getEquipmentList());
        $this->assertEquals($safetyEquipment, $facility->getSafetyEquipment());
        $this->assertEquals('正常使用', $facility->getFacilityStatus());
    }

    /**
     * 测试create静态方法使用默认参数
     */
    public function test_create_withDefaultParameters(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '办公区域',
            '行政办公室',
            '办公楼2层201室',
            25.0,
            10
        );

        $this->assertEquals([], $facility->getEquipmentList());
        $this->assertEquals([], $facility->getSafetyEquipment());
        $this->assertEquals('正常使用', $facility->getFacilityStatus());
    }

    /**
     * 测试设置和获取机构
     */
    public function test_setInstitution_updatesInstitutionAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->setInstitution($this->institution);

        $this->assertSame($this->institution, $facility->getInstitution());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取设施类型
     */
    public function test_setFacilityType_updatesTypeAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->setFacilityType('实训场地');

        $this->assertEquals('实训场地', $facility->getFacilityType());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取设施名称
     */
    public function test_setFacilityName_updatesNameAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->setFacilityName('电工实训室');

        $this->assertEquals('电工实训室', $facility->getFacilityName());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取设施位置
     */
    public function test_setFacilityLocation_updatesLocationAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->setFacilityLocation('实训楼3层301室');

        $this->assertEquals('实训楼3层301室', $facility->getFacilityLocation());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取设施面积
     */
    public function test_setFacilityArea_updatesAreaAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->setFacilityArea(120.5);

        $this->assertEquals(120.5, $facility->getFacilityArea());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取容纳人数
     */
    public function test_setCapacity_updatesCapacityAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->setCapacity(30);

        $this->assertEquals(30, $facility->getCapacity());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取设备清单
     */
    public function test_setEquipmentList_updatesListAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $equipmentList = [
            ['name' => '电脑', 'quantity' => 20],
            ['name' => '桌椅', 'quantity' => 20]
        ];
        
        usleep(1000);
        
        $facility->setEquipmentList($equipmentList);

        $this->assertEquals($equipmentList, $facility->getEquipmentList());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取安全设备
     */
    public function test_setSafetyEquipment_updatesEquipmentAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $safetyEquipment = [
            ['name' => '灭火器', 'quantity' => 2],
            ['name' => '应急照明', 'quantity' => 4]
        ];
        
        usleep(1000);
        
        $facility->setSafetyEquipment($safetyEquipment);

        $this->assertEquals($safetyEquipment, $facility->getSafetyEquipment());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取设施状态
     */
    public function test_setFacilityStatus_updatesStatusAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->setFacilityStatus('维修中');

        $this->assertEquals('维修中', $facility->getFacilityStatus());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取最后检查日期
     */
    public function test_setLastInspectionDate_updatesDateAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $inspectionDate = new \DateTimeImmutable('2023-06-15');
        
        usleep(1000);
        
        $facility->setLastInspectionDate($inspectionDate);

        $this->assertSame($inspectionDate, $facility->getLastInspectionDate());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试设置和获取下次检查日期
     */
    public function test_setNextInspectionDate_updatesDateAndTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $nextInspectionDate = new \DateTimeImmutable('2024-06-15');
        
        usleep(1000);
        
        $facility->setNextInspectionDate($nextInspectionDate);

        $this->assertSame($nextInspectionDate, $facility->getNextInspectionDate());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试AQ8011合规检查 - 教室面积不足
     */
    public function test_checkAQ8011Compliance_withInsufficientClassroomArea(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '小教室',
            '教学楼1层',
            30.0,  // 小于最小要求50平方米
            20
        );

        $issues = $facility->checkAQ8011Compliance();

        $this->assertContains('设施面积不足，最小要求50平方米，当前30平方米', $issues);
    }

    /**
     * 测试AQ8011合规检查 - 实训场地面积不足
     */
    public function test_checkAQ8011Compliance_withInsufficientTrainingArea(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '实训场地',
            '小实训室',
            '实训楼1层',
            80.0,  // 小于最小要求100平方米
            30
        );

        $issues = $facility->checkAQ8011Compliance();

        $this->assertContains('设施面积不足，最小要求100平方米，当前80平方米', $issues);
    }

    /**
     * 测试AQ8011合规检查 - 教室人均面积不足
     */
    public function test_checkAQ8011Compliance_withInsufficientAreaPerPerson(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '拥挤教室',
            '教学楼1层',
            60.0,
            50  // 人均面积1.2平方米，小于要求的1.5平方米
        );

        $issues = $facility->checkAQ8011Compliance();

        $this->assertContains('人均面积不足，要求1.5平方米/人，当前1.2平方米/人', $issues);
    }

    /**
     * 测试AQ8011合规检查 - 缺少安全设备
     */
    public function test_checkAQ8011Compliance_withMissingSafetyEquipment(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '普通教室',
            '教学楼1层',
            80.0,
            40,
            [],
            [] // 没有安全设备
        );

        $issues = $facility->checkAQ8011Compliance();

        $this->assertContains('缺少必要的安全设备：灭火器', $issues);
        $this->assertContains('缺少必要的安全设备：烟雾报警器', $issues);
        $this->assertContains('缺少必要的安全设备：应急照明', $issues);
    }

    /**
     * 测试AQ8011合规检查 - 设施状态异常
     */
    public function test_checkAQ8011Compliance_withAbnormalStatus(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '维修教室',
            '教学楼1层',
            80.0,
            40,
            [],
            [],
            '维修中'
        );

        $issues = $facility->checkAQ8011Compliance();

        $this->assertContains('设施状态异常：维修中', $issues);
    }

    /**
     * 测试AQ8011合规检查 - 完全合规
     */
    public function test_checkAQ8011Compliance_withFullCompliance(): void
    {
        $safetyEquipment = [
            ['name' => '灭火器', 'quantity' => 2],
            ['name' => '烟雾报警器', 'quantity' => 4],
            ['name' => '应急照明', 'quantity' => 2]
        ];

        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '标准教室',
            '教学楼1层',
            80.0,
            50,  // 人均1.6平方米，符合要求
            [],
            $safetyEquipment,
            '正常使用'
        );

        $issues = $facility->checkAQ8011Compliance();

        $this->assertEmpty($issues);
    }

    /**
     * 测试安全设备检查 - 数组格式设备
     */
    public function test_hasSafetyEquipment_withArrayFormat(): void
    {
        $safetyEquipment = [
            ['name' => '灭火器', 'type' => '干粉灭火器'],
            ['name' => '烟雾报警器', 'type' => '光电式']
        ];

        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '测试教室',
            '教学楼1层',
            80.0,
            40,
            [],
            $safetyEquipment
        );

        $this->assertTrue($facility->hasSafetyEquipment('灭火器'));
        $this->assertTrue($facility->hasSafetyEquipment('烟雾报警器'));
        $this->assertFalse($facility->hasSafetyEquipment('应急照明'));
    }

    /**
     * 测试安全设备检查 - 字符串格式设备
     */
    public function test_hasSafetyEquipment_withStringFormat(): void
    {
        $safetyEquipment = ['灭火器', '烟雾报警器'];

        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '测试教室',
            '教学楼1层',
            80.0,
            40,
            [],
            $safetyEquipment
        );

        $this->assertTrue($facility->hasSafetyEquipment('灭火器'));
        $this->assertTrue($facility->hasSafetyEquipment('烟雾报警器'));
        $this->assertFalse($facility->hasSafetyEquipment('应急照明'));
    }

    /**
     * 测试检查需求判断 - 无下次检查日期
     */
    public function test_needsInspection_withNoNextInspectionDate(): void
    {
        $facility = new InstitutionFacility();

        $this->assertTrue($facility->needsInspection());
    }

    /**
     * 测试检查需求判断 - 检查日期已过
     */
    public function test_needsInspection_withPastInspectionDate(): void
    {
        $facility = new InstitutionFacility();
        $facility->setNextInspectionDate(new \DateTimeImmutable('-1 day'));

        $this->assertTrue($facility->needsInspection());
    }

    /**
     * 测试检查需求判断 - 检查日期未到
     */
    public function test_needsInspection_withFutureInspectionDate(): void
    {
        $facility = new InstitutionFacility();
        $facility->setNextInspectionDate(new \DateTimeImmutable('+1 month'));

        $this->assertFalse($facility->needsInspection());
    }

    /**
     * 测试利用率计算
     */
    public function test_calculateUtilizationRate_returnsZero(): void
    {
        $facility = new InstitutionFacility();
        $usageRecords = [];

        $rate = $facility->calculateUtilizationRate($usageRecords);

        $this->assertEquals(0.0, $rate);
    }

    /**
     * 测试添加设备
     */
    public function test_addEquipment_addsToListAndUpdatesTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $equipment = ['name' => '投影仪', 'model' => 'EPSON-001'];
        
        usleep(1000);
        
        $result = $facility->addEquipment($equipment);

        $this->assertSame($facility, $result);
        $this->assertContains($equipment, $facility->getEquipmentList());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试添加多个设备
     */
    public function test_addEquipment_multipleEquipment(): void
    {
        $facility = new InstitutionFacility();
        $equipment1 = ['name' => '投影仪', 'model' => 'EPSON-001'];
        $equipment2 = ['name' => '音响', 'model' => 'BOSE-002'];
        
        $facility->addEquipment($equipment1);
        $facility->addEquipment($equipment2);

        $equipmentList = $facility->getEquipmentList();
        $this->assertCount(2, $equipmentList);
        $this->assertContains($equipment1, $equipmentList);
        $this->assertContains($equipment2, $equipmentList);
    }

    /**
     * 测试添加安全设备
     */
    public function test_addSafetyEquipment_addsToListAndUpdatesTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $safetyEquipment = ['name' => '灭火器', 'type' => '干粉灭火器'];
        
        usleep(1000);
        
        $result = $facility->addSafetyEquipment($safetyEquipment);

        $this->assertSame($facility, $result);
        $this->assertContains($safetyEquipment, $facility->getSafetyEquipment());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试安排检查
     */
    public function test_scheduleInspection_setsDateAndUpdatesTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $inspectionDate = new \DateTimeImmutable('+1 month');
        
        usleep(1000);
        
        $result = $facility->scheduleInspection($inspectionDate);

        $this->assertSame($facility, $result);
        $this->assertSame($inspectionDate, $facility->getNextInspectionDate());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试完成检查
     */
    public function test_completeInspection_setsDatesAndUpdatesTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        $inspectionDate = new \DateTimeImmutable('2023-06-15');
        $nextInspectionDate = new \DateTimeImmutable('2024-06-15');
        
        usleep(1000);
        
        $result = $facility->completeInspection($inspectionDate, $nextInspectionDate);

        $this->assertSame($facility, $result);
        $this->assertSame($inspectionDate, $facility->getLastInspectionDate());
        $this->assertSame($nextInspectionDate, $facility->getNextInspectionDate());
        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试preUpdate生命周期回调
     */
    public function test_preUpdate_updatesUpdateTime(): void
    {
        $facility = new InstitutionFacility();
        $originalUpdateTime = $facility->getUpdateTime();
        
        usleep(1000);
        
        $facility->preUpdate();

        $this->assertGreaterThan($originalUpdateTime, $facility->getUpdateTime());
    }

    /**
     * 测试边界条件 - 零面积
     */
    public function test_setFacilityArea_withZeroArea(): void
    {
        $facility = new InstitutionFacility();
        
        $facility->setFacilityArea(0.0);

        $this->assertEquals(0.0, $facility->getFacilityArea());
    }

    /**
     * 测试边界条件 - 零容量
     */
    public function test_setCapacity_withZeroCapacity(): void
    {
        $facility = new InstitutionFacility();
        
        $facility->setCapacity(0);

        $this->assertEquals(0, $facility->getCapacity());
    }

    /**
     * 测试边界条件 - 空设备列表
     */
    public function test_hasSafetyEquipment_withEmptyList(): void
    {
        $facility = new InstitutionFacility();

        $this->assertFalse($facility->hasSafetyEquipment('任何设备'));
    }

    /**
     * 测试边界条件 - 设置null检查日期
     */
    public function test_setInspectionDates_withNull(): void
    {
        $facility = new InstitutionFacility();
        
        $facility->setLastInspectionDate(null);
        $facility->setNextInspectionDate(null);

        $this->assertNull($facility->getLastInspectionDate());
        $this->assertNull($facility->getNextInspectionDate());
        $this->assertTrue($facility->needsInspection());
    }
} 