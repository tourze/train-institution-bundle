<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;

/**
 * 机构变更记录Repository
 */
class InstitutionChangeRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionChangeRecord::class);
    }

    public function findByInstitution(Institution $institution): array
    {
        return $this->findBy(['institution' => $institution], ['createTime' => 'DESC']);
    }

    public function findPendingApproval(): array
    {
        return $this->findBy(['approvalStatus' => '待审批'], ['createTime' => 'ASC']);
    }

    public function findByChangeType(string $changeType): array
    {
        return $this->findBy(['changeType' => $changeType], ['createTime' => 'DESC']);
    }
} 