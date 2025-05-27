<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;
use Tourze\TrainInstitutionBundle\Service\FacilityService;

/**
 * FacilityService 单元测试
 */
class FacilityServiceTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&InstitutionRepository $institutionRepository;
    private MockObject&InstitutionFacilityRepository $facilityRepository;
    private FacilityService $facilityService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->institutionRepository = $this->createMock(InstitutionRepository::class);
        $this->facilityRepository = $this->createMock(InstitutionFacilityRepository::class);

        $this->facilityService = new FacilityService(
            $this->entityManager,
            $this->institutionRepository,
            $this->facilityRepository
        );
    }

    /**
     * 测试添加设施
     */
    public function testAddFacility(): void
    {
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn('institution-id');

        $facilityData = [
            'facilityType' => '教室',
            'facilityName' => '多媒体教室1',
            'facilityLocation' => '一楼东侧',
            'facilityArea' => 120.5,
            'capacity' => 50,
            'equipmentList' => ['投影仪', '音响设备'],
            'safetyEquipment' => ['灭火器', '应急照明'],
        ];

        // 模拟获取机构
        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with('institution-id')
            ->willReturn($institution);

        // 模拟保存操作
        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $facility = $this->facilityService->addFacility('institution-id', $facilityData);

        $this->assertInstanceOf(InstitutionFacility::class, $facility);
    }

    /**
     * 测试机构不存在的情况
     */
    public function testAddFacilityWithNonExistentInstitution(): void
    {
        $facilityData = [
            'facilityType' => '教室',
            'facilityName' => '多媒体教室1',
            'facilityLocation' => '一楼东侧',
            'facilityArea' => 120.5,
            'capacity' => 50,
        ];

        // 模拟机构不存在
        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with('non-existent-id')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('机构不存在');

        $this->facilityService->addFacility('non-existent-id', $facilityData);
    }

    /**
     * 测试安排设施检查
     */
    public function testScheduleFacilityInspection(): void
    {
        $facilityId = 'facility-id';
        $inspectionDate = new \DateTimeImmutable('+30 days');
        $facility = $this->createMock(InstitutionFacility::class);

        $this->facilityRepository
            ->expects($this->once())
            ->method('find')
            ->with($facilityId)
            ->willReturn($facility);

        $facility
            ->expects($this->once())
            ->method('scheduleInspection')
            ->with($inspectionDate);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->facilityService->scheduleFacilityInspection($facilityId, $inspectionDate);
    }

    /**
     * 测试设施不存在的情况
     */
    public function testScheduleInspectionForNonExistentFacility(): void
    {
        $facilityId = 'non-existent-id';
        $inspectionDate = new \DateTimeImmutable('+30 days');

        $this->facilityRepository
            ->expects($this->once())
            ->method('find')
            ->with($facilityId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('设施不存在');

        $this->facilityService->scheduleFacilityInspection($facilityId, $inspectionDate);
    }

    /**
     * 测试验证设施要求
     */
    public function testValidateFacilityRequirements(): void
    {
        $institutionId = 'institution-id';
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn($institutionId);

        $facility1 = $this->createMock(InstitutionFacility::class);
        $facility1->method('checkAQ8011Compliance')->willReturn([]);
        $facility1->method('getFacilityType')->willReturn('教室');

        $facility2 = $this->createMock(InstitutionFacility::class);
        $facility2->method('checkAQ8011Compliance')->willReturn(['面积不足']);
        $facility2->method('getFacilityType')->willReturn('实训场地');

        $facilities = [$facility1, $facility2];

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $this->facilityRepository
            ->expects($this->once())
            ->method('findByInstitution')
            ->with($institution)
            ->willReturn($facilities);

        $this->facilityRepository
            ->expects($this->once())
            ->method('getTotalAreaByInstitution')
            ->with($institution)
            ->willReturn(220.5);

        $result = $this->facilityService->validateFacilityRequirements($institutionId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('facilities', $result);
        $this->assertArrayHasKey('overall_compliant', $result);
        $this->assertArrayHasKey('facility_counts', $result);
        $this->assertEquals(2, $result['facility_counts']['total']);
        $this->assertCount(2, $result['facilities']);
    }

    /**
     * 测试生成设施报告
     */
    public function testGenerateFacilityReport(): void
    {
        $institutionId = 'institution-id';
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn($institutionId);
        $institution->method('getInstitutionName')->willReturn('测试机构');

        $facility = $this->createMock(InstitutionFacility::class);
        $facility->method('getFacilityType')->willReturn('教室');
        $facility->method('getFacilityArea')->willReturn(120.5);
        $facility->method('getCapacity')->willReturn(50);
        $facility->method('getFacilityStatus')->willReturn('正常使用');

        $facilities = [$facility];

        $this->institutionRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $this->facilityRepository
            ->expects($this->exactly(2))
            ->method('findByInstitution')
            ->with($institution)
            ->willReturn($facilities);

        $this->facilityRepository
            ->expects($this->exactly(2))
            ->method('getTotalAreaByInstitution')
            ->with($institution)
            ->willReturn(120.5);

        $report = $this->facilityService->generateFacilityReport($institutionId);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('institution', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertEquals('测试机构', $report['institution']['name']);
        $this->assertEquals(1, $report['summary']['total_facilities']);
        $this->assertEquals(120.5, $report['summary']['total_area']);
        $this->assertEquals(50, $report['summary']['total_capacity']);
    }
} 