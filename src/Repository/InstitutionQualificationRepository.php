<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * 机构资质Repository
 * 
 * 提供机构资质的数据访问方法，包括到期检查、有效性验证等功能
 */
class InstitutionQualificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionQualification::class);
    }

    /**
     * 根据机构查找所有资质
     */
    public function findByInstitution(Institution $institution): array
    {
        return $this->findBy(['institution' => $institution], ['createTime' => 'DESC']);
    }

    /**
     * 根据机构查找有效资质
     */
    public function findValidByInstitution(Institution $institution): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('q')
            ->where('q.institution = :institution')
            ->andWhere('q.qualificationStatus = :status')
            ->andWhere('q.validFrom <= :now')
            ->andWhere('q.validTo > :now')
            ->setParameter('institution', $institution)
            ->setParameter('status', '有效')
            ->setParameter('now', $now)
            ->orderBy('q.validTo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据证书编号查找资质
     */
    public function findByCertificateNumber(string $certificateNumber): ?InstitutionQualification
    {
        return $this->findOneBy(['certificateNumber' => $certificateNumber]);
    }

    /**
     * 根据资质类型查找资质
     */
    public function findByQualificationType(string $qualificationType): array
    {
        return $this->findBy(['qualificationType' => $qualificationType]);
    }

    /**
     * 查找即将到期的资质（指定天数内）
     */
    public function findExpiringSoon(int $days = 30): array
    {
        $now = new \DateTimeImmutable();
        $futureDate = $now->modify("+{$days} days");
        
        return $this->createQueryBuilder('q')
            ->where('q.qualificationStatus = :status')
            ->andWhere('q.validTo > :now')
            ->andWhere('q.validTo <= :futureDate')
            ->setParameter('status', '有效')
            ->setParameter('now', $now)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('q.validTo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找已过期的资质
     */
    public function findExpired(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('q')
            ->where('q.qualificationStatus = :status')
            ->andWhere('q.validTo <= :now')
            ->setParameter('status', '有效')
            ->setParameter('now', $now)
            ->orderBy('q.validTo', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据发证机关查找资质
     */
    public function findByIssuingAuthority(string $issuingAuthority): array
    {
        return $this->findBy(['issuingAuthority' => $issuingAuthority]);
    }

    /**
     * 根据机构和资质类型查找资质
     */
    public function findByInstitutionAndType(Institution $institution, string $qualificationType): array
    {
        return $this->findBy([
            'institution' => $institution,
            'qualificationType' => $qualificationType
        ], ['createTime' => 'DESC']);
    }

    /**
     * 检查机构是否有指定类型的有效资质
     */
    public function hasValidQualification(Institution $institution, string $qualificationType): bool
    {
        $now = new \DateTimeImmutable();
        
        $count = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.institution = :institution')
            ->andWhere('q.qualificationType = :type')
            ->andWhere('q.qualificationStatus = :status')
            ->andWhere('q.validFrom <= :now')
            ->andWhere('q.validTo > :now')
            ->setParameter('institution', $institution)
            ->setParameter('type', $qualificationType)
            ->setParameter('status', '有效')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * 获取资质统计信息
     */
    public function getStatistics(): array
    {
        $now = new \DateTimeImmutable();
        
        // 总数统计
        $totalCount = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // 按状态统计
        $statusStats = $this->createQueryBuilder('q')
            ->select('q.qualificationStatus, COUNT(q.id) as count')
            ->groupBy('q.qualificationStatus')
            ->getQuery()
            ->getResult();

        // 按类型统计
        $typeStats = $this->createQueryBuilder('q')
            ->select('q.qualificationType, COUNT(q.id) as count')
            ->groupBy('q.qualificationType')
            ->getQuery()
            ->getResult();

        // 有效资质数量
        $validCount = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.qualificationStatus = :status')
            ->andWhere('q.validFrom <= :now')
            ->andWhere('q.validTo > :now')
            ->setParameter('status', '有效')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        // 即将到期数量（30天内）
        $expiringSoonCount = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.qualificationStatus = :status')
            ->andWhere('q.validTo > :now')
            ->andWhere('q.validTo <= :futureDate')
            ->setParameter('status', '有效')
            ->setParameter('now', $now)
            ->setParameter('futureDate', $now->modify('+30 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $totalCount,
            'valid' => $validCount,
            'expiring_soon' => $expiringSoonCount,
            'by_status' => $statusStats,
            'by_type' => $typeStats,
        ];
    }

    /**
     * 检查证书编号是否已存在
     */
    public function isCertificateNumberExists(string $certificateNumber, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.certificateNumber = :number')
            ->setParameter('number', $certificateNumber);

        if ($excludeId !== null) {
            $qb->andWhere('q.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 根据有效期范围查找资质
     */
    public function findByValidDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.validTo >= :startDate')
            ->andWhere('q.validTo <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('q.validTo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取需要续期提醒的资质
     */
    public function findNeedingRenewalReminder(int $reminderDays = 60): array
    {
        $now = new \DateTimeImmutable();
        $reminderDate = $now->modify("+{$reminderDays} days");
        
        return $this->createQueryBuilder('q')
            ->where('q.qualificationStatus = :status')
            ->andWhere('q.validTo > :now')
            ->andWhere('q.validTo <= :reminderDate')
            ->setParameter('status', '有效')
            ->setParameter('now', $now)
            ->setParameter('reminderDate', $reminderDate)
            ->orderBy('q.validTo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 分页查询资质
     */
    public function findPaginated(int $page = 1, int $limit = 20, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('q')
            ->leftJoin('q.institution', 'i');

        // 添加查询条件
        if (!empty($criteria['institution_id'])) {
            $qb->andWhere('q.institution = :institution')
               ->setParameter('institution', $criteria['institution_id']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('q.qualificationStatus = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('q.qualificationType = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (!empty($criteria['certificate_number'])) {
            $qb->andWhere('q.certificateNumber LIKE :number')
               ->setParameter('number', '%' . $criteria['certificate_number'] . '%');
        }

        $offset = ($page - 1) * $limit;
        
        $results = $qb->orderBy('q.createTime', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // 获取总数
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(q.id)')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
        ];
    }
} 