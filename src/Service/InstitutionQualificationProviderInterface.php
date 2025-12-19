<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * 机构资质Provider接口
 */
interface InstitutionQualificationProviderInterface
{
    /**
     * 根据机构查找所有资质
     * @return array<InstitutionQualification>
     */
    public function findByInstitution(Institution $institution): array;

    /**
     * 根据机构查找有效资质
     * @return array<InstitutionQualification>
     */
    public function findValidByInstitution(Institution $institution): array;

    /**
     * 根据证书编号查找资质
     */
    public function findByCertificateNumber(string $certificateNumber): ?InstitutionQualification;

    /**
     * 根据资质类型查找资质
     * @return array<InstitutionQualification>
     */
    public function findByQualificationType(string $qualificationType): array;

    /**
     * 查找即将到期的资质（指定天数内）
     * @return array<InstitutionQualification>
     */
    public function findExpiringSoon(int $days = 30): array;

    /**
     * 查找已过期的资质
     * @return array<InstitutionQualification>
     */
    public function findExpired(): array;

    /**
     * 根据发证机关查找资质
     * @return array<InstitutionQualification>
     */
    public function findByIssuingAuthority(string $issuingAuthority): array;

    /**
     * 根据机构和资质类型查找资质
     * @return array<InstitutionQualification>
     */
    public function findByInstitutionAndType(Institution $institution, string $qualificationType): array;

    /**
     * 检查机构是否有指定类型的有效资质
     */
    public function hasValidQualification(Institution $institution, string $qualificationType): bool;

    /**
     * 获取资质统计信息
     * @return array<string, mixed>
     */
    public function getStatistics(): array;

    /**
     * 检查证书编号是否已存在
     */
    public function isCertificateNumberExists(string $certificateNumber, ?string $excludeId = null): bool;

    /**
     * 根据有效期范围查找资质
     * @return array<InstitutionQualification>
     */
    public function findByValidDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array;

    /**
     * 获取需要续期提醒的资质
     * @return array<InstitutionQualification>
     */
    public function findNeedingRenewalReminder(int $reminderDays = 60): array;

    /**
     * 分页查询资质
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function findPaginated(int $page = 1, int $limit = 20, array $criteria = []): array;

    /**
     * 保存实体
     */
    public function save(InstitutionQualification $entity, bool $flush = true): void;

    /**
     * 删除实体
     */
    public function remove(InstitutionQualification $entity, bool $flush = true): void;
}