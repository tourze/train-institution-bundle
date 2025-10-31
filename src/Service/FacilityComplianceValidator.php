<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * 设施合规验证器
 *
 * 负责验证设施是否符合AQ8011-2023标准
 */
final class FacilityComplianceValidator
{
    /**
     * 检查设施是否符合AQ8011-2023要求
     * @return array<string>
     */
    public function checkFacilityCompliance(InstitutionFacility $facility): array
    {
        $issues = [];
        $issues = array_merge($issues, $this->checkAreaRequirements($facility));
        $issues = array_merge($issues, $this->checkPerCapitaAreaRequirements($facility));
        $issues = array_merge($issues, $this->checkSafetyEquipmentRequirements($facility));

        return array_merge($issues, $this->checkFacilityStatusRequirement($facility));
    }

    /**
     * 检查面积要求
     * @return array<string>
     */
    private function checkAreaRequirements(InstitutionFacility $facility): array
    {
        $minAreaRequirements = $this->getMinAreaRequirements();
        $facilityType = $facility->getFacilityType();

        if (!isset($minAreaRequirements[$facilityType])) {
            return [];
        }

        $minArea = $minAreaRequirements[$facilityType];
        if ($facility->getFacilityArea() >= $minArea) {
            return [];
        }

        return ["设施面积不足，最小要求{$minArea}平方米，当前{$facility->getFacilityArea()}平方米"];
    }

    /**
     * 获取最小面积要求
     * @return array<string, float>
     */
    private function getMinAreaRequirements(): array
    {
        return [
            '教室' => 50.0,  // 最小50平方米
            '实训场地' => 100.0,  // 最小100平方米
            '办公区域' => 20.0,   // 最小20平方米
        ];
    }

    /**
     * 检查人均面积要求
     * @return array<string>
     */
    private function checkPerCapitaAreaRequirements(InstitutionFacility $facility): array
    {
        $facilityType = $facility->getFacilityType();
        if (!in_array($facilityType, ['教室', '实训场地'], true)) {
            return [];
        }

        $areaPerPerson = $facility->getFacilityArea() / max($facility->getCapacity(), 1);
        $minAreaPerPerson = '教室' === $facilityType ? 1.5 : 2.0;

        if ($areaPerPerson >= $minAreaPerPerson) {
            return [];
        }

        return ["人均面积不足，要求{$minAreaPerPerson}平方米/人，当前{$areaPerPerson}平方米/人"];
    }

    /**
     * 检查安全设备要求
     * @return array<string>
     */
    private function checkSafetyEquipmentRequirements(InstitutionFacility $facility): array
    {
        $issues = [];
        $requiredSafetyEquipment = ['灭火器', '烟雾报警器', '应急照明'];

        foreach ($requiredSafetyEquipment as $equipment) {
            if (!$this->hasSafetyEquipment($facility, $equipment)) {
                $issues[] = "缺少必要的安全设备：{$equipment}";
            }
        }

        return $issues;
    }

    /**
     * 检查是否有指定的安全设备
     */
    private function hasSafetyEquipment(InstitutionFacility $facility, string $equipmentName): bool
    {
        foreach ($facility->getSafetyEquipment() as $equipment) {
            if ($this->isEquipmentMatch($equipment, $equipmentName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查设备是否匹配
     */
    private function isEquipmentMatch(mixed $equipment, string $equipmentName): bool
    {
        if (is_array($equipment) && isset($equipment['name'])) {
            return $equipment['name'] === $equipmentName;
        }

        if (is_string($equipment)) {
            return $equipment === $equipmentName;
        }

        return false;
    }

    /**
     * 检查设施状态要求
     * @return array<string>
     */
    private function checkFacilityStatusRequirement(InstitutionFacility $facility): array
    {
        if ('正常使用' === $facility->getFacilityStatus()) {
            return [];
        }

        return ["设施状态异常：{$facility->getFacilityStatus()}"];
    }
}
