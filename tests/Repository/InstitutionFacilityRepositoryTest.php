<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;
use Tourze\TrainInstitutionBundle\Tests\Integration\IntegrationTestKernel;

/**
 * InstitutionFacilityRepository 单元测试
 */
class InstitutionFacilityRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private InstitutionFacilityRepository $repository;
    private Institution $testInstitution;

    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(InstitutionFacility::class);

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . InstitutionFacility::class)->execute();
        $this->entityManager->createQuery('DELETE FROM ' . Institution::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();

        // 创建测试机构
        $this->testInstitution = Institution::create(
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
        $this->entityManager->persist($this->testInstitution);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试设施
     */
    private function createTestFacility(
        string $type = '教室',
        string $name = '多媒体教室A101',
        string $location = '教学楼1层101室',
        float $area = 80.0,
        int $capacity = 50,
        string $status = '正常使用',
        ?\DateTimeInterface $nextInspectionDate = null
    ): InstitutionFacility {
        $facility = InstitutionFacility::create(
            $this->testInstitution,
            $type,
            $name,
            $location,
            $area,
            $capacity,
            [],
            [],
            $status
        );

        if ($nextInspectionDate !== null) {
            $facility->setNextInspectionDate($nextInspectionDate);
        }

        $this->entityManager->persist($facility);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 测试根据机构查找设施
     */
    public function test_findByInstitution_returnsAllFacilities(): void
    {
        $facility1 = $this->createTestFacility('教室', '教室A101', '教学楼1层');
        sleep(1); // 确保时间差异
        $facility2 = $this->createTestFacility('实训场地', '实训室B201', '实训楼2层');

        $results = $this->repository->findByInstitution($this->testInstitution);

        $this->assertCount(2, $results);
        // 验证包含所有设施，不依赖特定顺序
        $facilityNames = array_map(fn($f) => $f->getFacilityName(), $results);
        $this->assertContains('教室A101', $facilityNames);
        $this->assertContains('实训室B201', $facilityNames);
    }

    /**
     * 测试根据设施类型查找设施
     */
    public function test_findByFacilityType_returnsCorrectFacilities(): void
    {
        $this->createTestFacility('教室', '教室A101', '教学楼1层');
        $this->createTestFacility('教室', '教室A102', '教学楼1层');
        $this->createTestFacility('实训场地', '实训室B201', '实训楼2层');

        $results = $this->repository->findByFacilityType('教室');

        $this->assertCount(2, $results);
        foreach ($results as $facility) {
            $this->assertEquals('教室', $facility->getFacilityType());
        }
    }

    /**
     * 测试查找需要检查的设施 - 无下次检查日期
     */
    public function test_findNeedingInspection_withNoNextInspectionDate_returnsFacilities(): void
    {
        $facility1 = $this->createTestFacility('教室', '教室A101', '教学楼1层');
        $facility2 = $this->createTestFacility('实训场地', '实训室B201', '实训楼2层');

        // 设施1没有设置下次检查日期，设施2设置了未来的检查日期
        $facility2->setNextInspectionDate(new \DateTimeImmutable('+1 month'));
        $this->entityManager->flush();

        $results = $this->repository->findNeedingInspection();

        $this->assertCount(1, $results);
        $this->assertEquals('教室A101', $results[0]->getFacilityName());
    }

    /**
     * 测试查找需要检查的设施 - 检查日期已过
     */
    public function test_findNeedingInspection_withPastInspectionDate_returnsFacilities(): void
    {
        $facility1 = $this->createTestFacility(
            '教室',
            '教室A101',
            '教学楼1层',
            80.0,
            50,
            '正常使用',
            new \DateTimeImmutable('-1 day') // 昨天应该检查
        );

        $facility2 = $this->createTestFacility(
            '实训场地',
            '实训室B201',
            '实训楼2层',
            120.0,
            30,
            '正常使用',
            new \DateTimeImmutable('+1 month') // 下个月检查
        );

        $results = $this->repository->findNeedingInspection();

        $this->assertCount(1, $results);
        $this->assertEquals('教室A101', $results[0]->getFacilityName());
    }

    /**
     * 测试查找需要检查的设施 - 今天需要检查
     */
    public function test_findNeedingInspection_withTodayInspectionDate_returnsFacilities(): void
    {
        $facility = $this->createTestFacility(
            '教室',
            '教室A101',
            '教学楼1层',
            80.0,
            50,
            '正常使用',
            new \DateTimeImmutable('today') // 今天检查
        );

        $results = $this->repository->findNeedingInspection();

        $this->assertCount(1, $results);
        $this->assertEquals('教室A101', $results[0]->getFacilityName());
    }

    /**
     * 测试查找需要检查的设施 - 按检查日期排序
     */
    public function test_findNeedingInspection_returnsInCorrectOrder(): void
    {
        $facility1 = $this->createTestFacility(
            '教室',
            '教室A101',
            '教学楼1层',
            80.0,
            50,
            '正常使用',
            new \DateTimeImmutable('-2 days') // 前天应该检查
        );

        $facility2 = $this->createTestFacility(
            '实训场地',
            '实训室B201',
            '实训楼2层',
            120.0,
            30,
            '正常使用',
            new \DateTimeImmutable('-1 day') // 昨天应该检查
        );

        $facility3 = $this->createTestFacility('办公区域', '办公室C301', '办公楼3层');
        // facility3 没有设置检查日期，应该排在最前面

        $results = $this->repository->findNeedingInspection();

        $this->assertCount(3, $results);
        // 验证排序：NULL值在前，然后按日期升序
        $this->assertEquals('办公室C301', $results[0]->getFacilityName());
        $this->assertEquals('教室A101', $results[1]->getFacilityName());
        $this->assertEquals('实训室B201', $results[2]->getFacilityName());
    }

    /**
     * 测试获取机构总面积
     */
    public function test_getTotalAreaByInstitution_returnsCorrectTotal(): void
    {
        $this->createTestFacility('教室', '教室A101', '教学楼1层', 80.0, 50);
        $this->createTestFacility('实训场地', '实训室B201', '实训楼2层', 120.0, 30);
        $this->createTestFacility('办公区域', '办公室C301', '办公楼3层', 25.0, 10);

        $totalArea = $this->repository->getTotalAreaByInstitution($this->testInstitution);

        $this->assertEquals(225.0, $totalArea);
    }

    /**
     * 测试获取机构总面积 - 无设施
     */
    public function test_getTotalAreaByInstitution_withNoFacilities_returnsZero(): void
    {
        $totalArea = $this->repository->getTotalAreaByInstitution($this->testInstitution);

        $this->assertEquals(0.0, $totalArea);
    }

    /**
     * 测试获取机构总面积 - 包含零面积设施
     */
    public function test_getTotalAreaByInstitution_withZeroAreaFacilities_returnsCorrectTotal(): void
    {
        $this->createTestFacility('教室', '教室A101', '教学楼1层', 80.0, 50);
        $this->createTestFacility('储物间', '储物间D101', '地下室', 0.0, 0);
        $this->createTestFacility('实训场地', '实训室B201', '实训楼2层', 120.0, 30);

        $totalArea = $this->repository->getTotalAreaByInstitution($this->testInstitution);

        $this->assertEquals(200.0, $totalArea);
    }

    /**
     * 测试不同机构的设施隔离
     */
    public function test_findByInstitution_withMultipleInstitutions_returnsOnlyOwnFacilities(): void
    {
        // 创建另一个机构
        $otherInstitution = Institution::create(
            '其他培训机构',
            'OTHER001',
            '企业培训机构',
            '王五',
            '赵六',
            '13900139000',
            'other@example.com',
            '上海市浦东新区其他路456号',
            '职业技能培训',
            new \DateTimeImmutable('2021-01-01'),
            'REGOTHER001'
        );
        $this->entityManager->persist($otherInstitution);
        $this->entityManager->flush();

        // 为测试机构创建设施
        $this->createTestFacility('教室', '教室A101', '教学楼1层');

        // 为其他机构创建设施
        $otherFacility = InstitutionFacility::create(
            $otherInstitution,
            '教室',
            '其他教室B101',
            '其他教学楼1层',
            90.0,
            60
        );
        $this->entityManager->persist($otherFacility);
        $this->entityManager->flush();

        // 测试只返回测试机构的设施
        $results = $this->repository->findByInstitution($this->testInstitution);

        $this->assertCount(1, $results);
        $this->assertEquals('教室A101', $results[0]->getFacilityName());
        $this->assertSame($this->testInstitution, $results[0]->getInstitution());
    }

    /**
     * 测试总面积计算的机构隔离
     */
    public function test_getTotalAreaByInstitution_withMultipleInstitutions_returnsOnlyOwnArea(): void
    {
        // 创建另一个机构
        $otherInstitution = Institution::create(
            '其他培训机构',
            'OTHER001',
            '企业培训机构',
            '王五',
            '赵六',
            '13900139000',
            'other@example.com',
            '上海市浦东新区其他路456号',
            '职业技能培训',
            new \DateTimeImmutable('2021-01-01'),
            'REGOTHER001'
        );
        $this->entityManager->persist($otherInstitution);
        $this->entityManager->flush();

        // 为测试机构创建设施
        $this->createTestFacility('教室', '教室A101', '教学楼1层', 80.0, 50);

        // 为其他机构创建设施
        $otherFacility = InstitutionFacility::create(
            $otherInstitution,
            '教室',
            '其他教室B101',
            '其他教学楼1层',
            200.0, // 大面积
            100
        );
        $this->entityManager->persist($otherFacility);
        $this->entityManager->flush();

        // 测试只计算测试机构的面积
        $totalArea = $this->repository->getTotalAreaByInstitution($this->testInstitution);

        $this->assertEquals(80.0, $totalArea);
    }

    /**
     * 测试设施类型查找的准确性
     */
    public function test_findByFacilityType_withMixedTypes_returnsOnlyMatchingType(): void
    {
        $this->createTestFacility('教室', '教室A101', '教学楼1层');
        $this->createTestFacility('实训场地', '实训室B201', '实训楼2层');
        $this->createTestFacility('办公区域', '办公室C301', '办公楼3层');
        $this->createTestFacility('教室', '教室A102', '教学楼1层');

        $results = $this->repository->findByFacilityType('教室');

        $this->assertCount(2, $results);
        foreach ($results as $facility) {
            $this->assertEquals('教室', $facility->getFacilityType());
        }

        $classroomNames = array_map(fn($f) => $f->getFacilityName(), $results);
        $this->assertContains('教室A101', $classroomNames);
        $this->assertContains('教室A102', $classroomNames);
    }

    /**
     * 测试不存在的设施类型查找
     */
    public function test_findByFacilityType_withNonExistentType_returnsEmptyArray(): void
    {
        $this->createTestFacility('教室', '教室A101', '教学楼1层');
        $this->createTestFacility('实训场地', '实训室B201', '实训楼2层');

        $results = $this->repository->findByFacilityType('不存在的类型');

        $this->assertEmpty($results);
    }

    /**
     * 测试需要检查的设施查找 - 无需要检查的设施
     */
    public function test_findNeedingInspection_withNoFacilitiesNeedingInspection_returnsEmptyArray(): void
    {
        $this->createTestFacility(
            '教室',
            '教室A101',
            '教学楼1层',
            80.0,
            50,
            '正常使用',
            new \DateTimeImmutable('+1 month')
        );

        $this->createTestFacility(
            '实训场地',
            '实训室B201',
            '实训楼2层',
            120.0,
            30,
            '正常使用',
            new \DateTimeImmutable('+2 months')
        );

        $results = $this->repository->findNeedingInspection();

        $this->assertEmpty($results);
    }

    /**
     * 测试复杂的检查需求场景
     */
    public function test_findNeedingInspection_withComplexScenario_returnsCorrectFacilities(): void
    {
        // 需要检查的设施
        $needsInspection1 = $this->createTestFacility(
            '教室',
            '过期教室',
            '教学楼1层',
            80.0,
            50,
            '正常使用',
            new \DateTimeImmutable('-1 week')
        );

        $needsInspection2 = $this->createTestFacility('办公区域', '无检查日期办公室', '办公楼3层');

        $needsInspection3 = $this->createTestFacility(
            '实训场地',
            '今日检查实训室',
            '实训楼2层',
            120.0,
            30,
            '正常使用',
            new \DateTimeImmutable('today')
        );

        // 不需要检查的设施
        $noInspection = $this->createTestFacility(
            '会议室',
            '未来检查会议室',
            '办公楼2层',
            40.0,
            20,
            '正常使用',
            new \DateTimeImmutable('+1 month')
        );

        $results = $this->repository->findNeedingInspection();

        $this->assertCount(3, $results);

        $facilityNames = array_map(fn($f) => $f->getFacilityName(), $results);
        $this->assertContains('过期教室', $facilityNames);
        $this->assertContains('无检查日期办公室', $facilityNames);
        $this->assertContains('今日检查实训室', $facilityNames);
        $this->assertNotContains('未来检查会议室', $facilityNames);
    }

    /**
     * 测试面积计算的精度
     */
    public function test_getTotalAreaByInstitution_withDecimalAreas_returnsCorrectPrecision(): void
    {
        $this->createTestFacility('教室', '教室A101', '教学楼1层', 80.5, 50);
        $this->createTestFacility('实训场地', '实训室B201', '实训楼2层', 120.75, 30);
        $this->createTestFacility('办公区域', '办公室C301', '办公楼3层', 25.25, 10);

        $totalArea = $this->repository->getTotalAreaByInstitution($this->testInstitution);

        $this->assertEquals(226.5, $totalArea);
    }

    /**
     * 测试大量设施的性能
     */
    public function test_findByInstitution_withManyFacilities_returnsAllFacilities(): void
    {
        // 创建10个设施
        for ($i = 1; $i <= 10; $i++) {
            $this->createTestFacility(
                '教室',
                "教室A{$i}",
                "教学楼{$i}层",
                80.0 + $i,
                50 + $i
            );
        }

        $results = $this->repository->findByInstitution($this->testInstitution);

        $this->assertCount(10, $results);
        // 验证包含所有设施
        $facilityNames = array_map(fn($f) => $f->getFacilityName(), $results);
        for ($i = 1; $i <= 10; $i++) {
            $this->assertContains("教室A{$i}", $facilityNames);
        }
    }
} 