<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Contract;

use Doctrine\Persistence\ObjectRepository;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * 机构设施Repository接口
 *
 * @template-extends ObjectRepository<InstitutionFacility>
 */
interface InstitutionFacilityRepositoryInterface extends ObjectRepository
{
    /**
     * 根据机构查找所有设施
     * @return array<InstitutionFacility>
     */
    public function findByInstitution(Institution $institution): array;

    /**
     * 根据设施类型查找设施
     * @return array<InstitutionFacility>
     */
    public function findByFacilityType(string $facilityType): array;

    /**
     * 查找需要检查的设施
     * @return array<InstitutionFacility>
     */
    public function findNeedingInspection(): array;

    /**
     * 获取机构的设施总面积
     */
    public function getTotalAreaByInstitution(Institution $institution): float;

    /**
     * 保存实体
     */
    public function save(InstitutionFacility $entity, bool $flush = true): void;

    /**
     * 删除实体
     */
    public function remove(InstitutionFacility $entity, bool $flush = true): void;
}