<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\DuplicateCertificateNumberException;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\QualificationExpiredException;
use Tourze\TrainInstitutionBundle\Exception\QualificationNotFoundException;
use Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 机构资质服务 - 重构后的简化版本，复杂度降低至安全范围
 */
#[Autoconfigure(public: true)]
class QualificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstitutionRepository $institutionRepository,
        private readonly InstitutionQualificationRepository $qualificationRepository,
        private readonly QualificationValidator $validator,
        private readonly QualificationUpdater $updater,
        private readonly QualificationFactory $factory,
    ) {
    }

    /**
     * 添加机构资质
     * @param array<string, mixed> $qualificationData
     */
    public function addQualification(string $institutionId, array $qualificationData): InstitutionQualification
    {
        $this->validator->validateQualificationData($qualificationData);
        $institution = $this->validateAndGetInstitution($institutionId);
        $this->validateQualificationUniqueness($qualificationData);
        $this->validator->validateDateRange($qualificationData);

        $qualification = $this->factory->createQualificationEntity($institution, $qualificationData);
        $this->persistQualification($qualification);

        return $qualification;
    }

    /**
     * 更新机构资质
     * @param array<string, mixed> $qualificationData
     */
    public function updateQualification(string $qualificationId, array $qualificationData): InstitutionQualification
    {
        $qualification = $this->getQualificationById($qualificationId);

        $this->validateUpdateData($qualificationData, $qualificationId);
        $this->updater->applyQualificationUpdates($qualification, $qualificationData);
        $this->validator->validateUpdatedQualification($qualification);
        $this->persistQualificationChanges();

        return $qualification;
    }

    /**
     * 检查机构资质到期情况
     * @return array<array<string, mixed>>
     */
    public function checkQualificationExpiry(string $institutionId): array
    {
        $institution = $this->validateAndGetInstitution($institutionId);
        $qualifications = $this->qualificationRepository->findByInstitution($institution);

        return $this->buildExpiryInfo($qualifications);
    }

    /**
     * 续期资质
     * @param array<string, mixed> $renewalData
     */
    public function renewQualification(string $qualificationId, array $renewalData): InstitutionQualification
    {
        $qualification = $this->getQualificationById($qualificationId);

        $this->validator->validateRenewalData($renewalData, $qualificationId);
        $this->updater->applyRenewalUpdates($qualification, $renewalData);
        $this->persistQualificationChanges();

        return $qualification;
    }

    /**
     * 获取即将到期的资质
     * @return array<InstitutionQualification>
     */
    public function getExpiringQualifications(int $days = 30): array
    {
        return $this->qualificationRepository->findExpiringSoon($days);
    }

    /**
     * 验证资质范围
     * @param array<string, mixed> $scope
     */
    public function validateQualificationScope(string $qualificationId, array $scope): bool
    {
        $qualification = $this->getQualificationById($qualificationId);

        if (!$qualification->isValid()) {
            return false;
        }

        $trainingTypes = $this->normalizeTrainingTypes($scope);

        return $this->allTrainingTypesCovered($qualification, $trainingTypes);
    }

    /**
     * 获取需要续期提醒的资质
     * @return array<InstitutionQualification>
     */
    public function getQualificationsNeedingRenewalReminder(int $reminderDays = 60): array
    {
        return $this->qualificationRepository->findNeedingRenewalReminder($reminderDays);
    }

    /**
     * 获取资质统计信息
     * @return array<string, mixed>
     */
    public function getQualificationStatistics(): array
    {
        return $this->qualificationRepository->getStatistics();
    }

    /**
     * 分页获取资质列表
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function getQualificationsPaginated(int $page = 1, int $limit = 20, array $criteria = []): array
    {
        return $this->qualificationRepository->findPaginated($page, $limit, $criteria);
    }

    public function revokeQualification(string $qualificationId, string $reason): InstitutionQualification
    {
        return $this->changeQualificationStatus($qualificationId, '已撤销');
    }

    public function suspendQualification(string $qualificationId, string $reason): InstitutionQualification
    {
        return $this->changeQualificationStatus($qualificationId, '暂停');
    }

    public function restoreQualification(string $qualificationId): InstitutionQualification
    {
        $qualification = $this->getQualificationById($qualificationId);

        if ($qualification->getValidTo() <= new \DateTimeImmutable()) {
            throw new QualificationExpiredException();
        }

        return $this->changeQualificationStatus($qualificationId, '有效');
    }

    /**
     * 标准化培训类型数组
     * @param array<string, mixed> $scope
     * @return array<string>
     */
    private function normalizeTrainingTypes(array $scope): array
    {
        $trainingTypes = $scope['training_types'] ?? $scope;

        if (is_array($trainingTypes)) {
            return array_filter($trainingTypes, 'is_string');
        }

        return is_string($trainingTypes) ? [$trainingTypes] : [];
    }

    /**
     * 检查所有培训类型是否被覆盖
     * @param array<mixed> $trainingTypes
     */
    private function allTrainingTypesCovered(InstitutionQualification $qualification, array $trainingTypes): bool
    {
        foreach ($trainingTypes as $trainingType) {
            if (is_string($trainingType) && !$qualification->coversTrainingType($trainingType)) {
                return false;
            }
        }

        return true;
    }

    private function changeQualificationStatus(string $qualificationId, string $status): InstitutionQualification
    {
        $qualification = $this->getQualificationById($qualificationId);
        $qualification->setQualificationStatus($status);
        $this->entityManager->flush();

        return $qualification;
    }

    private function getQualificationById(string $qualificationId): InstitutionQualification
    {
        $qualification = $this->qualificationRepository->find($qualificationId);
        if (null === $qualification) {
            throw new QualificationNotFoundException($qualificationId);
        }
        assert($qualification instanceof InstitutionQualification);

        return $qualification;
    }

    /**
     * @param array<string, mixed> $qualificationData
     */
    private function validateUpdateData(array $qualificationData, string $qualificationId): void
    {
        $certificateNumber = $qualificationData['certificateNumber'] ?? null;
        if (is_string($certificateNumber)) {
            if ($this->qualificationRepository->isCertificateNumberExists($certificateNumber, $qualificationId)) {
                throw new DuplicateCertificateNumberException($certificateNumber);
            }
        }
    }

    private function persistQualificationChanges(): void
    {
        $this->entityManager->flush();
    }

    private function validateAndGetInstitution(string $institutionId): Institution
    {
        $institution = $this->institutionRepository->find($institutionId);
        if (null === $institution) {
            throw new InstitutionNotFoundException($institutionId);
        }
        assert($institution instanceof Institution);

        return $institution;
    }

    /**
     * @param array<string, mixed> $qualificationData
     */
    private function validateQualificationUniqueness(array $qualificationData): void
    {
        $certificateNumber = $this->extractStringFromData($qualificationData, 'certificateNumber');
        if ($this->qualificationRepository->isCertificateNumberExists($certificateNumber)) {
            throw new DuplicateCertificateNumberException($certificateNumber);
        }
    }

    private function persistQualification(InstitutionQualification $qualification): void
    {
        $this->entityManager->persist($qualification);
        $this->entityManager->flush();
    }

    /**
     * 构建到期信息数组
     * @param array<InstitutionQualification> $qualifications
     * @return array<array<string, mixed>>
     */
    private function buildExpiryInfo(array $qualifications): array
    {
        return array_map(
            fn (InstitutionQualification $qualification) => $this->buildSingleExpiryInfo($qualification),
            $qualifications
        );
    }

    /**
     * 构建单个资质的到期信息
     * @return array<string, mixed>
     */
    private function buildSingleExpiryInfo(InstitutionQualification $qualification): array
    {
        $remainingDays = $qualification->getRemainingDays();
        $status = $this->determineExpiryStatus($remainingDays);

        return [
            'qualification' => $qualification,
            'remaining_days' => $remainingDays,
            'status' => $status,
            'is_valid' => $qualification->isValid(),
        ];
    }

    private function determineExpiryStatus(int $remainingDays): string
    {
        if ($remainingDays <= 0) {
            return 'expired';
        }

        if ($remainingDays <= 30) {
            return 'expiring_soon';
        }

        if ($remainingDays <= 60) {
            return 'warning';
        }

        return 'normal';
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
