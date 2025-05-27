<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * 机构设施Repository
 */
class InstitutionFacilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionFacility::class);
    }

    public function findByInstitution(Institution $institution): array
    {
        return $this->findBy(['institution' => $institution], ['createTime' => 'DESC']);
    }

    public function findByFacilityType(string $facilityType): array
    {
        return $this->findBy(['facilityType' => $facilityType]);
    }

    public function findNeedingInspection(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('f')
            ->where('f.nextInspectionDate IS NULL OR f.nextInspectionDate <= :now')
            ->setParameter('now', $now)
            ->orderBy('f.nextInspectionDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalAreaByInstitution(Institution $institution): float
    {
        $result = $this->createQueryBuilder('f')
            ->select('SUM(f.facilityArea)')
            ->where('f.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }
} 