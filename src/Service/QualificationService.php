<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 机构资质服务
 * 
 * 提供培训机构资质的核心业务逻辑，包括资质添加、更新、到期检查、续期等功能
 */
class QualificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
        private readonly InstitutionQualificationRepository $qualificationRepository
    ) {
    }

    /**
     * 添加机构资质
     */
    public function addQualification(string $institutionId, array $qualificationData): InstitutionQualification
    {
        // 验证数据
        $this->validateQualificationData($qualificationData);

        // 获取机构
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        // 检查证书编号唯一性
        if ($this->qualificationRepository->isCertificateNumberExists($qualificationData['certificateNumber'])) {
            throw new \InvalidArgumentException('证书编号已存在');
        }

        // 验证有效期
        if ($qualificationData['validFrom'] >= $qualificationData['validTo']) {
            throw new \InvalidArgumentException('有效期开始日期必须早于结束日期');
        }

        $qualification = new InstitutionQualification(
            $qualificationData['id'],
            $institution,
            $qualificationData['qualificationType'],
            $qualificationData['qualificationName'],
            $qualificationData['certificateNumber'],
            $qualificationData['issuingAuthority'],
            $qualificationData['issueDate'],
            $qualificationData['validFrom'],
            $qualificationData['validTo'],
            $qualificationData['qualificationScope'] ?? [],
            $qualificationData['qualificationStatus'] ?? '有效',
            $qualificationData['attachments'] ?? []
        );

        $this->entityManager->persist($qualification);
        $this->entityManager->flush();

        return $qualification;
    }

    /**
     * 更新机构资质
     */
    public function updateQualification(string $qualificationId, array $qualificationData): InstitutionQualification
    {
        $qualification = $this->qualificationRepository->find($qualificationId);
        if (!$qualification) {
            throw new \InvalidArgumentException('资质不存在');
        }

        // 更新字段
        if (isset($qualificationData['qualificationType'])) {
            $qualification->setQualificationType($qualificationData['qualificationType']);
        }
        if (isset($qualificationData['qualificationName'])) {
            $qualification->setQualificationName($qualificationData['qualificationName']);
        }
        if (isset($qualificationData['certificateNumber'])) {
            if ($this->qualificationRepository->isCertificateNumberExists($qualificationData['certificateNumber'], $qualificationId)) {
                throw new \InvalidArgumentException('证书编号已存在');
            }
            $qualification->setCertificateNumber($qualificationData['certificateNumber']);
        }
        if (isset($qualificationData['issuingAuthority'])) {
            $qualification->setIssuingAuthority($qualificationData['issuingAuthority']);
        }
        if (isset($qualificationData['issueDate'])) {
            $qualification->setIssueDate($qualificationData['issueDate']);
        }
        if (isset($qualificationData['validFrom'])) {
            $qualification->setValidFrom($qualificationData['validFrom']);
        }
        if (isset($qualificationData['validTo'])) {
            $qualification->setValidTo($qualificationData['validTo']);
        }
        if (isset($qualificationData['qualificationScope'])) {
            $qualification->setQualificationScope($qualificationData['qualificationScope']);
        }
        if (isset($qualificationData['qualificationStatus'])) {
            $qualification->setQualificationStatus($qualificationData['qualificationStatus']);
        }
        if (isset($qualificationData['attachments'])) {
            $qualification->setAttachments($qualificationData['attachments']);
        }

        // 验证有效期
        if ($qualification->getValidFrom() >= $qualification->getValidTo()) {
            throw new \InvalidArgumentException('有效期开始日期必须早于结束日期');
        }

        $this->entityManager->flush();

        return $qualification;
    }

    /**
     * 检查机构资质到期情况
     */
    public function checkQualificationExpiry(string $institutionId): array
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (!$institution) {
            throw new \InvalidArgumentException('机构不存在');
        }

        $qualifications = $this->qualificationRepository->findByInstitution($institution);
        $expiryInfo = [];

        foreach ($qualifications as $qualification) {
            $remainingDays = $qualification->getRemainingDays();
            $status = 'normal';

            if ($remainingDays <= 0) {
                $status = 'expired';
            } elseif ($remainingDays <= 30) {
                $status = 'expiring_soon';
            } elseif ($remainingDays <= 60) {
                $status = 'warning';
            }

            $expiryInfo[] = [
                'qualification' => $qualification,
                'remaining_days' => $remainingDays,
                'status' => $status,
                'is_valid' => $qualification->isValid(),
            ];
        }

        return $expiryInfo;
    }

    /**
     * 续期资质
     */
    public function renewQualification(string $qualificationId, array $renewalData): InstitutionQualification
    {
        $qualification = $this->qualificationRepository->find($qualificationId);
        if (!$qualification) {
            throw new \InvalidArgumentException('资质不存在');
        }

        // 验证续期数据
        if (empty($renewalData['newValidTo'])) {
            throw new \InvalidArgumentException('新的有效期结束日期不能为空');
        }

        $newValidTo = $renewalData['newValidTo'];
        if ($newValidTo <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('新的有效期结束日期必须是未来日期');
        }

        // 检查新证书编号唯一性（如果提供）
        $newCertificateNumber = $renewalData['newCertificateNumber'] ?? null;
        if ($newCertificateNumber && $this->qualificationRepository->isCertificateNumberExists($newCertificateNumber, $qualificationId)) {
            throw new \InvalidArgumentException('新证书编号已存在');
        }

        $qualification->renew($newValidTo, $newCertificateNumber);

        // 更新其他信息
        if (isset($renewalData['issuingAuthority'])) {
            $qualification->setIssuingAuthority($renewalData['issuingAuthority']);
        }
        if (isset($renewalData['issueDate'])) {
            $qualification->setIssueDate($renewalData['issueDate']);
        }
        if (isset($renewalData['qualificationScope'])) {
            $qualification->setQualificationScope($renewalData['qualificationScope']);
        }
        if (isset($renewalData['attachments'])) {
            $qualification->setAttachments($renewalData['attachments']);
        }

        $this->entityManager->flush();

        return $qualification;
    }

    /**
     * 获取即将到期的资质
     */
    public function getExpiringQualifications(int $days = 30): array
    {
        return $this->qualificationRepository->findExpiringSoon($days);
    }

    /**
     * 验证资质范围
     */
    public function validateQualificationScope(string $qualificationId, array $scope): bool
    {
        $qualification = $this->qualificationRepository->find($qualificationId);
        if (!$qualification) {
            throw new \InvalidArgumentException('资质不存在');
        }

        // 检查资质是否有效
        if (!$qualification->isValid()) {
            return false;
        }

        // 检查每个培训类型是否在资质范围内
        foreach ($scope as $trainingType) {
            if (!$qualification->coversTrainingType($trainingType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取需要续期提醒的资质
     */
    public function getQualificationsNeedingRenewalReminder(int $reminderDays = 60): array
    {
        return $this->qualificationRepository->findNeedingRenewalReminder($reminderDays);
    }

    /**
     * 获取资质统计信息
     */
    public function getQualificationStatistics(): array
    {
        return $this->qualificationRepository->getStatistics();
    }

    /**
     * 分页获取资质列表
     */
    public function getQualificationsPaginated(int $page = 1, int $limit = 20, array $criteria = []): array
    {
        return $this->qualificationRepository->findPaginated($page, $limit, $criteria);
    }

    /**
     * 撤销资质
     */
    public function revokeQualification(string $qualificationId, string $reason): InstitutionQualification
    {
        $qualification = $this->qualificationRepository->find($qualificationId);
        if (!$qualification) {
            throw new \InvalidArgumentException('资质不存在');
        }

        $qualification->setQualificationStatus('已撤销');
        $this->entityManager->flush();

        return $qualification;
    }

    /**
     * 暂停资质
     */
    public function suspendQualification(string $qualificationId, string $reason): InstitutionQualification
    {
        $qualification = $this->qualificationRepository->find($qualificationId);
        if (!$qualification) {
            throw new \InvalidArgumentException('资质不存在');
        }

        $qualification->setQualificationStatus('暂停');
        $this->entityManager->flush();

        return $qualification;
    }

    /**
     * 恢复资质
     */
    public function restoreQualification(string $qualificationId): InstitutionQualification
    {
        $qualification = $this->qualificationRepository->find($qualificationId);
        if (!$qualification) {
            throw new \InvalidArgumentException('资质不存在');
        }

        // 检查是否在有效期内
        if ($qualification->getValidTo() <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('资质已过期，无法恢复');
        }

        $qualification->setQualificationStatus('有效');
        $this->entityManager->flush();

        return $qualification;
    }

    /**
     * 验证资质数据
     */
    private function validateQualificationData(array $qualificationData): void
    {
        $errors = [];

        // 必填字段验证
        $requiredFields = [
            'qualificationType' => '资质类型',
            'qualificationName' => '资质名称',
            'certificateNumber' => '证书编号',
            'issuingAuthority' => '发证机关',
            'issueDate' => '发证日期',
            'validFrom' => '有效期开始日期',
            'validTo' => '有效期结束日期',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($qualificationData[$field])) {
                $errors[] = "{$label}不能为空";
            }
        }

        // 日期格式验证
        $dateFields = ['issueDate', 'validFrom', 'validTo'];
        foreach ($dateFields as $field) {
            if (!empty($qualificationData[$field]) && !($qualificationData[$field] instanceof \DateTimeImmutable)) {
                $errors[] = "{$requiredFields[$field]}必须是有效的日期格式";
            }
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