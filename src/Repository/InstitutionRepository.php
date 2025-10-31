<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainInstitutionBundle\Entity\Institution;

/**
 * 培训机构Repository
 *
 * 提供培训机构的数据访问方法，包括查询、统计等功能
 * @extends ServiceEntityRepository<Institution>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: Institution::class)]
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
     * @return array<Institution>
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['institutionStatus' => $status]);
    }

    /**
     * 根据机构类型查找机构列表
     * @return array<Institution>
     */
    public function findByType(string $type): array
    {
        return $this->findBy(['institutionType' => $type]);
    }

    /**
     * 查找正常运营的机构
     * @return array<Institution>
     */
    public function findActiveInstitutions(): array
    {
        return $this->findBy(['institutionStatus' => '正常运营']);
    }

    /**
     * 查找待审核的机构
     * @return array<Institution>
     */
    public function findPendingInstitutions(): array
    {
        return $this->findBy(['institutionStatus' => '待审核']);
    }

    /**
     * 根据法人代表查找机构
     * @return array<Institution>
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
     * @return array<Institution>
     */
    public function searchByName(string $name): array
    {
        /** @var array<Institution> */
        return $this->createQueryBuilder('i')
            ->where('i.institutionName LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('i.institutionName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据地址搜索机构
     * @return array<Institution>
     */
    public function searchByAddress(string $address): array
    {
        /** @var array<Institution> */
        return $this->createQueryBuilder('i')
            ->where('i.address LIKE :address')
            ->setParameter('address', '%' . $address . '%')
            ->orderBy('i.institutionName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取机构统计信息
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('i');

        $totalCount = $qb->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $statusStats = $this->createQueryBuilder('i')
            ->select('i.institutionStatus, COUNT(i.id) as count')
            ->groupBy('i.institutionStatus')
            ->getQuery()
            ->getResult()
        ;

        $typeStats = $this->createQueryBuilder('i')
            ->select('i.institutionType, COUNT(i.id) as count')
            ->groupBy('i.institutionType')
            ->getQuery()
            ->getResult()
        ;

        return [
            'total' => $totalCount,
            'by_status' => $statusStats,
            'by_type' => $typeStats,
        ];
    }

    /**
     * 获取最近创建的机构
     * @return array<Institution>
     */
    public function findRecentlyCreated(int $limit = 10): array
    {
        /** @var array<Institution> */
        return $this->createQueryBuilder('i')
            ->orderBy('i.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取最近更新的机构
     * @return array<Institution>
     */
    public function findRecentlyUpdated(int $limit = 10): array
    {
        /** @var array<Institution> */
        return $this->createQueryBuilder('i')
            ->orderBy('i.updateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据成立日期范围查找机构
     * @return array<Institution>
     */
    public function findByEstablishDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<Institution> */
        return $this->createQueryBuilder('i')
            ->where('i.establishDate >= :startDate')
            ->andWhere('i.establishDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('i.establishDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 检查机构代码是否已存在
     */
    public function isInstitutionCodeExists(string $institutionCode, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.institutionCode = :code')
            ->setParameter('code', $institutionCode)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('i.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
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
            ->setParameter('number', $registrationNumber)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('i.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 分页查询机构
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function findPaginated(int $page = 1, int $limit = 20, array $criteria = []): array
    {
        // 构建数据查询
        $dataQb = $this->createQueryBuilder('i');
        $this->applyCriteria($dataQb, $criteria);

        $offset = ($page - 1) * $limit;
        $results = $dataQb->orderBy('i.createTime', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        // 构建计数查询
        $countQb = $this->createQueryBuilder('i');
        $this->applyCriteria($countQb, $criteria);
        $total = $countQb->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil((int) $total / $limit),
        ];
    }

    /**
     * 应用查询条件
     * @param array<string, mixed> $criteria
     */
    private function applyCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['status']) && '' !== $criteria['status']) {
            $qb->andWhere('i.institutionStatus = :status')
                ->setParameter('status', $criteria['status'])
            ;
        }

        if (isset($criteria['type']) && '' !== $criteria['type']) {
            $qb->andWhere('i.institutionType = :type')
                ->setParameter('type', $criteria['type'])
            ;
        }

        if (isset($criteria['name']) && '' !== $criteria['name'] && is_string($criteria['name'])) {
            $qb->andWhere('i.institutionName LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%')
            ;
        }
    }

    /**
     * 保存实体
     */
    public function save(Institution $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(Institution $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
