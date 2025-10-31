<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;

/**
 * 资质更新服务
 */
final class QualificationUpdater
{
    /**
     * 应用资质更新
     * @param array<string, mixed> $qualificationData
     */
    public function applyQualificationUpdates(InstitutionQualification $qualification, array $qualificationData): void
    {
        $this->updateBasicFields($qualification, $qualificationData);
        $this->updateOptionalFields($qualification, $qualificationData);
    }

    /**
     * 应用续期更新
     * @param array<string, mixed> $renewalData
     */
    public function applyRenewalUpdates(InstitutionQualification $qualification, array $renewalData): void
    {
        $newValidTo = $renewalData['newValidTo'];
        $newCertificateNumber = $renewalData['newCertificateNumber'] ?? null;
        $certificateNumber = is_string($newCertificateNumber) ? $newCertificateNumber : null;

        if ($newValidTo instanceof \DateTimeImmutable) {
            $qualification->renew($newValidTo, $certificateNumber);
        } else {
            throw new InvalidQualificationDataException('新的有效期结束日期必须是有效的日期');
        }

        $this->updateRenewalOptionalFields($qualification, $renewalData);
    }

    /**
     * 更新基础字段
     * @param array<string, mixed> $qualificationData
     */
    private function updateBasicFields(InstitutionQualification $qualification, array $qualificationData): void
    {
        $basicFieldMappings = [
            'qualificationType' => 'setQualificationType',
            'qualificationName' => 'setQualificationName',
            'certificateNumber' => 'setCertificateNumber',
            'issuingAuthority' => 'setIssuingAuthority',
            'issueDate' => 'setIssueDate',
        ];

        foreach ($basicFieldMappings as $field => $setter) {
            $this->updateFieldIfSet($qualification, $qualificationData, $field, $setter);
        }
    }

    /**
     * 更新字段（如果设置）
     * @param array<string, mixed> $data
     */
    private function updateFieldIfSet(InstitutionQualification $qualification, array $data, string $field, string $setter): void
    {
        if (!isset($data[$field])) {
            return;
        }

        $value = $data[$field];

        match ($setter) {
            'setQualificationType' => $qualification->setQualificationType($this->ensureString($value)),
            'setQualificationName' => $qualification->setQualificationName($this->ensureString($value)),
            'setCertificateNumber' => $qualification->setCertificateNumber($this->ensureString($value)),
            'setIssuingAuthority' => $qualification->setIssuingAuthority($this->ensureString($value)),
            'setIssueDate' => $qualification->setIssueDate($this->ensureDate($value)),
            'setValidFrom' => $qualification->setValidFrom($this->ensureDate($value)),
            'setValidTo' => $qualification->setValidTo($this->ensureDate($value)),
            'setQualificationStatus' => $qualification->setQualificationStatus($this->ensureString($value)),
            'setAttachments' => $qualification->setAttachments($this->ensureArray($value)),
            default => throw new \InvalidArgumentException("Unknown setter: {$setter}"),
        };
    }

    /**
     * 更新可选字段
     * @param array<string, mixed> $qualificationData
     */
    private function updateOptionalFields(InstitutionQualification $qualification, array $qualificationData): void
    {
        $this->updateDateFields($qualification, $qualificationData);
        $this->updateQualificationScope($qualification, $qualificationData);
        $this->updateSimpleOptionalFields($qualification, $qualificationData);
    }

    /**
     * 更新日期字段
     * @param array<string, mixed> $data
     */
    private function updateDateFields(InstitutionQualification $qualification, array $data): void
    {
        $dateFields = ['validFrom' => 'setValidFrom', 'validTo' => 'setValidTo'];

        foreach ($dateFields as $field => $setter) {
            $this->updateFieldIfSet($qualification, $data, $field, $setter);
        }
    }

    /**
     * 更新资质范围
     * @param array<string, mixed> $data
     */
    private function updateQualificationScope(InstitutionQualification $qualification, array $data): void
    {
        if (!isset($data['qualificationScope']) || !is_array($data['qualificationScope'])) {
            return;
        }

        $cleanScope = [];
        foreach ($data['qualificationScope'] as $item) {
            if (is_string($item) && '' !== $item) {
                $cleanScope[] = $item;
            } elseif (is_scalar($item)) {
                $converted = (string) $item;
                if ('' !== $converted) {
                    $cleanScope[] = $converted;
                }
            }
        }

        $qualification->setQualificationScope($cleanScope);
    }

    /**
     * 更新简单可选字段
     * @param array<string, mixed> $data
     */
    private function updateSimpleOptionalFields(InstitutionQualification $qualification, array $data): void
    {
        $simpleFields = [
            'qualificationStatus' => 'setQualificationStatus',
            'attachments' => 'setAttachments',
        ];

        foreach ($simpleFields as $field => $setter) {
            $this->updateFieldIfSet($qualification, $data, $field, $setter);
        }
    }

    /**
     * 更新续期可选字段
     * @param array<string, mixed> $renewalData
     */
    private function updateRenewalOptionalFields(InstitutionQualification $qualification, array $renewalData): void
    {
        $this->updateIssuingAuthorityIfProvided($qualification, $renewalData);
        $this->updateIssueDateIfProvided($qualification, $renewalData);
        $this->updateQualificationScopeIfProvided($qualification, $renewalData);
        $this->updateAttachmentsIfProvided($qualification, $renewalData);
    }

    /**
     * 更新颁发机构（如果提供）
     * @param array<string, mixed> $renewalData
     */
    private function updateIssuingAuthorityIfProvided(InstitutionQualification $qualification, array $renewalData): void
    {
        $issuingAuthority = $renewalData['issuingAuthority'] ?? null;
        if (is_string($issuingAuthority)) {
            $qualification->setIssuingAuthority($issuingAuthority);
        }
    }

    /**
     * 更新颁发日期（如果提供）
     * @param array<string, mixed> $renewalData
     */
    private function updateIssueDateIfProvided(InstitutionQualification $qualification, array $renewalData): void
    {
        $issueDate = $renewalData['issueDate'] ?? null;
        if ($issueDate instanceof \DateTimeImmutable) {
            $qualification->setIssueDate($issueDate);
        }
    }

    /**
     * 更新资质范围（如果提供）
     * @param array<string, mixed> $renewalData
     */
    private function updateQualificationScopeIfProvided(InstitutionQualification $qualification, array $renewalData): void
    {
        if (!isset($renewalData['qualificationScope']) || !is_array($renewalData['qualificationScope'])) {
            return;
        }

        $cleanScope = [];
        foreach ($renewalData['qualificationScope'] as $item) {
            if (is_string($item) && '' !== $item) {
                $cleanScope[] = $item;
            } elseif (is_scalar($item)) {
                $converted = (string) $item;
                if ('' !== $converted) {
                    $cleanScope[] = $converted;
                }
            }
        }
        $qualification->setQualificationScope($cleanScope);
    }

    /**
     * 更新附件（如果提供）
     * @param array<string, mixed> $renewalData
     */
    private function updateAttachmentsIfProvided(InstitutionQualification $qualification, array $renewalData): void
    {
        $attachments = $renewalData['attachments'] ?? null;
        if (!is_array($attachments)) {
            return;
        }

        /** @var array<string, mixed> $attachments */
        $qualification->setAttachments($attachments);
    }

    /**
     * 确保值为字符串
     * @param mixed $value
     */
    private function ensureString($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * 确保值为日期对象
     * @param mixed $value
     */
    private function ensureDate($value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        if (is_scalar($value)) {
            return new \DateTimeImmutable((string) $value);
        }

        return new \DateTimeImmutable();
    }

    /**
     * 确保值为数组
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function ensureArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $value;
    }
}
