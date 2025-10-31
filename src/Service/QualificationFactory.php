<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * 资质实体工厂服务
 */
final class QualificationFactory
{
    /**
     * 创建资质实体
     * @param array<string, mixed> $qualificationData
     */
    public function createQualificationEntity(Institution $institution, array $qualificationData): InstitutionQualification
    {
        return InstitutionQualification::create(
            $institution,
            $this->extractString($qualificationData, 'qualificationType'),
            $this->extractString($qualificationData, 'qualificationName'),
            $this->extractString($qualificationData, 'certificateNumber'),
            $this->extractString($qualificationData, 'issuingAuthority'),
            $this->parseDate($qualificationData['issueDate']),
            $this->parseDate($qualificationData['validFrom']),
            $this->parseDate($qualificationData['validTo']),
            $this->parseQualificationScope($qualificationData),
            $this->getQualificationStatusOrDefault($qualificationData),
            $this->getAttachmentsOrDefault($qualificationData)
        );
    }

    /**
     * 解析日期
     * @param mixed $date
     */
    public function parseDate($date): \DateTimeImmutable
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
     * 解析资质范围
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function parseQualificationScope(array $data): array
    {
        if (!isset($data['qualificationScope']) || !is_array($data['qualificationScope'])) {
            return [];
        }

        $result = [];
        foreach ($data['qualificationScope'] as $item) {
            if (is_string($item) && '' !== $item) {
                $result[] = $item;
            } elseif (is_scalar($item)) {
                $converted = (string) $item;
                if ('' !== $converted) {
                    $result[] = $converted;
                }
            }
        }

        return $result;
    }

    /**
     * 获取资质状态或默认值
     * @param array<string, mixed> $data
     */
    private function getQualificationStatusOrDefault(array $data): string
    {
        $status = $data['qualificationStatus'] ?? null;

        return is_string($status) ? $status : '有效';
    }

    /**
     * 获取附件或默认值
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function getAttachmentsOrDefault(array $data): array
    {
        if (!isset($data['attachments'])) {
            return [];
        }

        $attachments = $data['attachments'];
        if (!is_array($attachments)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $attachments;
    }

    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     */
    private function extractString(array $data, string $key): string
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
