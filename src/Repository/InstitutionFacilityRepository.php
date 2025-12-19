<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainInstitutionBundle\Contract\InstitutionFacilityRepositoryInterface;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * 机构设施Repository
 * @extends ServiceEntityRepository<InstitutionFacility>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: InstitutionFacility::class)]
final class InstitutionFacilityRepository extends ServiceEntityRepository implements InstitutionFacilityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionFacility::class);
    }

    /**
     * @return array<InstitutionFacility>
     */
    public function findByInstitution(Institution $institution): array
    {
        return $this->findBy(['institution' => $institution], ['createTime' => 'DESC']);
    }

    /**
     * @return array<InstitutionFacility>
     */
    public function findByFacilityType(string $facilityType): array
    {
        return $this->findBy(['facilityType' => $facilityType]);
    }

    /**
     * @return array<InstitutionFacility>
     */
    public function findNeedingInspection(): array
    {
        $now = new \DateTimeImmutable();

        /** @var array<InstitutionFacility> */
        return $this->createQueryBuilder('f')
            ->where('f.nextInspectionDate IS NULL OR f.nextInspectionDate <= :now')
            ->setParameter('now', $now)
            ->orderBy('f.nextInspectionDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalAreaByInstitution(Institution $institution): float
    {
        $result = $this->createQueryBuilder('f')
            ->select('SUM(f.facilityArea)')
            ->where('f.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (float) ($result ?? 0.0);
    }

    /**
     * 保存实体
     */
    public function save(InstitutionFacility $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(InstitutionFacility $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
