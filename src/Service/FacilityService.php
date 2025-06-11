<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 机构设施服务
 * 
 * 提供培训机构设施的核心业务逻辑，包括设施添加、更新、检查调度、合规验证等功能
 */
class FacilityService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
        private readonly InstitutionFacilityRepository $facilityRepository
    ) {
    }

    /**
     * 添加机构设施
     */
    public function addFacility(string $institutionId, array $facilityData): InstitutionFacility
    {
        // 验证数据
        $this->validateFacilityData($facilityData);

        // 获取机构
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $facility = new InstitutionFacility(
            $facilityData['id'],
            $institution,
            $facilityData['facilityType'],
            $facilityData['facilityName'],
            $facilityData['facilityLocation'],
            $facilityData['facilityArea'],
            $facilityData['capacity'],
            $facilityData['equipmentList'] ?? [],
            $facilityData['safetyEquipment'] ?? [],
            $facilityData['facilityStatus'] ?? '正常使用'
        );

        // 设置检查日期（如果提供）
        if (!empty($facilityData['lastInspectionDate'])) {
            $facility->setLastInspectionDate($facilityData['lastInspectionDate']);
        }
        if (!empty($facilityData['nextInspectionDate'])) {
            $facility->setNextInspectionDate($facilityData['nextInspectionDate']);
        }

        $this->entityManager->persist($facility);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 更新机构设施
     */
    public function updateFacility(string $facilityId, array $facilityData): InstitutionFacility
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (!$facility) {
            throw new \InvalidArgumentException('设施不存在');
        }

        // 更新字段
        if (isset($facilityData['facilityType'])) {
            $facility->setFacilityType($facilityData['facilityType']);
        }
        if (isset($facilityData['facilityName'])) {
            $facility->setFacilityName($facilityData['facilityName']);
        }
        if (isset($facilityData['facilityLocation'])) {
            $facility->setFacilityLocation($facilityData['facilityLocation']);
        }
        if (isset($facilityData['facilityArea'])) {
            $facility->setFacilityArea($facilityData['facilityArea']);
        }
        if (isset($facilityData['capacity'])) {
            $facility->setCapacity($facilityData['capacity']);
        }
        if (isset($facilityData['equipmentList'])) {
            $facility->setEquipmentList($facilityData['equipmentList']);
        }
        if (isset($facilityData['safetyEquipment'])) {
            $facility->setSafetyEquipment($facilityData['safetyEquipment']);
        }
        if (isset($facilityData['facilityStatus'])) {
            $facility->setFacilityStatus($facilityData['facilityStatus']);
        }
        if (isset($facilityData['lastInspectionDate'])) {
            $facility->setLastInspectionDate($facilityData['lastInspectionDate']);
        }
        if (isset($facilityData['nextInspectionDate'])) {
            $facility->setNextInspectionDate($facilityData['nextInspectionDate']);
        }

        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 安排设施检查
     */
    public function scheduleFacilityInspection(string $facilityId, \DateTimeImmutable $inspectionDate): void
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (!$facility) {
            throw new \InvalidArgumentException('设施不存在');
        }

        $facility->scheduleInspection($inspectionDate);
        $this->entityManager->flush();
    }

    /**
     * 完成设施检查
     */
    public function completeFacilityInspection(string $facilityId, \DateTimeImmutable $inspectionDate, \DateTimeImmutable $nextInspectionDate): InstitutionFacility
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (!$facility) {
            throw new \InvalidArgumentException('设施不存在');
        }

        $facility->completeInspection($inspectionDate, $nextInspectionDate);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 获取设施利用率
     */
    public function getFacilityUtilization(string $facilityId): array
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (!$facility) {
            throw new \InvalidArgumentException('设施不存在');
        }

        // 这里应该根据实际的使用记录来计算利用率
        // 简化实现，实际应该从使用记录表中获取数据
        return [
            'facility_id' => $facilityId,
            'facility_name' => $facility->getFacilityName(),
            'capacity' => $facility->getCapacity(),
            'area' => $facility->getFacilityArea(),
            'utilization_rate' => 0.0, // 需要根据实际使用记录计算
            'total_hours_available' => 0,
            'total_hours_used' => 0,
            'peak_usage_hours' => [],
            'average_occupancy' => 0,
        ];
    }

    /**
     * 验证设施要求（AQ8011-2023标准）
     */
    public function validateFacilityRequirements(string $institutionId): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $facilities = $this->facilityRepository->findByInstitution($institution);
        $validationResults = [];

        foreach ($facilities as $facility) {
            $issues = $facility->checkAQ8011Compliance();
            $validationResults[] = [
                'facility' => $facility,
                'is_compliant' => empty($issues),
                'issues' => $issues,
            ];
        }

        // 检查整体要求
        $totalArea = $this->facilityRepository->getTotalAreaByInstitution($institution);
        $facilitiesArray = $facilities;
        $classroomCount = count(array_filter($facilitiesArray, fn($f) => $f->getFacilityType() === '教室'));
        $trainingAreaCount = count(array_filter($facilitiesArray, fn($f) => $f->getFacilityType() === '实训场地'));

        $overallIssues = [];
        if ($totalArea < 200) {
            $overallIssues[] = '总面积不足200平方米';
        }
        if ($classroomCount < 1) {
            $overallIssues[] = '至少需要1间教室';
        }
        if ($trainingAreaCount < 1) {
            $overallIssues[] = '至少需要1个实训场地';
        }

        return [
            'facilities' => $validationResults,
            'overall_compliant' => empty($overallIssues),
            'overall_issues' => $overallIssues,
            'total_area' => $totalArea,
            'facility_counts' => [
                'classroom' => $classroomCount,
                'training_area' => $trainingAreaCount,
                'total' => count($facilities),
            ],
        ];
    }

    /**
     * 生成设施报告
     */
    public function generateFacilityReport(string $institutionId): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $facilities = $this->facilityRepository->findByInstitution($institution);
        $facilitiesArray = $facilities;
        $totalArea = $this->facilityRepository->getTotalAreaByInstitution($institution);

        // 按类型统计
        $typeStats = [];
        $statusStats = [];
        $totalCapacity = 0;

        foreach ($facilities as $facility) {
            $type = $facility->getFacilityType();
            $status = $facility->getFacilityStatus();

            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;
            $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
            $totalCapacity += $facility->getCapacity();
        }

        // 检查需要检查的设施
        $needingInspection = array_filter($facilitiesArray, fn($f) => $f->needsInspection());

        // 合规性检查
        $complianceResult = $this->validateFacilityRequirements($institutionId);

        return [
            'institution' => [
                'id' => $institution->getId(),
                'name' => $institution->getInstitutionName(),
            ],
            'summary' => [
                'total_facilities' => count($facilities),
                'total_area' => $totalArea,
                'total_capacity' => $totalCapacity,
                'average_area_per_facility' => count($facilities) > 0 ? $totalArea / count($facilities) : 0,
                'average_capacity_per_facility' => count($facilities) > 0 ? $totalCapacity / count($facilities) : 0,
            ],
            'statistics' => [
                'by_type' => $typeStats,
                'by_status' => $statusStats,
            ],
            'maintenance' => [
                'needing_inspection' => count($needingInspection),
                'inspection_list' => array_map(fn($f) => [
                    'id' => $f->getId(),
                    'name' => $f->getFacilityName(),
                    'type' => $f->getFacilityType(),
                    'last_inspection' => $f->getLastInspectionDate()?->format('Y-m-d'),
                    'next_inspection' => $f->getNextInspectionDate()?->format('Y-m-d'),
                ], $needingInspection),
            ],
            'compliance' => $complianceResult,
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * 获取需要检查的设施
     */
    public function getFacilitiesNeedingInspection(): array
    {
        return $this->facilityRepository->findNeedingInspection();
    }

    /**
     * 批量安排检查
     */
    public function batchScheduleInspections(array $facilityIds, \DateTimeImmutable $baseDate, int $intervalDays = 7): array
    {
        $results = [];
        $currentDate = $baseDate;

        foreach ($facilityIds as $facilityId) {
            try {
                $this->scheduleFacilityInspection($facilityId, $currentDate);
                $results[] = [
                    'facility_id' => $facilityId,
                    'scheduled_date' => $currentDate,
                    'success' => true,
                ];
                $currentDate = $currentDate->modify("+{$intervalDays} days");
            } catch  (\Throwable $e) {
                $results[] = [
                    'facility_id' => $facilityId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * 添加设备到设施
     */
    public function addEquipmentToFacility(string $facilityId, array $equipment): InstitutionFacility
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (!$facility) {
            throw new \InvalidArgumentException('设施不存在');
        }

        $facility->addEquipment($equipment);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 添加安全设备到设施
     */
    public function addSafetyEquipmentToFacility(string $facilityId, array $safetyEquipment): InstitutionFacility
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (!$facility) {
            throw new \InvalidArgumentException('设施不存在');
        }

        $facility->addSafetyEquipment($safetyEquipment);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 验证设施数据
     */
    private function validateFacilityData(array $facilityData): void
    {
        $errors = [];

        // 必填字段验证
        $requiredFields = [
            'facilityType' => '设施类型',
            'facilityName' => '设施名称',
            'facilityLocation' => '设施位置',
            'facilityArea' => '设施面积',
            'capacity' => '容纳人数',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($facilityData[$field])) {
                $errors[] = "{$label}不能为空";
            }
        }

        // 数值验证
        if (!empty($facilityData['facilityArea']) && (!is_numeric($facilityData['facilityArea']) || $facilityData['facilityArea'] <= 0)) {
            $errors[] = '设施面积必须是大于0的数值';
        }

        if (!empty($facilityData['capacity']) && (!is_int($facilityData['capacity']) || $facilityData['capacity'] <= 0)) {
            $errors[] = '容纳人数必须是大于0的整数';
        }

        // 设施类型验证
        $validTypes = ['教室', '实训场地', '办公区域', '会议室', '图书馆', '其他'];
        if (!empty($facilityData['facilityType']) && !in_array($facilityData['facilityType'], $validTypes, true)) {
            $errors[] = '设施类型无效，允许的类型：' . implode('、', $validTypes);
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode('；', $errors));
        }
    }

    /**
     * 生成ID
     */
    private function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
} 