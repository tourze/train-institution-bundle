<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * 机构设施服务接口
 *
 * 定义培训机构设施的核心业务逻辑接口
 */
interface FacilityServiceInterface
{
    /**
     * 添加机构设施
     * @param array<string, mixed> $facilityData
     */
    public function addFacility(string $institutionId, array $facilityData): InstitutionFacility;

    /**
     * 更新机构设施
     * @param array<string, mixed> $facilityData
     */
    public function updateFacility(string $facilityId, array $facilityData): InstitutionFacility;

    /**
     * 安排设施检查
     */
    public function scheduleFacilityInspection(string $facilityId, \DateTimeImmutable $inspectionDate): void;

    /**
     * 完成设施检查
     */
    public function completeFacilityInspection(string $facilityId, \DateTimeImmutable $inspectionDate, \DateTimeImmutable $nextInspectionDate): InstitutionFacility;

    /**
     * 获取设施利用率
     * @return array<string, mixed>
     */
    public function getFacilityUtilization(string $facilityId): array;

    /**
     * 验证设施要求（AQ8011-2023标准）
     * @return array<string, mixed>
     */
    public function validateFacilityRequirements(string $institutionId): array;

    /**
     * 生成设施报告
     * @return array<string, mixed>
     */
    public function generateFacilityReport(string $institutionId): array;

    /**
     * 获取需要检查的设施
     * @return array<InstitutionFacility>
     */
    public function getFacilitiesNeedingInspection(): array;

    /**
     * 批量安排检查
     * @param array<string> $facilityIds
     * @return array<array<string, mixed>>
     */
    public function batchScheduleInspections(array $facilityIds, \DateTimeImmutable $baseDate, int $intervalDays = 7): array;

    /**
     * 添加设备到设施
     * @param array<string, mixed> $equipment
     */
    public function addEquipmentToFacility(string $facilityId, array $equipment): InstitutionFacility;

    /**
     * 添加安全设备到设施
     * @param array<string, mixed> $safetyEquipment
     */
    public function addSafetyEquipmentToFacility(string $facilityId, array $safetyEquipment): InstitutionFacility;
}