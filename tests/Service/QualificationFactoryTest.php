<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Service\QualificationFactory;

/**
 * QualificationFactory 单元测试
 *
 * @internal
 */
#[CoversClass(QualificationFactory::class)]
final class QualificationFactoryTest extends TestCase
{
    private QualificationFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new QualificationFactory();
    }

    public function testCreateQualificationEntity(): void
    {
        $institution = $this->createMockInstitution();
        $qualificationData = $this->getValidQualificationData();

        $result = $this->factory->createQualificationEntity($institution, $qualificationData);

        self::assertSame($qualificationData['qualificationType'], $result->getQualificationType());
        self::assertSame($qualificationData['qualificationName'], $result->getQualificationName());
        self::assertSame($qualificationData['certificateNumber'], $result->getCertificateNumber());
        self::assertSame($qualificationData['issuingAuthority'], $result->getIssuingAuthority());
        self::assertEquals($qualificationData['issueDate'], $result->getIssueDate());
        self::assertEquals($qualificationData['validFrom'], $result->getValidFrom());
        self::assertEquals($qualificationData['validTo'], $result->getValidTo());
        self::assertSame($qualificationData['qualificationScope'], $result->getQualificationScope());
        self::assertSame($qualificationData['qualificationStatus'], $result->getQualificationStatus());
        self::assertSame($qualificationData['attachments'], $result->getAttachments());
    }

    public function testCreateQualificationEntityWithDefaultValues(): void
    {
        $institution = $this->createMockInstitution();
        $minimalData = [
            'qualificationType' => '职业技能培训资质',
            'qualificationName' => '安全生产培训资质',
            'certificateNumber' => 'CERT-2024-001',
            'issuingAuthority' => '应急管理部',
            'issueDate' => new \DateTimeImmutable('2024-01-01'),
            'validFrom' => new \DateTimeImmutable('2024-01-01'),
            'validTo' => new \DateTimeImmutable('2026-01-01'),
        ];

        $result = $this->factory->createQualificationEntity($institution, $minimalData);

        self::assertSame('有效', $result->getQualificationStatus()); // 默认状态
        self::assertSame([], $result->getQualificationScope()); // 默认空数组
        self::assertSame([], $result->getAttachments()); // 默认空数组
    }

    #[DataProvider('parseDateProvider')]
    public function testParseDate(string|\DateTimeInterface $input, \DateTimeImmutable $expected): void
    {
        $result = $this->factory->parseDate($input);

        self::assertEquals($expected->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function parseDateProvider(): array
    {
        return [
            'DateTimeImmutable input' => [
                new \DateTimeImmutable('2024-01-01'),
                new \DateTimeImmutable('2024-01-01'),
            ],
            'DateTime input' => [
                new \DateTime('2024-01-01'),
                new \DateTimeImmutable('2024-01-01'),
            ],
            'string input' => [
                '2024-01-01',
                new \DateTimeImmutable('2024-01-01'),
            ],
            'complex string input' => [
                '2024-12-31 23:59:59',
                new \DateTimeImmutable('2024-12-31 23:59:59'),
            ],
        ];
    }

    public function testCreateQualificationEntityWithStringQualificationScope(): void
    {
        $institution = $this->createMockInstitution();
        $qualificationData = $this->getValidQualificationData();
        $qualificationData['qualificationScope'] = [
            '特种作业人员培训',
            '安全管理人员培训',
            '',  // 空字符串应被过滤
            '危险化学品培训',
            123, // 数字应被转换为字符串
        ];

        $result = $this->factory->createQualificationEntity($institution, $qualificationData);

        $expectedScope = [
            '特种作业人员培训',
            '安全管理人员培训',
            '危险化学品培训',
            '123',
        ];

        self::assertSame($expectedScope, $result->getQualificationScope());
    }

    public function testCreateQualificationEntityWithInvalidQualificationScope(): void
    {
        $institution = $this->createMockInstitution();
        $qualificationData = $this->getValidQualificationData();
        $qualificationData['qualificationScope'] = 'not-an-array';

        $result = $this->factory->createQualificationEntity($institution, $qualificationData);

        self::assertSame([], $result->getQualificationScope());
    }

    public function testCreateQualificationEntityWithNonStringQualificationStatus(): void
    {
        $institution = $this->createMockInstitution();
        $qualificationData = $this->getValidQualificationData();
        $qualificationData['qualificationStatus'] = 123; // 非字符串

        $result = $this->factory->createQualificationEntity($institution, $qualificationData);

        self::assertSame('有效', $result->getQualificationStatus()); // 应使用默认值
    }

    public function testCreateQualificationEntityWithInvalidAttachments(): void
    {
        $institution = $this->createMockInstitution();
        $qualificationData = $this->getValidQualificationData();
        $qualificationData['attachments'] = 'not-an-array';

        $result = $this->factory->createQualificationEntity($institution, $qualificationData);

        self::assertSame([], $result->getAttachments());
    }

    private function createMockInstitution(): Institution
    {
        $institution = $this->createMock(Institution::class);
        $institution->method('getId')->willReturn('test-institution-id');

        return $institution;
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidQualificationData(): array
    {
        return [
            'qualificationType' => '职业技能培训资质',
            'qualificationName' => '安全生产培训资质',
            'certificateNumber' => 'CERT-2024-001',
            'issuingAuthority' => '应急管理部',
            'issueDate' => new \DateTimeImmutable('2024-01-01'),
            'validFrom' => new \DateTimeImmutable('2024-01-01'),
            'validTo' => new \DateTimeImmutable('2026-01-01'),
            'qualificationScope' => [
                '特种作业人员培训',
                '安全管理人员培训',
                '危险化学品培训',
            ],
            'qualificationStatus' => '有效',
            'attachments' => [
                ['name' => '资质证书.pdf', 'path' => '/uploads/cert_2024_001.pdf'],
                ['name' => '资质授权书.pdf', 'path' => '/uploads/auth_2024_001.pdf'],
            ],
        ];
    }
}
