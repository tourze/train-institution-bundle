<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 培训机构服务
 * 
 * 提供培训机构的核心业务逻辑，包括创建、更新、状态管理等功能
 */
class InstitutionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
        private readonly InstitutionChangeRecordRepository $changeRecordRepository
    ) {
    }

    /**
     * 创建培训机构
     */
    public function createInstitution(array $institutionData): Institution
    {
        // 验证数据
        $this->validateInstitutionData($institutionData);

        // 检查机构代码唯一性
        if ($this->institutionRepository->isInstitutionCodeExists($institutionData['institutionCode'])) {
            throw new \InvalidArgumentException('机构代码已存在');
        }

        // 检查注册号唯一性
        if ($this->institutionRepository->isRegistrationNumberExists($institutionData['registrationNumber'])) {
            throw new \InvalidArgumentException('注册号已存在');
        }

        $institution = Institution::create(
            $institutionData['institutionName'],
            $institutionData['institutionCode'],
            $institutionData['institutionType'],
            $institutionData['legalPerson'],
            $institutionData['contactPerson'],
            $institutionData['contactPhone'],
            $institutionData['contactEmail'],
            $institutionData['address'],
            $institutionData['businessScope'],
            $institutionData['establishDate'],
            $institutionData['registrationNumber'],
            $institutionData['institutionStatus'] ?? '待审核',
            $institutionData['organizationStructure'] ?? []
        );

        $this->entityManager->persist($institution);
        $this->entityManager->flush();

        return $institution;
    }

    /**
     * 更新培训机构
     */
    public function updateInstitution(string $institutionId, array $institutionData): Institution
    {
        $institution = $this->getInstitutionById($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        // 记录变更前的数据
        $beforeData = [
            'institutionName' => $institution->getInstitutionName(),
            'institutionCode' => $institution->getInstitutionCode(),
            'institutionType' => $institution->getInstitutionType(),
            'legalPerson' => $institution->getLegalPerson(),
            'contactPerson' => $institution->getContactPerson(),
            'contactPhone' => $institution->getContactPhone(),
            'contactEmail' => $institution->getContactEmail(),
            'address' => $institution->getAddress(),
            'businessScope' => $institution->getBusinessScope(),
        ];

        // 更新字段
        if (isset($institutionData['institutionName'])) {
            $institution->setInstitutionName($institutionData['institutionName']);
        }
        if (isset($institutionData['institutionCode'])) {
            if ($this->institutionRepository->isInstitutionCodeExists($institutionData['institutionCode'], $institutionId)) {
                throw new \InvalidArgumentException('机构代码已存在');
            }
            $institution->setInstitutionCode($institutionData['institutionCode']);
        }
        if (isset($institutionData['institutionType'])) {
            $institution->setInstitutionType($institutionData['institutionType']);
        }
        if (isset($institutionData['legalPerson'])) {
            $institution->setLegalPerson($institutionData['legalPerson']);
        }
        if (isset($institutionData['contactPerson'])) {
            $institution->setContactPerson($institutionData['contactPerson']);
        }
        if (isset($institutionData['contactPhone'])) {
            $institution->setContactPhone($institutionData['contactPhone']);
        }
        if (isset($institutionData['contactEmail'])) {
            $institution->setContactEmail($institutionData['contactEmail']);
        }
        if (isset($institutionData['address'])) {
            $institution->setAddress($institutionData['address']);
        }
        if (isset($institutionData['businessScope'])) {
            $institution->setBusinessScope($institutionData['businessScope']);
        }

        // 记录变更
        $afterData = [
            'institutionName' => $institution->getInstitutionName(),
            'institutionCode' => $institution->getInstitutionCode(),
            'institutionType' => $institution->getInstitutionType(),
            'legalPerson' => $institution->getLegalPerson(),
            'contactPerson' => $institution->getContactPerson(),
            'contactPhone' => $institution->getContactPhone(),
            'contactEmail' => $institution->getContactEmail(),
            'address' => $institution->getAddress(),
            'businessScope' => $institution->getBusinessScope(),
        ];

        // 创建变更记录
        if ($beforeData !== $afterData) {
            $changeRecord = InstitutionChangeRecord::create(
                $institution,
                '基本信息变更',
                ['summary' => '更新机构基本信息'],
                $beforeData,
                $afterData,
                $institutionData['changeReason'] ?? '系统更新',
                $institutionData['operator'] ?? 'system'
            );
            $this->entityManager->persist($changeRecord);
        }

        $this->entityManager->flush();

        return $institution;
    }

    /**
     * 根据ID获取机构
     */
    public function getInstitutionById(string $institutionId): ?Institution
    {
        return $this->institutionRepository->find($institutionId);
    }

    /**
     * 根据状态获取机构列表
     */
    public function getInstitutionsByStatus(string $status): array
    {
        return $this->institutionRepository->findByStatus($status);
    }

    /**
     * 验证机构数据
     */
    public function validateInstitutionData(array $institutionData): array
    {
        $errors = [];

        // 必填字段验证
        $requiredFields = [
            'institutionName' => '机构名称',
            'institutionCode' => '机构代码',
            'institutionType' => '机构类型',
            'legalPerson' => '法人代表',
            'contactPerson' => '联系人',
            'contactPhone' => '联系电话',
            'contactEmail' => '联系邮箱',
            'address' => '机构地址',
            'businessScope' => '经营范围',
            'establishDate' => '成立日期',
            'registrationNumber' => '注册号',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($institutionData[$field])) {
                $errors[] = "{$label}不能为空";
            }
        }

        // 邮箱格式验证
        if (!empty($institutionData['contactEmail']) && !filter_var($institutionData['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = '联系邮箱格式不正确';
        }

        // 电话格式验证
        if (!empty($institutionData['contactPhone']) && !preg_match('/^1[3-9]\d{9}$/', $institutionData['contactPhone'])) {
            $errors[] = '联系电话格式不正确';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode('；', $errors));
        }

        return $errors;
    }

    /**
     * 变更机构状态
     */
    public function changeInstitutionStatus(string $institutionId, string $status, string $reason): Institution
    {
        $institution = $this->getInstitutionById($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $oldStatus = $institution->getInstitutionStatus();
        $institution->setInstitutionStatus($status);

        // 创建状态变更记录
        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '状态变更',
            ['summary' => "状态从 {$oldStatus} 变更为 {$status}"],
            ['institutionStatus' => $oldStatus],
            ['institutionStatus' => $status],
            $reason,
            'system'
        );

        $this->entityManager->persist($changeRecord);
        $this->entityManager->flush();

        return $institution;
    }

    /**
     * 检查机构AQ8011-2023合规性
     */
    public function checkInstitutionCompliance(string $institutionId): array
    {
        $institution = $this->getInstitutionById($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $issues = [];

        // 基本信息合规检查
        $basicIssues = $institution->checkAQ8011Compliance();
        $issues = array_merge($issues, $basicIssues);

        // 资质合规检查
        $validQualifications = $institution->getValidQualifications();
        if ($validQualifications->isEmpty()) {
            $issues[] = '机构缺少有效的培训资质';
        }

        // 设施合规检查
        $facilities = $institution->getFacilities();
        if ($facilities->isEmpty()) {
            $issues[] = '机构缺少培训设施信息';
        } else {
            foreach ($facilities as $facility) {
                $facilityIssues = $facility->checkAQ8011Compliance();
                $issues = array_merge($issues, $facilityIssues);
            }
        }

        return $issues;
    }

    /**
     * 获取机构统计信息
     */
    public function getInstitutionStatistics(): array
    {
        return $this->institutionRepository->getStatistics();
    }

    /**
     * 搜索机构
     */
    public function searchInstitutions(array $criteria): array
    {
        if (!empty($criteria['name'])) {
            return $this->institutionRepository->searchByName($criteria['name']);
        }

        if (!empty($criteria['address'])) {
            return $this->institutionRepository->searchByAddress($criteria['address']);
        }

        return [];
    }

    /**
     * 分页获取机构列表
     */
    public function getInstitutionsPaginated(int $page = 1, int $limit = 20, array $criteria = []): array
    {
        return $this->institutionRepository->findPaginated($page, $limit, $criteria);
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