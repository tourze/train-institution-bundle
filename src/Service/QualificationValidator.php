<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\DuplicateCertificateNumberException;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;
use Tourze\TrainInstitutionBundle\Contract\InstitutionQualificationRepositoryInterface;

/**
 * 资质验证服务
 */
final class QualificationValidator
{
    public function __construct(
        private readonly InstitutionQualificationRepositoryInterface $qualificationRepository,
    ) {
    }

    /**
     * 验证资质数据
     * @param array<string, mixed> $qualificationData
     */
    public function validateQualificationData(array $qualificationData): void
    {
        $errors = [];
        $errors = array_merge($errors, $this->validateQualificationRequiredFields($qualificationData));
        $errors = array_merge($errors, $this->validateQualificationDateFormats($qualificationData));

        if ([] !== $errors) {
            throw new InvalidQualificationDataException(implode('；', $errors));
        }
    }

    /**
     * 验证日期范围
     * @param array<string, mixed> $qualificationData
     */
    public function validateDateRange(array $qualificationData): void
    {
        if ($qualificationData['validFrom'] >= $qualificationData['validTo']) {
            throw new InvalidQualificationDataException('有效期开始日期必须早于结束日期');
        }
    }

    /**
     * 验证更新后的资质
     */
    public function validateUpdatedQualification(InstitutionQualification $qualification): void
    {
        if ($qualification->getValidFrom() >= $qualification->getValidTo()) {
            throw new InvalidQualificationDataException('有效期开始日期必须早于结束日期');
        }
    }

    /**
     * 验证续期数据
     * @param array<string, mixed> $renewalData
     */
    public function validateRenewalData(array $renewalData, string $qualificationId): void
    {
        $this->validateNewValidToDate($renewalData);
        $this->validateNewCertificateNumber($renewalData, $qualificationId);
    }

    /**
     * 验证资质必填字段
     * @param array<string, mixed> $qualificationData
     * @return array<string>
     */
    private function validateQualificationRequiredFields(array $qualificationData): array
    {
        $errors = [];
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
            if (!isset($qualificationData[$field]) || '' === $qualificationData[$field] || [] === $qualificationData[$field]) {
                $errors[] = "{$label}不能为空";
            }
        }

        return $errors;
    }

    /**
     * 验证资质日期格式
     * @param array<string, mixed> $qualificationData
     * @return array<string>
     */
    private function validateQualificationDateFormats(array $qualificationData): array
    {
        $errors = [];
        $requiredFields = [
            'issueDate' => '发证日期',
            'validFrom' => '有效期开始日期',
            'validTo' => '有效期结束日期',
        ];

        foreach (['issueDate', 'validFrom', 'validTo'] as $field) {
            if (isset($qualificationData[$field]) && !($qualificationData[$field] instanceof \DateTimeImmutable)) {
                $errors[] = "{$requiredFields[$field]}必须是有效的日期格式";
            }
        }

        return $errors;
    }

    /**
     * 验证新的有效期结束日期
     * @param array<string, mixed> $renewalData
     */
    private function validateNewValidToDate(array $renewalData): void
    {
        if (!isset($renewalData['newValidTo'])) {
            throw new InvalidQualificationDataException('新的有效期结束日期不能为空');
        }

        $newValidTo = $renewalData['newValidTo'];
        if ($newValidTo <= new \DateTimeImmutable()) {
            throw new InvalidQualificationDataException('新的有效期结束日期必须是未来日期');
        }
    }

    /**
     * 验证新的证书编号
     * @param array<string, mixed> $renewalData
     */
    private function validateNewCertificateNumber(array $renewalData, string $qualificationId): void
    {
        $newCertificateNumber = $renewalData['newCertificateNumber'] ?? null;
        if (is_string($newCertificateNumber) && $this->qualificationRepository->isCertificateNumberExists($newCertificateNumber, $qualificationId)) {
            throw new DuplicateCertificateNumberException($newCertificateNumber);
        }
    }
}
