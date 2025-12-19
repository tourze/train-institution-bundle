<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Exception\ChangeRecordAlreadyProcessedException;
use Tourze\TrainInstitutionBundle\Exception\ChangeRecordNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InvalidChangeDataException;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 机构变更记录服务
 *
 * 提供培训机构变更记录的核心业务逻辑，包括变更记录、审批流程、历史查询等功能
 */
#[Autoconfigure(public: true)]
final class ChangeRecordService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
        private readonly InstitutionChangeRecordRepository $changeRecordRepository,
    ) {
    }

    /**
     * 记录变更
     * @param array<string, mixed> $changeData
     */
    public function recordChange(string $institutionId, array $changeData): InstitutionChangeRecord
    {
        // 验证数据
        $this->validateChangeData($changeData);

        // 获取机构
        $institution = $this->institutionRepository->find($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }
        assert($institution instanceof Institution);

        // 类型安全提取
        $changeType = $this->extractStringValue($changeData, 'changeType');
        $changeDetails = $this->extractArrayValue($changeData, 'changeDetails');
        $beforeData = $this->extractArrayValue($changeData, 'beforeData');
        $afterData = $this->extractArrayValue($changeData, 'afterData');
        $changeReason = $this->extractStringValue($changeData, 'changeReason');
        $changeOperator = $this->extractStringValue($changeData, 'changeOperator');
        $approvalStatus = $this->extractStringValue($changeData, 'approvalStatus', '待审批');

        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            $changeType,
            $changeDetails,
            $beforeData,
            $afterData,
            $changeReason,
            $changeOperator,
            $approvalStatus
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
        if (null === $changeRecord) {
            throw new ChangeRecordNotFoundException($recordId);
        }
        assert($changeRecord instanceof InstitutionChangeRecord);

        if ('待审批' !== $changeRecord->getApprovalStatus()) {
            throw new ChangeRecordAlreadyProcessedException('审批');
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
        if (null === $changeRecord) {
            throw new ChangeRecordNotFoundException($recordId);
        }
        assert($changeRecord instanceof InstitutionChangeRecord);

        if ('待审批' !== $changeRecord->getApprovalStatus()) {
            throw new ChangeRecordAlreadyProcessedException('拒绝');
        }

        $changeRecord->reject($approver);
        $this->entityManager->flush();

        return $changeRecord;
    }

    /**
     * 获取变更历史
     * @return array<InstitutionChangeRecord>
     */
    public function getChangeHistory(string $institutionId): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }
        assert($institution instanceof Institution);

        return $this->changeRecordRepository->findByInstitution($institution);
    }

    /**
     * 获取待审批的变更记录
     * @return array<InstitutionChangeRecord>
     */
    public function getPendingChanges(): array
    {
        return $this->changeRecordRepository->findPendingApproval();
    }

    /**
     * 根据变更类型获取记录
     * @return array<InstitutionChangeRecord>
     */
    public function getChangesByType(string $changeType): array
    {
        return $this->changeRecordRepository->findByChangeType($changeType);
    }

    /**
     * 生成变更报告
     * @return array<string, mixed>
     */
    public function generateChangeReport(string $institutionId): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }
        assert($institution instanceof Institution);

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
        $pendingChanges = array_filter($changeRecords, fn ($r) => '待审批' === $r->getApprovalStatus());

        return [
            'institution' => [
                'id' => $institution->getId(),
                'name' => $institution->getInstitutionName(),
            ],
            'summary' => [
                'total_changes' => count($changeRecords),
                'pending_approval' => count($pendingChanges),
                'approved_changes' => count(array_filter($changeRecords, fn ($r) => '已审批' === $r->getApprovalStatus())),
                'rejected_changes' => count(array_filter($changeRecords, fn ($r) => '已拒绝' === $r->getApprovalStatus())),
            ],
            'statistics' => [
                'by_type' => $typeStats,
                'by_status' => $statusStats,
                'by_operator' => $operatorStats,
                'by_month' => $monthlyStats,
            ],
            'recent_changes' => array_map(fn ($r) => [
                'id' => $r->getId(),
                'type' => $r->getChangeType(),
                'operator' => $r->getChangeOperator(),
                'date' => $r->getChangeDate()->format('Y-m-d H:i:s'),
                'status' => $r->getApprovalStatus(),
                'approver' => $r->getApprover(),
            ], $recentChanges),
            'pending_changes' => array_map(fn ($r) => [
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
     * @param array<string> $recordIds
     * @return array<array<string, mixed>>
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
            } catch (\Throwable $e) {
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
     * @param array<string> $recordIds
     * @return array<array<string, mixed>>
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
            } catch (\Throwable $e) {
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
     * @return array<string, mixed>
     */
    public function getChangeDetail(string $recordId): array
    {
        $changeRecord = $this->changeRecordRepository->find($recordId);
        if (null === $changeRecord) {
            throw new ChangeRecordNotFoundException($recordId);
        }
        assert($changeRecord instanceof InstitutionChangeRecord);

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
     * @return array<InstitutionChangeRecord>
     */
    public function getChangesByDateRange(string $institutionId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }
        assert($institution instanceof Institution);

        $allChanges = $this->changeRecordRepository->findByInstitution($institution);

        return array_filter($allChanges, function ($record) use ($startDate, $endDate) {
            $changeDate = $record->getChangeDate();

            return $changeDate >= $startDate && $changeDate <= $endDate;
        });
    }

    /**
     * 获取变更统计信息
     * @return array<string, mixed>
     */
    public function getChangeStatistics(): array
    {
        $allRecords = $this->changeRecordRepository->findAll();

        $totalCount = count($allRecords);
        $pendingCount = count(array_filter($allRecords, function (InstitutionChangeRecord $r): bool {
            return '待审批' === $r->getApprovalStatus();
        }));
        $approvedCount = count(array_filter($allRecords, function (InstitutionChangeRecord $r): bool {
            return '已审批' === $r->getApprovalStatus();
        }));
        $rejectedCount = count(array_filter($allRecords, function (InstitutionChangeRecord $r): bool {
            return '已拒绝' === $r->getApprovalStatus();
        }));

        // 按类型统计
        $typeStats = [];
        foreach ($allRecords as $record) {
            assert($record instanceof InstitutionChangeRecord);
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
     * @param array<string, mixed> $changeData
     */
    private function validateChangeData(array $changeData): void
    {
        $errors = [];

        $errors = array_merge($errors, $this->validateRequiredFields($changeData));
        $errors = array_merge($errors, $this->validateDataTypes($changeData));

        if ([] !== $errors) {
            throw new InvalidChangeDataException(implode('；', $errors));
        }
    }

    /**
     * 验证必填字段
     * @param array<string, mixed> $changeData
     * @return array<string>
     */
    private function validateRequiredFields(array $changeData): array
    {
        $errors = [];
        $requiredFields = $this->getRequiredFields();

        foreach ($requiredFields as $field => $label) {
            if (!isset($changeData[$field]) || '' === $changeData[$field] || [] === $changeData[$field]) {
                $errors[] = "{$label}不能为空";
            }
        }

        return $errors;
    }

    /**
     * 验证数据类型
     * @param array<string, mixed> $changeData
     * @return array<string>
     */
    private function validateDataTypes(array $changeData): array
    {
        $errors = [];
        $arrayFields = ['changeDetails', 'beforeData', 'afterData'];

        foreach ($arrayFields as $field) {
            if (isset($changeData[$field]) && '' !== $changeData[$field] && !is_array($changeData[$field])) {
                $fieldLabel = $this->getFieldLabel($field);
                $errors[] = "{$fieldLabel}必须是数组格式";
            }
        }

        return $errors;
    }

    /**
     * 获取必填字段定义
     * @return array<string, string>
     */
    private function getRequiredFields(): array
    {
        return [
            'changeType' => '变更类型',
            'changeDetails' => '变更详情',
            'beforeData' => '变更前数据',
            'afterData' => '变更后数据',
            'changeReason' => '变更原因',
            'changeOperator' => '变更操作人',
        ];
    }

    /**
     * 获取字段标签
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'changeDetails' => '变更详情',
            'beforeData' => '变更前数据',
            'afterData' => '变更后数据',
        ];

        return $labels[$field] ?? $field;
    }

    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     */
    private function extractStringValue(array $data, string $key, string $default = ''): string
    {
        if (!isset($data[$key])) {
            return $default;
        }

        $value = $data[$key];
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * 安全提取数组值
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function extractArrayValue(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            return [];
        }

        $value = $data[$key];
        if (!is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $value;
    }
}
