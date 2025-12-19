<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Exception\FacilityNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InvalidFacilityDataException;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 机构设施服务
 *
 * 提供培训机构设施的核心业务逻辑，包括设施添加、更新、检查调度、合规验证等功能
 */
#[Autoconfigure(public: true)]
final class FacilityService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
        private readonly InstitutionFacilityRepository $facilityRepository,
        private readonly FacilityComplianceValidator $complianceValidator,
        private readonly FacilityDataExtractor $dataExtractor,
        private readonly FacilityReportGenerator $reportGenerator,
    ) {
    }

    /**
     * 添加机构设施
     * @param array<string, mixed> $facilityData
     */
    public function addFacility(string $institutionId, array $facilityData): InstitutionFacility
    {
        // 验证数据
        $this->validateFacilityData($facilityData);

        // 获取机构
        $institution = $this->institutionRepository->find($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }
        assert($institution instanceof Institution);

        $facility = InstitutionFacility::create(
            $institution,
            $this->dataExtractor->extractString($facilityData, 'facilityType'),
            $this->dataExtractor->extractString($facilityData, 'facilityName'),
            $this->dataExtractor->extractString($facilityData, 'facilityLocation'),
            $this->dataExtractor->extractFloat($facilityData, 'facilityArea'),
            $this->dataExtractor->extractInt($facilityData, 'capacity'),
            $this->dataExtractor->extractArray($facilityData, 'equipmentList'),
            $this->dataExtractor->extractArray($facilityData, 'safetyEquipment'),
            $this->dataExtractor->extractString($facilityData, 'facilityStatus', '正常使用')
        );

        // 设置检查日期（如果提供）
        $this->setInspectionDatesIfProvided($facility, $facilityData);

        $this->entityManager->persist($facility);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 更新机构设施
     * @param array<string, mixed> $facilityData
     */
    public function updateFacility(string $facilityId, array $facilityData): InstitutionFacility
    {
        $facility = $this->getFacilityById($facilityId);

        $this->applyFacilityUpdates($facility, $facilityData);
        $this->persistFacilityChanges();

        return $facility;
    }

    /**
     * 安排设施检查
     */
    public function scheduleFacilityInspection(string $facilityId, \DateTimeImmutable $inspectionDate): void
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (null === $facility) {
            throw new FacilityNotFoundException($facilityId);
        }
        assert($facility instanceof InstitutionFacility);

        $facility->setNextInspectionDate($inspectionDate);
        $this->entityManager->flush();
    }

    /**
     * 完成设施检查
     */
    public function completeFacilityInspection(string $facilityId, \DateTimeImmutable $inspectionDate, \DateTimeImmutable $nextInspectionDate): InstitutionFacility
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (null === $facility) {
            throw new FacilityNotFoundException($facilityId);
        }
        assert($facility instanceof InstitutionFacility);

        $facility->setLastInspectionDate($inspectionDate);
        $facility->setNextInspectionDate($nextInspectionDate);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 获取设施利用率
     * @return array<string, mixed>
     */
    public function getFacilityUtilization(string $facilityId): array
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (null === $facility) {
            throw new FacilityNotFoundException($facilityId);
        }
        assert($facility instanceof InstitutionFacility);

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
     * @return array<string, mixed>
     */
    public function validateFacilityRequirements(string $institutionId): array
    {
        $institution = $this->getValidatedInstitution($institutionId);
        $facilities = $this->facilityRepository->findByInstitution($institution);

        $validationResults = $this->validateIndividualFacilities($facilities);
        $overallResults = $this->validateOverallRequirements($institution, $facilities);

        return array_merge($validationResults, $overallResults);
    }

    /**
     * 生成设施报告
     * @return array<string, mixed>
     */
    public function generateFacilityReport(string $institutionId): array
    {
        $institution = $this->getValidatedInstitution($institutionId);
        $facilities = $this->facilityRepository->findByInstitution($institution);

        return [
            'institution' => $this->reportGenerator->formatInstitutionInfo($institution),
            'summary' => $this->reportGenerator->generateSummaryStatistics($facilities, $institution),
            'statistics' => $this->reportGenerator->generateTypeAndStatusStatistics($facilities),
            'maintenance' => $this->reportGenerator->generateMaintenanceInfo($facilities),
            'compliance' => $this->validateFacilityRequirements($institutionId),
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * 获取需要检查的设施
     * @return array<InstitutionFacility>
     */
    public function getFacilitiesNeedingInspection(): array
    {
        return $this->facilityRepository->findNeedingInspection();
    }

    /**
     * 批量安排检查
     * @param array<string> $facilityIds
     * @return array<array<string, mixed>>
     */
    public function batchScheduleInspections(array $facilityIds, \DateTimeImmutable $baseDate, int $intervalDays = 7): array
    {
        $results = [];
        $currentDate = $baseDate;

        foreach ($facilityIds as $facilityId) {
            $result = $this->scheduleIndividualInspection($facilityId, $currentDate);
            $results[] = $result;

            if (true === $result['success']) {
                $currentDate = $currentDate->modify("+{$intervalDays} days");
            }
        }

        return $results;
    }

    /**
     * 添加设备到设施
     * @param array<string, mixed> $equipment
     */
    public function addEquipmentToFacility(string $facilityId, array $equipment): InstitutionFacility
    {
        $facility = $this->getFacilityById($facilityId);
        $currentEquipment = $facility->getEquipmentList();
        $updatedEquipment = $this->mergeEquipmentList($currentEquipment, $equipment);

        $facility->setEquipmentList($updatedEquipment);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 添加安全设备到设施
     * @param array<string, mixed> $safetyEquipment
     */
    public function addSafetyEquipmentToFacility(string $facilityId, array $safetyEquipment): InstitutionFacility
    {
        $facility = $this->getFacilityById($facilityId);
        $currentSafetyEquipment = $facility->getSafetyEquipment();
        $updatedSafetyEquipment = $this->mergeEquipmentList($currentSafetyEquipment, $safetyEquipment);

        $facility->setSafetyEquipment($updatedSafetyEquipment);
        $this->entityManager->flush();

        return $facility;
    }

    /**
     * 验证设施数据
     * @param array<string, mixed> $facilityData
     */
    private function validateFacilityData(array $facilityData): void
    {
        $errors = [];
        $errors = array_merge($errors, $this->checkRequiredFields($facilityData));
        $errors = array_merge($errors, $this->checkNumericFields($facilityData));
        $errors = array_merge($errors, $this->checkFacilityType($facilityData));

        if ([] !== $errors) {
            throw new InvalidFacilityDataException(implode('；', $errors));
        }
    }

    /**
     * 获取设施实体
     */
    private function getFacilityById(string $facilityId): InstitutionFacility
    {
        $facility = $this->facilityRepository->find($facilityId);
        if (null === $facility) {
            throw new FacilityNotFoundException($facilityId);
        }
        assert($facility instanceof InstitutionFacility);

        return $facility;
    }

    /**
     * 应用设施更新
     * @param array<string, mixed> $facilityData
     */
    private function applyFacilityUpdates(InstitutionFacility $facility, array $facilityData): void
    {
        $this->updateBasicFacilityFields($facility, $facilityData);
        $this->updateEquipmentFields($facility, $facilityData);
        $this->updateInspectionFields($facility, $facilityData);
    }

    /**
     * 更新基础设施字段
     * @param array<string, mixed> $facilityData
     */
    private function updateBasicFacilityFields(InstitutionFacility $facility, array $facilityData): void
    {
        $this->updateStringFieldIfPresent($facility, $facilityData, 'facilityType', fn ($v) => $facility->setFacilityType($v));
        $this->updateStringFieldIfPresent($facility, $facilityData, 'facilityName', fn ($v) => $facility->setFacilityName($v));
        $this->updateStringFieldIfPresent($facility, $facilityData, 'facilityLocation', fn ($v) => $facility->setFacilityLocation($v));
        $this->updateStringFieldIfPresent($facility, $facilityData, 'facilityStatus', fn ($v) => $facility->setFacilityStatus($v));

        $this->updateNumericFields($facility, $facilityData);
    }

    /**
     * 更新数值字段
     * @param array<string, mixed> $facilityData
     */
    private function updateNumericFields(InstitutionFacility $facility, array $facilityData): void
    {
        if (isset($facilityData['facilityArea']) && is_numeric($facilityData['facilityArea'])) {
            $facility->setFacilityArea((float) $facilityData['facilityArea']);
        }
        if (isset($facilityData['capacity']) && is_int($facilityData['capacity'])) {
            $facility->setCapacity($facilityData['capacity']);
        }
    }

    /**
     * 更新字符串字段（如果存在）
     * @param array<string, mixed> $data
     * @param callable(string): void $setter
     */
    private function updateStringFieldIfPresent(InstitutionFacility $facility, array $data, string $key, callable $setter): void
    {
        if (isset($data[$key]) && is_string($data[$key])) {
            $setter($data[$key]);
        }
    }

    /**
     * 更新设备字段
     * @param array<string, mixed> $facilityData
     */
    private function updateEquipmentFields(InstitutionFacility $facility, array $facilityData): void
    {
        if (isset($facilityData['equipmentList']) && is_array($facilityData['equipmentList'])) {
            $facility->setEquipmentList($facilityData['equipmentList']);
        }
        if (isset($facilityData['safetyEquipment']) && is_array($facilityData['safetyEquipment'])) {
            $facility->setSafetyEquipment($facilityData['safetyEquipment']);
        }
    }

    /**
     * 更新检查字段
     * @param array<string, mixed> $facilityData
     */
    private function updateInspectionFields(InstitutionFacility $facility, array $facilityData): void
    {
        if (isset($facilityData['lastInspectionDate']) && $facilityData['lastInspectionDate'] instanceof \DateTimeImmutable) {
            $facility->setLastInspectionDate($facilityData['lastInspectionDate']);
        }
        if (isset($facilityData['nextInspectionDate']) && $facilityData['nextInspectionDate'] instanceof \DateTimeImmutable) {
            $facility->setNextInspectionDate($facilityData['nextInspectionDate']);
        }
    }

    /**
     * 持久化设施变更
     */
    private function persistFacilityChanges(): void
    {
        $this->entityManager->flush();
    }

    /**
     * 检查必填字段
     * @param array<string, mixed> $facilityData
     * @return array<string>
     */
    private function checkRequiredFields(array $facilityData): array
    {
        $errors = [];
        $requiredFields = [
            'facilityType' => '设施类型',
            'facilityName' => '设施名称',
            'facilityLocation' => '设施位置',
            'facilityArea' => '设施面积',
            'capacity' => '容纳人数',
        ];

        foreach ($requiredFields as $field => $label) {
            if (!isset($facilityData[$field]) || '' === $facilityData[$field] || [] === $facilityData[$field]) {
                $errors[] = "{$label}不能为空";
            }
        }

        return $errors;
    }

    /**
     * 检查数值字段
     * @param array<string, mixed> $facilityData
     * @return array<string>
     */
    private function checkNumericFields(array $facilityData): array
    {
        $errors = [];

        if (isset($facilityData['facilityArea']) && (!is_numeric($facilityData['facilityArea']) || $facilityData['facilityArea'] <= 0)) {
            $errors[] = '设施面积必须是大于0的数值';
        }

        if (isset($facilityData['capacity']) && (!is_int($facilityData['capacity']) || $facilityData['capacity'] <= 0)) {
            $errors[] = '容纳人数必须是大于0的整数';
        }

        return $errors;
    }

    /**
     * 检查设施类型
     * @param array<string, mixed> $facilityData
     * @return array<string>
     */
    private function checkFacilityType(array $facilityData): array
    {
        $validTypes = ['教室', '实训场地', '办公区域', '会议室', '图书馆', '其他'];
        if (isset($facilityData['facilityType']) && '' !== $facilityData['facilityType'] && !in_array($facilityData['facilityType'], $validTypes, true)) {
            return ['设施类型无效，允许的类型：' . implode('、', $validTypes)];
        }

        return [];
    }

    /**
     * 检查设施是否符合AQ8011-2023要求
     * @return array<string>
     */
    private function checkFacilityAQ8011Compliance(InstitutionFacility $facility): array
    {
        return $this->complianceValidator->checkFacilityCompliance($facility);
    }

    /**
     * 设置检查日期（如果提供）
     * @param array<string, mixed> $facilityData
     */
    private function setInspectionDatesIfProvided(InstitutionFacility $facility, array $facilityData): void
    {
        if (isset($facilityData['lastInspectionDate']) && $facilityData['lastInspectionDate'] instanceof \DateTimeImmutable) {
            $facility->setLastInspectionDate($facilityData['lastInspectionDate']);
        }
        if (isset($facilityData['nextInspectionDate']) && $facilityData['nextInspectionDate'] instanceof \DateTimeImmutable) {
            $facility->setNextInspectionDate($facilityData['nextInspectionDate']);
        }
    }

    /**
     * 获取并验证机构实例
     */
    private function getValidatedInstitution(string $institutionId): Institution
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }
        assert($institution instanceof Institution);

        return $institution;
    }

    /**
     * 验证单个设施合规性
     * @param array<InstitutionFacility> $facilities
     * @return array<string, mixed>
     */
    private function validateIndividualFacilities(array $facilities): array
    {
        $validationResults = [];

        foreach ($facilities as $facility) {
            assert($facility instanceof InstitutionFacility);
            $issues = $this->checkFacilityAQ8011Compliance($facility);
            $validationResults[] = [
                'facility' => $facility,
                'is_compliant' => [] === $issues,
                'issues' => $issues,
            ];
        }

        return ['facilities' => $validationResults];
    }

    /**
     * 验证整体设施要求
     * @param array<InstitutionFacility> $facilities
     * @return array<string, mixed>
     */
    private function validateOverallRequirements(Institution $institution, array $facilities): array
    {
        $totalArea = $this->facilityRepository->getTotalAreaByInstitution($institution);
        $facilityCounts = $this->calculateFacilityCounts($facilities);
        $overallIssues = $this->identifyOverallIssues($totalArea, $facilityCounts);

        return [
            'overall_compliant' => [] === $overallIssues,
            'overall_issues' => $overallIssues,
            'total_area' => $totalArea,
            'facility_counts' => $facilityCounts,
        ];
    }

    /**
     * 计算设施数量统计
     * @param array<InstitutionFacility> $facilities
     * @return array<string, int>
     */
    private function calculateFacilityCounts(array $facilities): array
    {
        $classroomCount = $this->countFacilitiesByType($facilities, '教室');
        $trainingAreaCount = $this->countFacilitiesByType($facilities, '实训场地');

        return [
            'classroom' => $classroomCount,
            'training_area' => $trainingAreaCount,
            'total' => count($facilities),
        ];
    }

    /**
     * 按类型统计设施数量
     * @param array<InstitutionFacility> $facilities
     */
    private function countFacilitiesByType(array $facilities, string $type): int
    {
        return count(array_filter($facilities, function (InstitutionFacility $f) use ($type): bool {
            return $type === $f->getFacilityType();
        }));
    }

    /**
     * 识别整体合规问题
     * @param array<string, int> $facilityCounts
     * @return array<string>
     */
    private function identifyOverallIssues(float $totalArea, array $facilityCounts): array
    {
        $issues = [];

        if ($totalArea < 200) {
            $issues[] = '总面积不足200平方米';
        }
        if ($facilityCounts['classroom'] < 1) {
            $issues[] = '至少需要1间教室';
        }
        if ($facilityCounts['training_area'] < 1) {
            $issues[] = '至少需要1个实训场地';
        }

        return $issues;
    }

    /**
     * 合并设备列表
     * @param array<int|string, mixed> $currentList
     * @param array<string, mixed> $newEquipment
     * @return array<int|string, mixed>
     */
    private function mergeEquipmentList(array $currentList, array $newEquipment): array
    {
        // 检查是否是单个设备对象（包含 'name' 字段）
        if (isset($newEquipment['name'])) {
            // 单个设备对象
            $currentList[] = $newEquipment;
        } else {
            // 设备数组（可能是字符串数组），合并到现有列表
            $currentList = array_merge($currentList, $newEquipment);
        }

        return $currentList;
    }

    /**
     * 安排单个设施检查
     * @return array<string, mixed>
     */
    private function scheduleIndividualInspection(string $facilityId, \DateTimeImmutable $currentDate): array
    {
        try {
            $this->scheduleFacilityInspection($facilityId, $currentDate);

            return [
                'facility_id' => $facilityId,
                'scheduled_date' => $currentDate,
                'success' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'facility_id' => $facilityId,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
