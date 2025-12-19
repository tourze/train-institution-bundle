<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Service\FacilityReportGenerator;

/**
 * @internal
 */
#[CoversClass(FacilityReportGenerator::class)]
#[RunTestsInSeparateProcesses]
final class FacilityReportGeneratorTest extends AbstractIntegrationTestCase
{
    private FacilityReportGenerator $generator;

    private Institution $institution;

    public function testFormatInstitutionInfo(): void
    {
        $result = $this->generator->formatInstitutionInfo($this->institution);

        self::assertIsArray($result);
        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('name', $result);
        self::assertSame('测试机构', $result['name']);
    }

    public function testGenerateSummaryStatistics(): void
    {
        // 持久化机构到数据库
        $entityManager = self::getEntityManager();
        $entityManager->persist($this->institution);
        $entityManager->flush();

        // 创建并持久化设施
        $facility1 = $this->createFacility('教室1', '教室', 100.0, 50);
        $facility2 = $this->createFacility('教室2', '教室', 80.0, 40);
        $facility3 = $this->createFacility('实训场地', '实训场地', 200.0, 100);

        $entityManager->persist($facility1);
        $entityManager->persist($facility2);
        $entityManager->persist($facility3);
        $entityManager->flush();

        $facilities = [$facility1, $facility2, $facility3];

        $result = $this->generator->generateSummaryStatistics($facilities, $this->institution);

        self::assertIsArray($result);
        self::assertArrayHasKey('total_facilities', $result);
        self::assertArrayHasKey('total_area', $result);
        self::assertArrayHasKey('total_capacity', $result);
        self::assertArrayHasKey('average_area_per_facility', $result);
        self::assertArrayHasKey('average_capacity_per_facility', $result);

        self::assertSame(3, $result['total_facilities']);
        self::assertSame(380.0, $result['total_area']);
        self::assertSame(190, $result['total_capacity']);
        self::assertEqualsWithDelta(126.67, $result['average_area_per_facility'], 0.01);
        self::assertEqualsWithDelta(63.33, $result['average_capacity_per_facility'], 0.01);
    }

    private function createFacility(
        string $name,
        string $type,
        float $area,
        int $capacity,
        string $status = '正常使用',
    ): InstitutionFacility {
        return InstitutionFacility::create(
            $this->institution,
            $type,
            $name,
            '一楼101室',
            $area,
            $capacity,
            [],
            [],
            $status
        );
    }

    public function testGenerateSummaryStatisticsWithEmptyFacilities(): void
    {
        // 持久化机构到数据库（但不添加设施）
        $entityManager = self::getEntityManager();
        $entityManager->persist($this->institution);
        $entityManager->flush();

        $facilities = [];

        $result = $this->generator->generateSummaryStatistics($facilities, $this->institution);

        self::assertSame(0, $result['total_facilities']);
        self::assertSame(0.0, $result['total_area']);
        self::assertSame(0, $result['total_capacity']);
        self::assertSame(0, $result['average_area_per_facility']);
        self::assertSame(0, $result['average_capacity_per_facility']);
    }

    public function testGenerateTypeAndStatusStatistics(): void
    {
        $facilities = [
            $this->createFacility('教室1', '教室', 100.0, 50, '正常使用'),
            $this->createFacility('教室2', '教室', 80.0, 40, '正常使用'),
            $this->createFacility('实训场地', '实训场地', 200.0, 100, '维修中'),
            $this->createFacility('会议室', '会议室', 50.0, 20, '正常使用'),
        ];

        $result = $this->generator->generateTypeAndStatusStatistics($facilities);

        self::assertIsArray($result);
        self::assertArrayHasKey('by_type', $result);
        self::assertArrayHasKey('by_status', $result);

        // 验证类型统计
        self::assertSame(2, $result['by_type']['教室']);
        self::assertSame(1, $result['by_type']['实训场地']);
        self::assertSame(1, $result['by_type']['会议室']);

        // 验证状态统计
        self::assertSame(3, $result['by_status']['正常使用']);
        self::assertSame(1, $result['by_status']['维修中']);
    }

    public function testGenerateTypeAndStatusStatisticsWithEmptyFacilities(): void
    {
        $facilities = [];

        $result = $this->generator->generateTypeAndStatusStatistics($facilities);

        self::assertIsArray($result);
        self::assertArrayHasKey('by_type', $result);
        self::assertArrayHasKey('by_status', $result);
        self::assertEmpty($result['by_type']);
        self::assertEmpty($result['by_status']);
    }

    public function testGenerateMaintenanceInfo(): void
    {
        $now = new \DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $tomorrow = $now->modify('+1 day');

        $facilities = [
            $this->createFacilityWithInspectionDates('教室1', $yesterday->modify('-30 days'), $yesterday),
            $this->createFacilityWithInspectionDates('教室2', $yesterday->modify('-15 days'), $now),
            $this->createFacilityWithInspectionDates('教室3', $now->modify('-5 days'), $tomorrow),
            $this->createFacilityWithInspectionDates('教室4', null, null), // 未设置检查日期
        ];

        $result = $this->generator->generateMaintenanceInfo($facilities);

        self::assertIsArray($result);
        self::assertArrayHasKey('needing_inspection', $result);
        self::assertArrayHasKey('inspection_list', $result);

        // 应有3个设施需要检查：昨天到期、今天到期、未设置日期
        self::assertSame(3, $result['needing_inspection']);
        self::assertIsArray($result['inspection_list']);
        self::assertCount(3, $result['inspection_list']);
    }

    private function createFacilityWithInspectionDates(
        string $name,
        ?\DateTimeImmutable $lastInspection,
        ?\DateTimeImmutable $nextInspection,
    ): InstitutionFacility {
        $facility = $this->createFacility($name, '教室', 100.0, 50);
        $facility->setLastInspectionDate($lastInspection);
        $facility->setNextInspectionDate($nextInspection);

        return $facility;
    }

    public function testGenerateMaintenanceInfoInspectionListFormat(): void
    {
        $now = new \DateTimeImmutable();
        $yesterday = $now->modify('-1 day');

        $facility = $this->createFacilityWithInspectionDates('教室1', $yesterday->modify('-30 days'), $yesterday);

        $result = $this->generator->generateMaintenanceInfo([$facility]);

        self::assertIsArray($result);
        self::assertArrayHasKey('inspection_list', $result);
        self::assertIsArray($result['inspection_list']);
        self::assertCount(1, $result['inspection_list']);

        $inspectionList = $result['inspection_list'];
        self::assertIsArray($inspectionList[0]);
        $inspectionItem = $inspectionList[0];
        self::assertArrayHasKey('id', $inspectionItem);
        self::assertArrayHasKey('name', $inspectionItem);
        self::assertArrayHasKey('type', $inspectionItem);
        self::assertArrayHasKey('last_inspection', $inspectionItem);
        self::assertArrayHasKey('next_inspection', $inspectionItem);

        self::assertSame('教室1', $inspectionItem['name']);
        self::assertSame('教室', $inspectionItem['type']);
        self::assertIsString($inspectionItem['last_inspection']);
        self::assertIsString($inspectionItem['next_inspection']);
    }

    public function testGenerateMaintenanceInfoWithNullDates(): void
    {
        $facility = $this->createFacilityWithInspectionDates('教室1', null, null);

        $result = $this->generator->generateMaintenanceInfo([$facility]);

        self::assertIsArray($result);
        self::assertArrayHasKey('needing_inspection', $result);
        self::assertArrayHasKey('inspection_list', $result);
        self::assertSame(1, $result['needing_inspection']);
        self::assertIsArray($result['inspection_list']);
        self::assertCount(1, $result['inspection_list']);

        $inspectionList = $result['inspection_list'];
        self::assertIsArray($inspectionList[0]);
        $inspectionItem = $inspectionList[0];
        self::assertNull($inspectionItem['last_inspection']);
        self::assertNull($inspectionItem['next_inspection']);
    }

    public function testGenerateMaintenanceInfoWithEmptyFacilities(): void
    {
        $facilities = [];

        $result = $this->generator->generateMaintenanceInfo($facilities);

        self::assertSame(0, $result['needing_inspection']);
        self::assertEmpty($result['inspection_list']);
    }

    protected function onSetUp(): void
    {
        $this->generator = self::getService(FacilityReportGenerator::class);
        $this->institution = Institution::create(
            '测试机构',
            'TEST-' . uniqid(),
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
    }
}
