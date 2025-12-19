<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Contract\InstitutionFacilityRepositoryInterface;

/**
 * 设施报告生成器
 *
 * 负责生成设施相关的统计报告和维护信息
 */
final class FacilityReportGenerator
{
    public function __construct(
        private readonly InstitutionFacilityRepositoryInterface $facilityRepository,
    ) {
    }

    /**
     * 格式化机构信息
     * @return array<string, mixed>
     */
    public function formatInstitutionInfo(Institution $institution): array
    {
        return [
            'id' => $institution->getId(),
            'name' => $institution->getInstitutionName(),
        ];
    }

    /**
     * 生成汇总统计信息
     * @param array<InstitutionFacility> $facilities
     * @return array<string, mixed>
     */
    public function generateSummaryStatistics(array $facilities, Institution $institution): array
    {
        $totalArea = $this->facilityRepository->getTotalAreaByInstitution($institution);
        $totalCapacity = $this->calculateTotalCapacity($facilities);
        $facilityCount = count($facilities);

        return [
            'total_facilities' => $facilityCount,
            'total_area' => $totalArea,
            'total_capacity' => $totalCapacity,
            'average_area_per_facility' => $facilityCount > 0 ? $totalArea / $facilityCount : 0,
            'average_capacity_per_facility' => $facilityCount > 0 ? $totalCapacity / $facilityCount : 0,
        ];
    }

    /**
     * 计算总容量
     * @param array<InstitutionFacility> $facilities
     */
    private function calculateTotalCapacity(array $facilities): int
    {
        $totalCapacity = 0;
        foreach ($facilities as $facility) {
            assert($facility instanceof InstitutionFacility);
            $totalCapacity += $facility->getCapacity();
        }

        return $totalCapacity;
    }

    /**
     * 生成类型和状态统计
     * @param array<InstitutionFacility> $facilities
     * @return array<string, array<string, int>>
     */
    public function generateTypeAndStatusStatistics(array $facilities): array
    {
        $typeStats = [];
        $statusStats = [];

        foreach ($facilities as $facility) {
            assert($facility instanceof InstitutionFacility);
            $type = $facility->getFacilityType();
            $status = $facility->getFacilityStatus();

            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;
            $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
        }

        return [
            'by_type' => $typeStats,
            'by_status' => $statusStats,
        ];
    }

    /**
     * 生成维护信息
     * @param array<InstitutionFacility> $facilities
     * @return array<string, mixed>
     */
    public function generateMaintenanceInfo(array $facilities): array
    {
        $needingInspection = array_filter($facilities, function (InstitutionFacility $f): bool {
            return $this->facilitiNeedsInspection($f);
        });

        return [
            'needing_inspection' => count($needingInspection),
            'inspection_list' => $this->formatInspectionList($needingInspection),
        ];
    }

    /**
     * 检查设施是否需要检查
     */
    private function facilitiNeedsInspection(InstitutionFacility $facility): bool
    {
        $nextInspectionDate = $facility->getNextInspectionDate();
        if (null === $nextInspectionDate) {
            return true;
        }

        return $nextInspectionDate <= new \DateTimeImmutable();
    }

    /**
     * 格式化检查列表
     * @param array<InstitutionFacility> $facilities
     * @return array<array<string, mixed>>
     */
    private function formatInspectionList(array $facilities): array
    {
        return array_map(function (InstitutionFacility $f): array {
            return [
                'id' => $f->getId(),
                'name' => $f->getFacilityName(),
                'type' => $f->getFacilityType(),
                'last_inspection' => $f->getLastInspectionDate()?->format('Y-m-d'),
                'next_inspection' => $f->getNextInspectionDate()?->format('Y-m-d'),
            ];
        }, $facilities);
    }
}
