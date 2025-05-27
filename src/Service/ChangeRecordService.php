<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 机构变更记录服务
 * 
 * 提供培训机构变更记录的核心业务逻辑，包括变更记录、审批流程、历史查询等功能
 */
class ChangeRecordService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
        private readonly InstitutionChangeRecordRepository $changeRecordRepository
    ) {
    }

    /**
     * 记录变更
     */
    public function recordChange(string $institutionId, array $changeData): InstitutionChangeRecord
    {
        // 验证数据
        $this->validateChangeData($changeData);

        // 获取机构
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            $changeData['changeType'],
            $changeData['changeDetails'],
            $changeData['beforeData'],
            $changeData['afterData'],
            $changeData['changeReason'],
            $changeData['changeOperator'],
            $changeData['approvalStatus'] ?? '待审批'
        );

        $this->entityManager->persist($changeRecord);
        $this->entityManager->flush();

        return $changeRecord;
    }

    /**
     * 审批变更
     */
    public function approveChange(string $recordId, string $approver): InstitutionChangeRecord
    {
        $changeRecord = $this->changeRecordRepository->find($recordId);
        if (!$changeRecord) {
            throw new \InvalidArgumentException('变更记录不存在');
        }

        if ($changeRecord->getApprovalStatus() !== '待审批') {
            throw new \InvalidArgumentException('该变更记录已处理，无法重复审批');
        }

        $changeRecord->approve($approver);
        $this->entityManager->flush();

        return $changeRecord;
    }

    /**
     * 拒绝变更
     */
    public function rejectChange(string $recordId, string $approver, string $reason = ''): InstitutionChangeRecord
    {
        $changeRecord = $this->changeRecordRepository->find($recordId);
        if (!$changeRecord) {
            throw new \InvalidArgumentException('变更记录不存在');
        }

        if ($changeRecord->getApprovalStatus() !== '待审批') {
            throw new \InvalidArgumentException('该变更记录已处理，无法重复拒绝');
        }

        $changeRecord->reject($approver);
        $this->entityManager->flush();

        return $changeRecord;
    }

    /**
     * 获取变更历史
     */
    public function getChangeHistory(string $institutionId): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        return $this->changeRecordRepository->findByInstitution($institution);
    }

    /**
     * 获取待审批的变更记录
     */
    public function getPendingChanges(): array
    {
        return $this->changeRecordRepository->findPendingApproval();
    }

    /**
     * 根据变更类型获取记录
     */
    public function getChangesByType(string $changeType): array
    {
        return $this->changeRecordRepository->findByChangeType($changeType);
    }

    /**
     * 生成变更报告
     */
    public function generateChangeReport(string $institutionId): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $changeRecords = $this->changeRecordRepository->findByInstitution($institution);

        // 按类型统计
        $typeStats = [];
        $statusStats = [];
        $operatorStats = [];
        $monthlyStats = [];

        foreach ($changeRecords as $record) {
            $type = $record->getChangeType();
            $status = $record->getApprovalStatus();
            $operator = $record->getChangeOperator();
            $month = $record->getChangeDate()->format('Y-m');

            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;
            $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
            $operatorStats[$operator] = ($operatorStats[$operator] ?? 0) + 1;
            $monthlyStats[$month] = ($monthlyStats[$month] ?? 0) + 1;
        }

        // 最近的变更
        $recentChanges = array_slice($changeRecords, 0, 10);

        // 待审批的变更
        $pendingChanges = array_filter($changeRecords, fn($r) => $r->getApprovalStatus() === '待审批');

        return [
            'institution' => [
                'id' => $institution->getId(),
                'name' => $institution->getInstitutionName(),
            ],
            'summary' => [
                'total_changes' => count($changeRecords),
                'pending_approval' => count($pendingChanges),
                'approved_changes' => count(array_filter($changeRecords, fn($r) => $r->getApprovalStatus() === '已审批')),
                'rejected_changes' => count(array_filter($changeRecords, fn($r) => $r->getApprovalStatus() === '已拒绝')),
            ],
            'statistics' => [
                'by_type' => $typeStats,
                'by_status' => $statusStats,
                'by_operator' => $operatorStats,
                'by_month' => $monthlyStats,
            ],
            'recent_changes' => array_map(fn($r) => [
                'id' => $r->getId(),
                'type' => $r->getChangeType(),
                'operator' => $r->getChangeOperator(),
                'date' => $r->getChangeDate()->format('Y-m-d H:i:s'),
                'status' => $r->getApprovalStatus(),
                'approver' => $r->getApprover(),
            ], $recentChanges),
            'pending_changes' => array_map(fn($r) => [
                'id' => $r->getId(),
                'type' => $r->getChangeType(),
                'operator' => $r->getChangeOperator(),
                'date' => $r->getChangeDate()->format('Y-m-d H:i:s'),
                'reason' => $r->getChangeReason(),
            ], $pendingChanges),
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * 批量审批变更
     */
    public function batchApproveChanges(array $recordIds, string $approver): array
    {
        $results = [];

        foreach ($recordIds as $recordId) {
            try {
                $changeRecord = $this->approveChange($recordId, $approver);
                $results[] = [
                    'record_id' => $recordId,
                    'success' => true,
                    'change_type' => $changeRecord->getChangeType(),
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'record_id' => $recordId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * 批量拒绝变更
     */
    public function batchRejectChanges(array $recordIds, string $approver, string $reason = ''): array
    {
        $results = [];

        foreach ($recordIds as $recordId) {
            try {
                $changeRecord = $this->rejectChange($recordId, $approver, $reason);
                $results[] = [
                    'record_id' => $recordId,
                    'success' => true,
                    'change_type' => $changeRecord->getChangeType(),
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'record_id' => $recordId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * 获取变更详情
     */
    public function getChangeDetail(string $recordId): array
    {
        $changeRecord = $this->changeRecordRepository->find($recordId);
        if (!$changeRecord) {
            throw new \InvalidArgumentException('变更记录不存在');
        }

        return [
            'id' => $changeRecord->getId(),
            'institution' => [
                'id' => $changeRecord->getInstitution()->getId(),
                'name' => $changeRecord->getInstitution()->getInstitutionName(),
            ],
            'change_type' => $changeRecord->getChangeType(),
            'change_details' => $changeRecord->getChangeDetails(),
            'before_data' => $changeRecord->getBeforeData(),
            'after_data' => $changeRecord->getAfterData(),
            'change_reason' => $changeRecord->getChangeReason(),
            'change_date' => $changeRecord->getChangeDate()->format('Y-m-d H:i:s'),
            'change_operator' => $changeRecord->getChangeOperator(),
            'approval_status' => $changeRecord->getApprovalStatus(),
            'approver' => $changeRecord->getApprover(),
            'approval_date' => $changeRecord->getApprovalDate()?->format('Y-m-d H:i:s'),
            'create_time' => $changeRecord->getCreateTime()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 根据日期范围获取变更记录
     */
    public function getChangesByDateRange(string $institutionId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $allChanges = $this->changeRecordRepository->findByInstitution($institution);
        
        return array_filter($allChanges, function ($record) use ($startDate, $endDate) {
            $changeDate = $record->getChangeDate();
            return $changeDate >= $startDate && $changeDate <= $endDate;
        });
    }

    /**
     * 获取变更统计信息
     */
    public function getChangeStatistics(): array
    {
        $allRecords = $this->changeRecordRepository->findAll();

        $totalCount = count($allRecords);
        $pendingCount = count(array_filter($allRecords, fn($r) => $r->getApprovalStatus() === '待审批'));
        $approvedCount = count(array_filter($allRecords, fn($r) => $r->getApprovalStatus() === '已审批'));
        $rejectedCount = count(array_filter($allRecords, fn($r) => $r->getApprovalStatus() === '已拒绝'));

        // 按类型统计
        $typeStats = [];
        foreach ($allRecords as $record) {
            $type = $record->getChangeType();
            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;
        }

        return [
            'total' => $totalCount,
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'approval_rate' => $totalCount > 0 ? round($approvedCount / $totalCount * 100, 2) : 0,
            'by_type' => $typeStats,
        ];
    }

    /**
     * 验证变更数据
     */
    private function validateChangeData(array $changeData): void
    {
        $errors = [];

        // 必填字段验证
        $requiredFields = [
            'changeType' => '变更类型',
            'changeDetails' => '变更详情',
            'beforeData' => '变更前数据',
            'afterData' => '变更后数据',
            'changeReason' => '变更原因',
            'changeOperator' => '变更操作人',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($changeData[$field])) {
                $errors[] = "{$label}不能为空";
            }
        }

        // 数据类型验证
        if (!empty($changeData['changeDetails']) && !is_array($changeData['changeDetails'])) {
            $errors[] = '变更详情必须是数组格式';
        }

        if (!empty($changeData['beforeData']) && !is_array($changeData['beforeData'])) {
            $errors[] = '变更前数据必须是数组格式';
        }

        if (!empty($changeData['afterData']) && !is_array($changeData['afterData'])) {
            $errors[] = '变更后数据必须是数组格式';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode('；', $errors));
        }
    }

    /**
     * 生成ID
     */
    private function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
} 