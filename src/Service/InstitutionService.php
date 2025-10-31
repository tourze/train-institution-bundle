<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\DuplicateInstitutionCodeException;
use Tourze\TrainInstitutionBundle\Exception\DuplicateRegistrationNumberException;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InvalidInstitutionDataException;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 培训机构服务
 *
 * 提供培训机构的核心业务逻辑，包括创建、更新、状态管理等功能
 */
#[Autoconfigure(public: true)]
class InstitutionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
    ) {
    }

    /**
     * 创建培训机构
     * @param array<string, mixed> $institutionData
     */
    public function createInstitution(array $institutionData): Institution
    {
        $this->validateInstitutionData($institutionData);
        $this->validateInstitutionUniqueness($institutionData);

        $institution = $this->createInstitutionEntity($institutionData);
        $this->persistInstitution($institution);

        return $institution;
    }

    /**
     * 更新培训机构
     * @param array<string, mixed> $institutionData
     */
    public function updateInstitution(string $institutionId, array $institutionData): Institution
    {
        $institution = $this->validateUpdateRequest($institutionId);
        $this->processInstitutionUpdate($institution, $institutionData, $institutionId);

        return $institution;
    }

    /**
     * 根据ID获取机构
     */
    public function getInstitutionById(string $institutionId): ?Institution
    {
        $institution = $this->institutionRepository->find($institutionId);
        assert(null === $institution || $institution instanceof Institution);

        return $institution;
    }

    /**
     * 根据状态获取机构列表
     * @return array<Institution>
     */
    public function getInstitutionsByStatus(string $status): array
    {
        return $this->institutionRepository->findByStatus($status);
    }

    /**
     * 验证机构数据
     * @param array<string, mixed> $institutionData
     * @return array<string>
     */
    public function validateInstitutionData(array $institutionData): array
    {
        $errors = array_merge(
            $this->validateRequiredFields($institutionData),
            $this->validateDataFormats($institutionData)
        );

        if ([] !== $errors) {
            throw new InvalidInstitutionDataException(implode('；', $errors));
        }

        return $errors;
    }

    /**
     * 变更机构状态
     */
    public function changeInstitutionStatus(string $institutionId, string $status, string $reason): Institution
    {
        $institution = $this->getInstitutionById($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
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
     * @return array<string>
     */
    public function checkInstitutionCompliance(string $institutionId): array
    {
        $institution = $this->getInstitutionById($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }

        return $this->performComplianceChecks($institution);
    }

    /**
     * 获取机构统计信息
     */
    /**
     * @return array<string, mixed>
     */
    public function getInstitutionStatistics(): array
    {
        return $this->institutionRepository->getStatistics();
    }

    /**
     * 搜索机构
     */
    /**
     * @param array<string, mixed> $criteria
     * @return array<Institution>
     */
    public function searchInstitutions(array $criteria): array
    {
        return $this->executeSearch($criteria);
    }

    /**
     * 获取机构的有效资质证书
     * @return Collection<int, InstitutionQualification>
     */
    public function getValidQualifications(Institution $institution): Collection
    {
        return $institution->getQualifications()->filter(function (InstitutionQualification $qualification) {
            return '有效' === $qualification->getQualificationStatus()
                && $qualification->getValidTo() > new \DateTimeImmutable();
        });
    }

    /**
     * 获取机构即将到期的资质证书（30天内）
     * @return Collection<int, InstitutionQualification>
     */
    public function getExpiringQualifications(Institution $institution): Collection
    {
        $thirtyDaysLater = new \DateTimeImmutable('+30 days');

        return $institution->getQualifications()->filter(function (InstitutionQualification $qualification) use ($thirtyDaysLater) {
            return '有效' === $qualification->getQualificationStatus()
                && $qualification->getValidTo() <= $thirtyDaysLater
                && $qualification->getValidTo() > new \DateTimeImmutable();
        });
    }

    /**
     * 分页获取机构列表
     */
    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function getInstitutionsPaginated(int $page = 1, int $limit = 20, array $criteria = []): array
    {
        return $this->institutionRepository->findPaginated($page, $limit, $criteria);
    }

    /**
     * 验证更新请求并获取机构实例
     */
    private function validateUpdateRequest(string $institutionId): Institution
    {
        $institution = $this->getInstitutionById($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }

        return $institution;
    }

    /**
     * 捕获机构当前数据状态
     */
    /**
     * @return array<string, mixed>
     */
    private function captureInstitutionData(Institution $institution): array
    {
        return [
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
    }

    /**
     * 更新机构字段信息
     */
    /**
     * @param array<string, mixed> $institutionData
     */
    private function updateInstitutionFields(Institution $institution, array $institutionData, string $institutionId): void
    {
        $this->updateBasicFields($institution, $institutionData);
        $this->updateInstitutionCodeIfProvided($institution, $institutionData, $institutionId);
        $this->updateContactFields($institution, $institutionData);
    }

    /**
     * 更新基本字段
     * @param array<string, mixed> $institutionData
     */
    private function updateBasicFields(Institution $institution, array $institutionData): void
    {
        $fieldMappings = [
            'institutionName' => 'setInstitutionName',
            'institutionType' => 'setInstitutionType',
            'legalPerson' => 'setLegalPerson',
            'address' => 'setAddress',
            'businessScope' => 'setBusinessScope',
        ];

        foreach ($fieldMappings as $field => $setter) {
            $this->updateStringFieldIfProvided($institution, $institutionData, $field, $setter);
        }
    }

    /**
     * 更新字符串字段（如果提供）
     * @param array<string, mixed> $data
     */
    private function updateStringFieldIfProvided(Institution $institution, array $data, string $field, string $setter): void
    {
        if (!isset($data[$field]) || !is_string($data[$field])) {
            return;
        }

        $this->applyFieldUpdate($institution, $setter, $data[$field]);
    }

    /**
     * 应用字段更新
     */
    private function applyFieldUpdate(Institution $institution, string $setter, string $value): void
    {
        match ($setter) {
            'setInstitutionName' => $institution->setInstitutionName($value),
            'setInstitutionType' => $institution->setInstitutionType($value),
            'setLegalPerson' => $institution->setLegalPerson($value),
            'setAddress' => $institution->setAddress($value),
            'setBusinessScope' => $institution->setBusinessScope($value),
            'setContactPerson' => $institution->setContactPerson($value),
            'setContactPhone' => $institution->setContactPhone($value),
            'setContactEmail' => $institution->setContactEmail($value),
            default => throw new \InvalidArgumentException("Unknown setter: {$setter}"),
        };
    }

    /**
     * 更新机构代码（如果提供）
     */
    /**
     * @param array<string, mixed> $institutionData
     */
    private function updateInstitutionCodeIfProvided(Institution $institution, array $institutionData, string $institutionId): void
    {
        if (isset($institutionData['institutionCode']) && is_string($institutionData['institutionCode'])) {
            $newCode = $institutionData['institutionCode'];
            if ($this->institutionRepository->isInstitutionCodeExists($newCode, $institutionId)) {
                throw new DuplicateInstitutionCodeException($newCode);
            }
            $institution->setInstitutionCode($newCode);
        }
    }

    /**
     * 更新联系人字段
     * @param array<string, mixed> $institutionData
     */
    private function updateContactFields(Institution $institution, array $institutionData): void
    {
        $contactFields = [
            'contactPerson' => 'setContactPerson',
            'contactPhone' => 'setContactPhone',
            'contactEmail' => 'setContactEmail',
        ];

        foreach ($contactFields as $field => $setter) {
            $this->updateStringFieldIfProvided($institution, $institutionData, $field, $setter);
        }
    }

    /**
     * 创建变更记录（如果需要）
     * @param array<string, mixed> $beforeData
     * @param array<string, mixed> $afterData
     * @param array<string, mixed> $institutionData
     */
    private function createChangeRecordIfNeeded(
        Institution $institution,
        array $beforeData,
        array $afterData,
        array $institutionData,
    ): void {
        if ($beforeData === $afterData) {
            return;
        }

        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '基本信息变更',
            ['summary' => '更新机构基本信息'],
            $beforeData,
            $afterData,
            $this->getChangeReason($institutionData),
            $this->getOperator($institutionData)
        );
        $this->entityManager->persist($changeRecord);
    }

    /**
     * 获取变更原因
     * @param array<string, mixed> $data
     */
    private function getChangeReason(array $data): string
    {
        $changeReason = $data['changeReason'] ?? null;

        return is_string($changeReason) ? $changeReason : '系统更新';
    }

    /**
     * 获取操作者
     * @param array<string, mixed> $data
     */
    private function getOperator(array $data): string
    {
        $operator = $data['operator'] ?? null;

        return is_string($operator) ? $operator : 'system';
    }

    /**
     * 持久化数据变更
     */
    private function persistChanges(): void
    {
        $this->entityManager->flush();
    }

    /**
     * 验证机构唯一性约束
     * @param array<string, mixed> $institutionData
     */
    private function validateInstitutionUniqueness(array $institutionData): void
    {
        // 检查机构代码唯一性
        $institutionCode = $this->extractStringFromData($institutionData, 'institutionCode');
        if ($this->institutionRepository->isInstitutionCodeExists($institutionCode)) {
            throw new DuplicateInstitutionCodeException($institutionCode);
        }

        // 检查注册号唯一性
        $registrationNumber = $this->extractStringFromData($institutionData, 'registrationNumber');
        if ($this->institutionRepository->isRegistrationNumberExists($registrationNumber)) {
            throw new DuplicateRegistrationNumberException($registrationNumber);
        }
    }

    /**
     * 创建机构实体对象
     * @param array<string, mixed> $institutionData
     */
    private function createInstitutionEntity(array $institutionData): Institution
    {
        return Institution::create(
            $this->extractStringFromData($institutionData, 'institutionName'),
            $this->extractStringFromData($institutionData, 'institutionCode'),
            $this->extractStringFromData($institutionData, 'institutionType'),
            $this->extractStringFromData($institutionData, 'legalPerson'),
            $this->extractStringFromData($institutionData, 'contactPerson'),
            $this->extractStringFromData($institutionData, 'contactPhone'),
            $this->extractStringFromData($institutionData, 'contactEmail'),
            $this->extractStringFromData($institutionData, 'address'),
            $this->extractStringFromData($institutionData, 'businessScope'),
            $this->parseEstablishDate($institutionData['establishDate']),
            $this->extractStringFromData($institutionData, 'registrationNumber'),
            $this->getInstitutionStatus($institutionData),
            $this->getOrganizationStructure($institutionData)
        );
    }

    /**
     * 解析成立日期
     * @param mixed $date
     */
    private function parseEstablishDate($date): \DateTimeImmutable
    {
        if ($date instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($date);
        }

        if (is_string($date)) {
            return new \DateTimeImmutable($date);
        }

        // 如果是标量，尝试转换为字符串
        if (is_scalar($date)) {
            return new \DateTimeImmutable((string) $date);
        }

        // 降级方案：返回当前时间
        return new \DateTimeImmutable();
    }

    /**
     * 获取机构状态
     * @param array<string, mixed> $data
     */
    private function getInstitutionStatus(array $data): string
    {
        $status = $data['institutionStatus'] ?? null;

        return is_string($status) ? $status : '待审核';
    }

    /**
     * 获取组织架构
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function getOrganizationStructure(array $data): array
    {
        if (!isset($data['organizationStructure'])) {
            return [];
        }

        $structure = $data['organizationStructure'];
        if (!is_array($structure)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $structure;
    }

    /**
     * 持久化机构实体
     */
    private function persistInstitution(Institution $institution): void
    {
        $this->entityManager->persist($institution);
        $this->entityManager->flush();
    }

    /**
     * 执行搜索操作
     * @param array<string, mixed> $criteria
     * @return array<Institution>
     */
    private function executeSearch(array $criteria): array
    {
        $searchMethods = [
            fn () => $this->searchByName($criteria),
            fn () => $this->searchByAddress($criteria),
            fn () => $this->searchByCode($criteria),
        ];

        foreach ($searchMethods as $searchMethod) {
            $result = $searchMethod();
            if ([] !== $result) {
                return $result;
            }
        }

        return [];
    }

    /**
     * 按名称搜索
     * @param array<string, mixed> $criteria
     * @return array<Institution>
     */
    private function searchByName(array $criteria): array
    {
        if (isset($criteria['name']) && is_string($criteria['name']) && '' !== $criteria['name']) {
            return $this->institutionRepository->searchByName($criteria['name']);
        }

        return [];
    }

    /**
     * 按地址搜索
     * @param array<string, mixed> $criteria
     * @return array<Institution>
     */
    private function searchByAddress(array $criteria): array
    {
        if (isset($criteria['address']) && is_string($criteria['address']) && '' !== $criteria['address']) {
            return $this->institutionRepository->searchByAddress($criteria['address']);
        }

        return [];
    }

    /**
     * 按代码搜索
     * @param array<string, mixed> $criteria
     * @return array<Institution>
     */
    private function searchByCode(array $criteria): array
    {
        if (isset($criteria['code']) && is_string($criteria['code']) && '' !== $criteria['code']) {
            $institution = $this->institutionRepository->findByInstitutionCode($criteria['code']);

            return null !== $institution ? [$institution] : [];
        }

        return [];
    }

    /**
     * 验证必填字段
     * @param array<string, mixed> $institutionData
     * @return array<string>
     */
    private function validateRequiredFields(array $institutionData): array
    {
        $requiredFields = $this->getRequiredFieldsMapping();

        return array_map(
            fn (string $label) => "{$label}不能为空",
            array_filter(
                $requiredFields,
                fn (string $label, string $field) => $this->isFieldEmpty($institutionData, $field),
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    /**
     * 获取必填字段映射
     * @return array<string, string>
     */
    private function getRequiredFieldsMapping(): array
    {
        return [
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
    }

    /**
     * 检查字段是否为空
     * @param array<string, mixed> $data
     */
    private function isFieldEmpty(array $data, string $field): bool
    {
        return !isset($data[$field]) || '' === $data[$field] || [] === $data[$field];
    }

    /**
     * 验证数据格式
     * @param array<string, mixed> $institutionData
     * @return array<string>
     */
    private function validateDataFormats(array $institutionData): array
    {
        return array_merge(
            $this->validateEmailFormat($institutionData),
            $this->validatePhoneFormat($institutionData)
        );
    }

    /**
     * 验证邮箱格式
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function validateEmailFormat(array $data): array
    {
        if (!isset($data['contactEmail']) || '' === $data['contactEmail']) {
            return [];
        }

        return false === filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)
            ? ['联系邮箱格式不正确']
            : [];
    }

    /**
     * 验证电话格式
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function validatePhoneFormat(array $data): array
    {
        $phone = $data['contactPhone'] ?? null;
        if (!is_string($phone) || '' === $phone) {
            return [];
        }

        return 0 === preg_match('/^1[3-9]\d{9}$/', $phone)
            ? ['联系电话格式不正确']
            : [];
    }

    /**
     * 执行合规性检查
     * @return array<string>
     */
    private function performComplianceChecks(Institution $institution): array
    {
        return array_merge(
            $this->checkBasicCompliance($institution),
            $this->checkQualificationCompliance($institution),
            $this->checkFacilityCompliance($institution)
        );
    }

    /**
     * 检查基本信息合规性
     * @return array<string>
     */
    private function checkBasicCompliance(Institution $institution): array
    {
        $checks = [
            [$institution->getInstitutionName(), '机构名称不能为空'],
            [$institution->getLegalPerson(), '法人代表不能为空'],
            [$institution->getContactPhone(), '联系电话不能为空'],
        ];

        $issues = array_values(array_filter(array_map(
            fn ($check) => '' === $check[0] ? $check[1] : null,
            $checks
        ), fn ($item) => null !== $item));

        if ('正常运营' !== $institution->getInstitutionStatus()) {
            $issues[] = '机构状态必须为正常运营';
        }

        return $issues;
    }

    /**
     * 检查资质合规性
     * @return array<string>
     */
    private function checkQualificationCompliance(Institution $institution): array
    {
        $validQualifications = $this->getValidQualifications($institution);
        if ($validQualifications->isEmpty()) {
            return ['机构缺少有效的培训资质'];
        }

        return [];
    }

    /**
     * 检查设施合规性
     * @return array<string>
     */
    private function checkFacilityCompliance(Institution $institution): array
    {
        $facilities = $institution->getFacilities();
        if ($facilities->isEmpty()) {
            return ['机构缺少培训设施信息'];
        }

        // 注意：设施合规性检查逻辑已移动到FacilityService
        // 这里只进行基本检查，详细检查应调用FacilityService
        return [];
    }

    /**
     * 处理机构更新流程
     * @param array<string, mixed> $institutionData
     */
    private function processInstitutionUpdate(Institution $institution, array $institutionData, string $institutionId): void
    {
        $beforeData = $this->captureInstitutionData($institution);
        $this->updateInstitutionFields($institution, $institutionData, $institutionId);
        $afterData = $this->captureInstitutionData($institution);
        $this->createChangeRecordIfNeeded($institution, $beforeData, $afterData, $institutionData);
        $this->persistChanges();
    }

    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     */
    private function extractStringFromData(array $data, string $key): string
    {
        if (!isset($data[$key])) {
            return '';
        }

        $value = $data[$key];
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
