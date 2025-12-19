<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * 机构资质服务接口
 *
 * 定义培训机构资质的核心业务逻辑接口
 */
interface QualificationServiceInterface
{
    /**
     * 添加机构资质
     * @param array<string, mixed> $qualificationData
     */
    public function addQualification(string $institutionId, array $qualificationData): InstitutionQualification;

    /**
     * 更新机构资质
     * @param array<string, mixed> $qualificationData
     */
    public function updateQualification(string $qualificationId, array $qualificationData): InstitutionQualification;

    /**
     * 检查机构资质到期情况
     * @return array<array<string, mixed>>
     */
    public function checkQualificationExpiry(string $institutionId): array;

    /**
     * 续期资质
     * @param array<string, mixed> $renewalData
     */
    public function renewQualification(string $qualificationId, array $renewalData): InstitutionQualification;

    /**
     * 获取即将到期的资质
     * @return array<InstitutionQualification>
     */
    public function getExpiringQualifications(int $days = 30): array;

    /**
     * 验证资质范围
     * @param array<string, mixed> $scope
     */
    public function validateQualificationScope(string $qualificationId, array $scope): bool;

    /**
     * 获取需要续期提醒的资质
     * @return array<InstitutionQualification>
     */
    public function getQualificationsNeedingRenewalReminder(int $reminderDays = 60): array;

    /**
     * 获取资质统计信息
     * @return array<string, mixed>
     */
    public function getQualificationStatistics(): array;

    /**
     * 分页获取资质列表
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function getQualificationsPaginated(int $page = 1, int $limit = 20, array $criteria = []): array;

    /**
     * 撤销资质
     */
    public function revokeQualification(string $qualificationId, string $reason): InstitutionQualification;

    /**
     * 暂停资质
     */
    public function suspendQualification(string $qualificationId, string $reason): InstitutionQualification;

    /**
     * 恢复资质
     */
    public function restoreQualification(string $qualificationId): InstitutionQualification;
}