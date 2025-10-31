<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Exception\FacilityNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InvalidFacilityDataException;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;
use Tourze\TrainInstitutionBundle\Service\FacilityService;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

/**
 * FacilityService 集成测试
 *
 * @internal
 */
#[CoversClass(FacilityService::class)]
#[RunTestsInSeparateProcesses]
final class FacilityServiceTest extends AbstractIntegrationTestCase
{
    private FacilityService $service;

    public function testServiceExists(): void
    {
        self::assertSame(FacilityService::class, $this->service::class);
    }

    public function testAddFacility(): void
    {
        // 创建一个真实的机构用于测试
        $institutionData = [
            'institutionName' => '测试机构_设施测试',
            'institutionCode' => 'TEST_FAC_' . uniqid(),
            'registrationNumber' => 'REG_FAC_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ];

        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution($institutionData);

        $facilityData = $this->getValidFacilityData();

        $result = $this->service->addFacility($institution->getId(), $facilityData);

        self::assertSame($facilityData['facilityType'], $result->getFacilityType());
        self::assertSame($facilityData['facilityName'], $result->getFacilityName());
    }

    public function testAddFacilityWithInvalidInstitution(): void
    {
        $institutionId = 'invalid-institution-id';
        $facilityData = $this->getValidFacilityData();

        $this->expectException(InstitutionNotFoundException::class);

        $this->service->addFacility($institutionId, $facilityData);
    }

    /**
     * @param array<string, mixed> $facilityData
     */
    #[DataProvider('invalidFacilityDataProvider')]
    public function testAddFacilityWithInvalidData(array $facilityData): void
    {
        $this->expectException(InvalidFacilityDataException::class);

        $this->service->addFacility('dummy-id', $facilityData);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function invalidFacilityDataProvider(): array
    {
        return [
            'missing facilityType' => [
                [
                    'facilityName' => '测试教室1',
                    'facilityLocation' => '1楼101室',
                    'facilityArea' => 100.0,
                    'capacity' => 50,
                ],
            ],
            'invalid facilityType' => [
                [
                    'facilityType' => 123, // 应该是字符串
                    'facilityName' => '测试教室1',
                    'facilityLocation' => '1楼101室',
                    'facilityArea' => 100.0,
                    'capacity' => 50,
                ],
            ],
            'negative area' => [
                [
                    'facilityType' => '教室',
                    'facilityName' => '测试教室1',
                    'facilityLocation' => '1楼101室',
                    'facilityArea' => -10.0,
                    'capacity' => 50,
                ],
            ],
            'zero capacity' => [
                [
                    'facilityType' => '教室',
                    'facilityName' => '测试教室1',
                    'facilityLocation' => '1楼101室',
                    'facilityArea' => 100.0,
                    'capacity' => 0,
                ],
            ],
        ];
    }

    protected function onSetUp(): void
    {
        $this->service = self::getService(FacilityService::class);
    }

    public function testUpdateFacility(): void
    {
        // 创建测试机构和设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_更新设施',
            'institutionCode' => 'TEST_UPD_FAC_' . uniqid(),
            'registrationNumber' => 'REG_UPD_FAC_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        $facilityData = $this->getValidFacilityData();
        $facility = $this->service->addFacility($institution->getId(), $facilityData);

        // 更新设施信息
        $updateData = [
            'facilityName' => '更新后的教室',
            'facilityLocation' => '2楼201室',
            'facilityArea' => 150.0,
            'capacity' => 60,
            'facilityStatus' => '维修中',
        ];

        $updatedFacility = $this->service->updateFacility($facility->getId(), $updateData);

        self::assertSame('更新后的教室', $updatedFacility->getFacilityName());
        self::assertSame('2楼201室', $updatedFacility->getFacilityLocation());
        self::assertSame(150.0, $updatedFacility->getFacilityArea());
        self::assertSame(60, $updatedFacility->getCapacity());
        self::assertSame('维修中', $updatedFacility->getFacilityStatus());
    }

    public function testUpdateFacilityWithInvalidId(): void
    {
        $this->expectException(FacilityNotFoundException::class);

        $this->service->updateFacility('invalid-facility-id', ['facilityName' => '新名称']);
    }

    public function testScheduleFacilityInspection(): void
    {
        // 创建测试机构和设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_调度检查',
            'institutionCode' => 'TEST_SCH_' . uniqid(),
            'registrationNumber' => 'REG_SCH_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        $facilityData = $this->getValidFacilityData();
        $facility = $this->service->addFacility($institution->getId(), $facilityData);

        // 安排检查
        $inspectionDate = new \DateTimeImmutable('+7 days');
        $this->service->scheduleFacilityInspection($facility->getId(), $inspectionDate);

        // 验证检查日期已设置
        $repository = self::getService(InstitutionFacilityRepository::class);
        $updatedFacility = $repository->find($facility->getId());
        self::assertNotNull($updatedFacility);
        self::assertInstanceOf(InstitutionFacility::class, $updatedFacility);
        self::assertNotNull($updatedFacility->getNextInspectionDate());
        self::assertSame($inspectionDate->format('Y-m-d'), $updatedFacility->getNextInspectionDate()->format('Y-m-d'));
    }

    public function testScheduleFacilityInspectionWithInvalidId(): void
    {
        $this->expectException(FacilityNotFoundException::class);

        $this->service->scheduleFacilityInspection('invalid-id', new \DateTimeImmutable('+7 days'));
    }

    public function testCompleteFacilityInspection(): void
    {
        // 创建测试机构和设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_完成检查',
            'institutionCode' => 'TEST_COMP_' . uniqid(),
            'registrationNumber' => 'REG_COMP_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        $facilityData = $this->getValidFacilityData();
        $facility = $this->service->addFacility($institution->getId(), $facilityData);

        // 完成检查
        $inspectionDate = new \DateTimeImmutable('today');
        $nextInspectionDate = new \DateTimeImmutable('+30 days');
        $completedFacility = $this->service->completeFacilityInspection(
            $facility->getId(),
            $inspectionDate,
            $nextInspectionDate
        );

        self::assertNotNull($completedFacility->getLastInspectionDate());
        self::assertSame($inspectionDate->format('Y-m-d'), $completedFacility->getLastInspectionDate()->format('Y-m-d'));
        self::assertNotNull($completedFacility->getNextInspectionDate());
        self::assertSame($nextInspectionDate->format('Y-m-d'), $completedFacility->getNextInspectionDate()->format('Y-m-d'));
    }

    public function testCompleteFacilityInspectionWithInvalidId(): void
    {
        $this->expectException(FacilityNotFoundException::class);

        $this->service->completeFacilityInspection(
            'invalid-id',
            new \DateTimeImmutable('today'),
            new \DateTimeImmutable('+30 days')
        );
    }

    public function testValidateFacilityRequirements(): void
    {
        // 创建测试机构和设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_验证要求',
            'institutionCode' => 'TEST_VAL_' . uniqid(),
            'registrationNumber' => 'REG_VAL_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        // 添加教室设施
        $classroomData = [
            'facilityType' => '教室',
            'facilityName' => '多媒体教室',
            'facilityLocation' => '1楼101室',
            'facilityArea' => 120.0,
            'capacity' => 50,
            'equipmentList' => ['投影仪'],
            'safetyEquipment' => ['灭火器'],
            'facilityStatus' => '正常使用',
        ];
        $this->service->addFacility($institution->getId(), $classroomData);

        // 添加实训场地
        $trainingData = [
            'facilityType' => '实训场地',
            'facilityName' => '安全实训场',
            'facilityLocation' => '2楼201室',
            'facilityArea' => 150.0,
            'capacity' => 30,
            'equipmentList' => ['实训设备'],
            'safetyEquipment' => ['灭火器', '急救箱'],
            'facilityStatus' => '正常使用',
        ];
        $this->service->addFacility($institution->getId(), $trainingData);

        // 验证设施要求
        $validationResult = $this->service->validateFacilityRequirements($institution->getId());

        self::assertArrayHasKey('facilities', $validationResult);
        self::assertArrayHasKey('overall_compliant', $validationResult);
        self::assertArrayHasKey('total_area', $validationResult);
        self::assertArrayHasKey('facility_counts', $validationResult);
    }

    public function testValidateFacilityRequirementsWithInvalidInstitution(): void
    {
        $this->expectException(InstitutionNotFoundException::class);

        $this->service->validateFacilityRequirements('invalid-institution-id');
    }

    public function testGenerateFacilityReport(): void
    {
        // 创建测试机构和设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_生成报告',
            'institutionCode' => 'TEST_REP_' . uniqid(),
            'registrationNumber' => 'REG_REP_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        $facilityData = $this->getValidFacilityData();
        $this->service->addFacility($institution->getId(), $facilityData);

        // 生成报告
        $report = $this->service->generateFacilityReport($institution->getId());

        self::assertArrayHasKey('institution', $report);
        self::assertArrayHasKey('summary', $report);
        self::assertArrayHasKey('statistics', $report);
        self::assertArrayHasKey('maintenance', $report);
        self::assertArrayHasKey('compliance', $report);
        self::assertArrayHasKey('generated_at', $report);
        self::assertInstanceOf(\DateTimeImmutable::class, $report['generated_at']);
    }

    public function testGenerateFacilityReportWithInvalidInstitution(): void
    {
        $this->expectException(InstitutionNotFoundException::class);

        $this->service->generateFacilityReport('invalid-institution-id');
    }

    public function testBatchScheduleInspections(): void
    {
        // 创建测试机构和多个设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_批量调度',
            'institutionCode' => 'TEST_BATCH_' . uniqid(),
            'registrationNumber' => 'REG_BATCH_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        $facilityIds = [];
        for ($i = 1; $i <= 3; ++$i) {
            $facilityData = [
                'facilityType' => '教室',
                'facilityName' => "教室{$i}",
                'facilityLocation' => "1楼10{$i}室",
                'facilityArea' => 100.0,
                'capacity' => 50,
                'equipmentList' => ['投影仪'],
                'safetyEquipment' => ['灭火器'],
                'facilityStatus' => '正常使用',
            ];
            $facility = $this->service->addFacility($institution->getId(), $facilityData);
            $facilityIds[] = $facility->getId();
        }

        // 批量安排检查
        $baseDate = new \DateTimeImmutable('+7 days');
        $results = $this->service->batchScheduleInspections($facilityIds, $baseDate, 7);

        self::assertCount(3, $results);
        foreach ($results as $result) {
            self::assertArrayHasKey('facility_id', $result);
            self::assertArrayHasKey('success', $result);
            self::assertTrue($result['success']);
            self::assertArrayHasKey('scheduled_date', $result);
        }
    }

    public function testBatchScheduleInspectionsWithInvalidId(): void
    {
        $facilityIds = ['invalid-id-1', 'invalid-id-2'];
        $baseDate = new \DateTimeImmutable('+7 days');

        $results = $this->service->batchScheduleInspections($facilityIds, $baseDate, 7);

        self::assertCount(2, $results);
        foreach ($results as $result) {
            self::assertFalse($result['success']);
            self::assertArrayHasKey('error', $result);
        }
    }

    public function testAddEquipmentToFacility(): void
    {
        // 创建测试机构和设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_添加设备',
            'institutionCode' => 'TEST_EQ_' . uniqid(),
            'registrationNumber' => 'REG_EQ_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        $facilityData = $this->getValidFacilityData();
        $facility = $this->service->addFacility($institution->getId(), $facilityData);

        $initialEquipmentCount = count($facility->getEquipmentList());

        // 添加单个设备
        $newEquipment = ['name' => '电脑', 'model' => 'Dell XPS', 'quantity' => 1];
        $updatedFacility = $this->service->addEquipmentToFacility($facility->getId(), $newEquipment);

        $equipmentList = $updatedFacility->getEquipmentList();
        self::assertCount($initialEquipmentCount + 1, $equipmentList);
        self::assertContains($newEquipment, $equipmentList);
    }

    public function testAddEquipmentToFacilityWithInvalidId(): void
    {
        $this->expectException(FacilityNotFoundException::class);

        $this->service->addEquipmentToFacility('invalid-id', ['name' => '设备']);
    }

    public function testAddSafetyEquipmentToFacility(): void
    {
        // 创建测试机构和设施
        $institutionService = self::getService(InstitutionService::class);
        $institution = $institutionService->createInstitution([
            'institutionName' => '测试机构_添加安全设备',
            'institutionCode' => 'TEST_SAFE_' . uniqid(),
            'registrationNumber' => 'REG_SAFE_' . uniqid(),
            'institutionType' => '企业',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '测试地址',
            'businessScope' => '职业技能培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
        ]);

        $facilityData = $this->getValidFacilityData();
        $facility = $this->service->addFacility($institution->getId(), $facilityData);

        $initialSafetyEquipmentCount = count($facility->getSafetyEquipment());

        // 添加安全设备
        $newSafetyEquipment = ['name' => '急救箱', 'type' => '标准型', 'quantity' => 1];
        $updatedFacility = $this->service->addSafetyEquipmentToFacility($facility->getId(), $newSafetyEquipment);

        $safetyEquipmentList = $updatedFacility->getSafetyEquipment();
        self::assertCount($initialSafetyEquipmentCount + 1, $safetyEquipmentList);
        self::assertContains($newSafetyEquipment, $safetyEquipmentList);
    }

    public function testAddSafetyEquipmentToFacilityWithInvalidId(): void
    {
        $this->expectException(FacilityNotFoundException::class);

        $this->service->addSafetyEquipmentToFacility('invalid-id', ['name' => '安全设备']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidFacilityData(): array
    {
        return [
            'facilityType' => '教室',
            'facilityName' => '测试教室1',
            'facilityLocation' => '1楼101室',
            'facilityArea' => 100.0,
            'capacity' => 50,
            'equipmentList' => [
                ['name' => '投影仪', 'model' => 'Pro-2024', 'quantity' => 1],
                ['name' => '白板', 'model' => 'Standard', 'quantity' => 2],
            ],
            'safetyEquipment' => [
                ['name' => '灭火器', 'type' => 'CO2', 'quantity' => 2],
                ['name' => '安全出口标识', 'type' => 'LED', 'quantity' => 4],
            ],
            'facilityStatus' => '正常使用',
        ];
    }
}
