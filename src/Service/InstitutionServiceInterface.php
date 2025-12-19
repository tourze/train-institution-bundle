<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\Common\Collections\Collection;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * 培训机构服务接口
 *
 * 定义培训机构的核心业务逻辑接口
 */
interface InstitutionServiceInterface
{
    /**
     * 创建培训机构
     * @param array<string, mixed> $institutionData
     */
    public function createInstitution(array $institutionData): Institution;

    /**
     * 更新培训机构
     * @param array<string, mixed> $institutionData
     */
    public function updateInstitution(string $institutionId, array $institutionData): Institution;

    /**
     * 根据ID获取机构
     */
    public function getInstitutionById(string $institutionId): ?Institution;

    /**
     * 根据状态获取机构列表
     * @return array<Institution>
     */
    public function getInstitutionsByStatus(string $status): array;

    /**
     * 验证机构数据
     * @param array<string, mixed> $institutionData
     * @return array<string>
     */
    public function validateInstitutionData(array $institutionData): array;

    /**
     * 变更机构状态
     */
    public function changeInstitutionStatus(string $institutionId, string $status, string $reason): Institution;

    /**
     * 检查机构AQ8011-2023合规性
     * @return array<string>
     */
    public function checkInstitutionCompliance(string $institutionId): array;

    /**
     * 获取机构统计信息
     * @return array<string, mixed>
     */
    public function getInstitutionStatistics(): array;

    /**
     * 搜索机构
     * @param array<string, mixed> $criteria
     * @return array<Institution>
     */
    public function searchInstitutions(array $criteria): array;

    /**
     * 获取机构的有效资质证书
     * @return Collection<int, InstitutionQualification>
     */
    public function getValidQualifications(Institution $institution): Collection;

    /**
     * 获取机构即将到期的资质证书（30天内）
     * @return Collection<int, InstitutionQualification>
     */
    public function getExpiringQualifications(Institution $institution): Collection;

    /**
     * 分页获取机构列表
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function getInstitutionsPaginated(int $page = 1, int $limit = 20, array $criteria = []): array;
}