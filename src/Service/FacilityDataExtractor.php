<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

/**
 * 设施数据提取器
 *
 * 负责从数组中安全提取和转换设施相关数据
 */
final class FacilityDataExtractor
{
    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     */
    public function extractString(array $data, string $key, string $default = ''): string
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
     * 安全提取浮点值
     * @param array<string, mixed> $data
     */
    public function extractFloat(array $data, string $key, float $default = 0.0): float
    {
        if (!isset($data[$key])) {
            return $default;
        }

        $value = $data[$key];

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * 安全提取整数值
     * @param array<string, mixed> $data
     */
    public function extractInt(array $data, string $key, int $default = 0): int
    {
        if (!isset($data[$key])) {
            return $default;
        }

        $value = $data[$key];
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * 安全提取数组值
     * @param array<string, mixed> $data
     * @return array<int|string, mixed>
     */
    public function extractArray(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            return [];
        }

        $value = $data[$key];

        return is_array($value) ? $value : [];
    }
}
