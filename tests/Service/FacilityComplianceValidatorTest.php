<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Service\FacilityComplianceValidator;

/**
 * @internal
 */
#[CoversClass(FacilityComplianceValidator::class)]
final class FacilityComplianceValidatorTest extends TestCase
{
    private FacilityComplianceValidator $validator;

    private Institution $institution;

    /**
     * @return iterable<string, array{string, float, int, array<int|string, mixed>, array<string>}>
     */
    public static function provideAreaRequirements(): iterable
    {
        $safetyEquipment = [
            ['name' => '灭火器'],
            ['name' => '烟雾报警器'],
            ['name' => '应急照明'],
        ];

        yield '教室面积不足' => [
            '教室',
            40.0, // 小于50平方米
            20,
            $safetyEquipment,
            ['设施面积不足，最小要求50平方米，当前40平方米'],
        ];

        yield '实训场地面积不足' => [
            '实训场地',
            80.0, // 小于100平方米
            30,
            $safetyEquipment,
            ['设施面积不足，最小要求100平方米，当前80平方米'],
        ];

        yield '办公区域面积不足' => [
            '办公区域',
            15.0, // 小于20平方米
            5,
            $safetyEquipment,
            ['设施面积不足，最小要求20平方米，当前15平方米'],
        ];
    }

    /**
     * @return iterable<string, array{string, float, int, array<int|string, mixed>, array<string>}>
     */
    public static function providePerCapitaAreaRequirements(): iterable
    {
        $safetyEquipment = [
            ['name' => '灭火器'],
            ['name' => '烟雾报警器'],
            ['name' => '应急照明'],
        ];

        yield '教室人均面积不足' => [
            '教室',
            100.0,
            80, // 人均1.25平方米，小于1.5
            $safetyEquipment,
            ['人均面积不足，要求1.5平方米/人，当前1.25平方米/人'],
        ];

        yield '实训场地人均面积不足' => [
            '实训场地',
            100.0,
            60, // 人均1.67平方米，小于2.0
            $safetyEquipment,
            ['人均面积不足，要求2平方米/人，当前1.6666666666667平方米/人'],
        ];
    }

    /**
     * @return iterable<string, array{array<int|string, mixed>, array<string>}>
     */
    public static function provideSafetyEquipmentRequirements(): iterable
    {
        yield '缺少所有安全设备' => [
            [],
            ['灭火器', '烟雾报警器', '应急照明'],
        ];

        yield '缺少部分安全设备' => [
            [['name' => '灭火器']],
            ['烟雾报警器', '应急照明'],
        ];

        yield '安全设备使用字符串格式' => [
            ['灭火器', '烟雾报警器'],
            ['应急照明'],
        ];

        yield '混合格式的安全设备' => [
            [
                ['name' => '灭火器'],
                '烟雾报警器',
            ],
            ['应急照明'],
        ];
    }

    /**
     * @return iterable<string, array{string, array<string>}>
     */
    public static function provideFacilityStatusRequirements(): iterable
    {
        yield '正常使用状态' => [
            '正常使用',
            [],
        ];

        yield '维修中状态' => [
            '维修中',
            ['设施状态异常：维修中'],
        ];

        yield '停用状态' => [
            '停用',
            ['设施状态异常：停用'],
        ];

        yield '待检查状态' => [
            '待检查',
            ['设施状态异常：待检查'],
        ];
    }

    public function testCheckFacilityComplianceWithCompliantFacility(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '培训教室1',
            '一楼101室',
            100.0,
            50,
            [],
            [
                ['name' => '灭火器'],
                ['name' => '烟雾报警器'],
                ['name' => '应急照明'],
            ],
            '正常使用'
        );

        $issues = $this->validator->checkFacilityCompliance($facility);

        self::assertIsArray($issues);
        self::assertEmpty($issues, '符合规范的设施不应有任何问题');
    }

    /**
     * @param array<int|string, mixed> $safetyEquipment
     * @param array<string> $expectedIssues
     */
    #[DataProvider('provideAreaRequirements')]
    public function testCheckAreaRequirements(
        string $facilityType,
        float $facilityArea,
        int $capacity,
        array $safetyEquipment,
        array $expectedIssues,
    ): void {
        $facility = InstitutionFacility::create(
            $this->institution,
            $facilityType,
            '测试设施',
            '一楼101室',
            $facilityArea,
            $capacity,
            [],
            $safetyEquipment,
            '正常使用'
        );

        $issues = $this->validator->checkFacilityCompliance($facility);

        foreach ($expectedIssues as $expectedIssue) {
            self::assertContains($expectedIssue, $issues);
        }
    }

    /**
     * @param array<int|string, mixed> $safetyEquipment
     * @param array<string> $expectedIssues
     */
    #[DataProvider('providePerCapitaAreaRequirements')]
    public function testCheckPerCapitaAreaRequirements(
        string $facilityType,
        float $facilityArea,
        int $capacity,
        array $safetyEquipment,
        array $expectedIssues,
    ): void {
        $facility = InstitutionFacility::create(
            $this->institution,
            $facilityType,
            '测试设施',
            '一楼101室',
            $facilityArea,
            $capacity,
            [],
            $safetyEquipment,
            '正常使用'
        );

        $issues = $this->validator->checkFacilityCompliance($facility);

        foreach ($expectedIssues as $expectedIssue) {
            self::assertContains($expectedIssue, $issues);
        }
    }

    /**
     * @param array<int|string, mixed> $safetyEquipment
     * @param array<string> $expectedMissingEquipment
     */
    #[DataProvider('provideSafetyEquipmentRequirements')]
    public function testCheckSafetyEquipmentRequirements(
        array $safetyEquipment,
        array $expectedMissingEquipment,
    ): void {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '测试设施',
            '一楼101室',
            100.0,
            50,
            [],
            $safetyEquipment,
            '正常使用'
        );

        $issues = $this->validator->checkFacilityCompliance($facility);

        foreach ($expectedMissingEquipment as $equipment) {
            $expectedIssue = "缺少必要的安全设备：{$equipment}";
            self::assertContains($expectedIssue, $issues);
        }
    }

    /**
     * @param array<string> $expectedIssues
     */
    #[DataProvider('provideFacilityStatusRequirements')]
    public function testCheckFacilityStatusRequirement(string $status, array $expectedIssues): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '测试设施',
            '一楼101室',
            100.0,
            50,
            [],
            [
                ['name' => '灭火器'],
                ['name' => '烟雾报警器'],
                ['name' => '应急照明'],
            ],
            $status
        );

        $issues = $this->validator->checkFacilityCompliance($facility);

        if ([] === $expectedIssues) {
            self::assertEmpty($issues);
        } else {
            foreach ($expectedIssues as $expectedIssue) {
                self::assertContains($expectedIssue, $issues);
            }
        }
    }

    public function testCheckFacilityComplianceWithMultipleIssues(): void
    {
        $facility = InstitutionFacility::create(
            $this->institution,
            '教室',
            '测试设施',
            '一楼101室',
            30.0, // 面积不足
            30, // 人均面积不足
            [],
            [], // 缺少安全设备
            '维修中' // 状态异常
        );

        $issues = $this->validator->checkFacilityCompliance($facility);

        self::assertGreaterThan(1, count($issues), '应检测到多个问题');
        self::assertContains('设施面积不足，最小要求50平方米，当前30平方米', $issues);
        self::assertContains('人均面积不足，要求1.5平方米/人，当前1平方米/人', $issues);
        self::assertContains('缺少必要的安全设备：灭火器', $issues);
        self::assertContains('缺少必要的安全设备：烟雾报警器', $issues);
        self::assertContains('缺少必要的安全设备：应急照明', $issues);
        self::assertContains('设施状态异常：维修中', $issues);
    }

    public function testCheckFacilityComplianceForNonRegulatedFacilityTypes(): void
    {
        // 非教室、实训场地的设施类型不检查人均面积
        $facility = InstitutionFacility::create(
            $this->institution,
            '图书馆',
            '测试图书馆',
            '二楼201室',
            50.0,
            100, // 人均0.5平方米，但不应触发人均面积检查
            [],
            [
                ['name' => '灭火器'],
                ['name' => '烟雾报警器'],
                ['name' => '应急照明'],
            ],
            '正常使用'
        );

        $issues = $this->validator->checkFacilityCompliance($facility);

        self::assertIsArray($issues);
        // 不应包含人均面积不足的问题
        foreach ($issues as $issue) {
            self::assertStringNotContainsString('人均面积不足', $issue);
        }
    }

    protected function setUp(): void
    {
        $this->validator = new FacilityComplianceValidator();
        $this->institution = Institution::create(
            '测试机构',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );
    }
}
