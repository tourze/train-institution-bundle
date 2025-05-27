<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainInstitutionBundle\Entity\Institution;

/**
 * 培训机构Repository
 * 
 * 提供培训机构的数据访问方法，包括查询、统计等功能
 */
class InstitutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Institution::class);
    }

    /**
     * 根据机构代码查找机构
     */
    public function findByInstitutionCode(string $institutionCode): ?Institution
    {
        return $this->findOneBy(['institutionCode' => $institutionCode]);
    }

    /**
     * 根据机构状态查找机构列表
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['institutionStatus' => $status]);
    }

    /**
     * 根据机构类型查找机构列表
     */
    public function findByType(string $type): array
    {
        return $this->findBy(['institutionType' => $type]);
    }

    /**
     * 查找正常运营的机构
     */
    public function findActiveInstitutions(): array
    {
        return $this->findBy(['institutionStatus' => '正常运营']);
    }

    /**
     * 查找待审核的机构
     */
    public function findPendingInstitutions(): array
    {
        return $this->findBy(['institutionStatus' => '待审核']);
    }

    /**
     * 根据法人代表查找机构
     */
    public function findByLegalPerson(string $legalPerson): array
    {
        return $this->findBy(['legalPerson' => $legalPerson]);
    }

    /**
     * 根据注册号查找机构
     */
    public function findByRegistrationNumber(string $registrationNumber): ?Institution
    {
        return $this->findOneBy(['registrationNumber' => $registrationNumber]);
    }

    /**
     * 模糊搜索机构名称
     */
    public function searchByName(string $name): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.institutionName LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('i.institutionName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据地址搜索机构
     */
    public function searchByAddress(string $address): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.address LIKE :address')
            ->setParameter('address', '%' . $address . '%')
            ->orderBy('i.institutionName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取机构统计信息
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('i');
        
        $totalCount = $qb->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $statusStats = $this->createQueryBuilder('i')
            ->select('i.institutionStatus, COUNT(i.id) as count')
            ->groupBy('i.institutionStatus')
            ->getQuery()
            ->getResult();

        $typeStats = $this->createQueryBuilder('i')
            ->select('i.institutionType, COUNT(i.id) as count')
            ->groupBy('i.institutionType')
            ->getQuery()
            ->getResult();

        return [
            'total' => $totalCount,
            'by_status' => $statusStats,
            'by_type' => $typeStats,
        ];
    }

    /**
     * 获取最近创建的机构
     */
    public function findRecentlyCreated(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取最近更新的机构
     */
    public function findRecentlyUpdated(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.updateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据成立日期范围查找机构
     */
    public function findByEstablishDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.establishDate >= :startDate')
            ->andWhere('i.establishDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('i.establishDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 检查机构代码是否已存在
     */
    public function isInstitutionCodeExists(string $institutionCode, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.institutionCode = :code')
            ->setParameter('code', $institutionCode);

        if ($excludeId !== null) {
            $qb->andWhere('i.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 检查注册号是否已存在
     */
    public function isRegistrationNumberExists(string $registrationNumber, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.registrationNumber = :number')
            ->setParameter('number', $registrationNumber);

        if ($excludeId !== null) {
            $qb->andWhere('i.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 分页查询机构
     */
    public function findPaginated(int $page = 1, int $limit = 20, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('i');

        // 添加查询条件
        if (!empty($criteria['status'])) {
            $qb->andWhere('i.institutionStatus = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('i.institutionType = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (!empty($criteria['name'])) {
            $qb->andWhere('i.institutionName LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        $offset = ($page - 1) * $limit;
        
        $results = $qb->orderBy('i.createTime', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // 获取总数
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(i.id)')
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