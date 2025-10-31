<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

#[When(env: 'test')]
#[When(env: 'dev')]
class InstitutionQualificationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const TRAINING_LICENSE_REFERENCE = 'training-license';
    public const SAFETY_QUALIFICATION_REFERENCE = 'safety-qualification';
    public const SPECIAL_OPERATION_REFERENCE = 'special-operation';
    public const EXPIRED_QUALIFICATION_REFERENCE = 'expired-qualification';

    public static function getGroups(): array
    {
        return ['train-institution', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $enterpriseInstitution = $this->getReference(InstitutionFixtures::ENTERPRISE_INSTITUTION_REFERENCE, Institution::class);
        $socialInstitution = $this->getReference(InstitutionFixtures::SOCIAL_INSTITUTION_REFERENCE, Institution::class);
        $governmentInstitution = $this->getReference(InstitutionFixtures::GOVERNMENT_INSTITUTION_REFERENCE, Institution::class);

        // 办学许可证 - 有效
        $trainingLicense = InstitutionQualification::create(
            $enterpriseInstitution,
            '办学许可证',
            '安全生产培训机构办学许可证',
            'CERT-001-2023',
            '北京市应急管理局',
            new \DateTimeImmutable('2023-01-15'),
            new \DateTimeImmutable('2023-01-15'),
            new \DateTimeImmutable('2026-01-14'),
            ['安全生产培训', '特种作业培训', '应急救援培训', '初级', '中级', '高级', '北京市', '河北省'],
            '有效',
            [
                'certificate_scan' => 'uploads/certs/cert-001-scan.pdf',
                'annual_report' => 'uploads/reports/annual-2023.pdf',
            ]
        );
        $manager->persist($trainingLicense);

        // 安全培训资质 - 有效
        $safetyQualification = InstitutionQualification::create(
            $enterpriseInstitution,
            '安全培训资质',
            '生产经营单位安全培训机构资质证书',
            'SAFE-002-2022',
            '国家应急管理部',
            new \DateTimeImmutable('2022-06-01'),
            new \DateTimeImmutable('2022-06-01'),
            new \DateTimeImmutable('2025-05-31'),
            ['主要负责人', '安全管理人员', '特种作业人员', '矿山', '危化', '烟花爆竹', '金属冶炼'],
            '有效',
            [
                'qualification_cert' => 'uploads/certs/safe-002.pdf',
                'evaluation_report' => 'uploads/eval/eval-2022.pdf',
            ]
        );
        $manager->persist($safetyQualification);

        // 特种作业培训资质 - 有效
        $specialOperation = InstitutionQualification::create(
            $socialInstitution,
            '特种作业培训资质',
            '特种作业人员培训资格证',
            'SPEC-003-2023',
            '上海市应急管理局',
            new \DateTimeImmutable('2023-03-20'),
            new \DateTimeImmutable('2023-03-20'),
            new \DateTimeImmutable('2026-03-19'),
            ['电工作业', '焊接与热切割', '高处作业', '制冷与空调作业', '理论培训', '实操培训', '复审培训'],
            '有效',
            [
                'license_scan' => 'uploads/certs/spec-003.pdf',
                'facility_check' => 'uploads/checks/facility-2023.pdf',
            ]
        );
        $manager->persist($specialOperation);

        // 已过期的资质 - 用于测试到期提醒
        $expiredQualification = InstitutionQualification::create(
            $governmentInstitution,
            '继续教育资质',
            '安全生产继续教育培训资质',
            'CONT-004-2020',
            '深圳市应急管理局',
            new \DateTimeImmutable('2020-01-01'),
            new \DateTimeImmutable('2020-01-01'),
            new \DateTimeImmutable('2023-12-31'),
            ['安全管理人员继续教育', '特种作业人员复审', '8小时', '16小时', '24小时', '线下培训', '线上培训', '混合培训'],
            '已过期',
            [
                'original_cert' => 'uploads/certs/cont-004.pdf',
                'expiry_notice' => 'uploads/notices/expiry-2023.pdf',
            ]
        );
        $manager->persist($expiredQualification);

        $manager->flush();

        // 添加引用
        $this->addReference(self::TRAINING_LICENSE_REFERENCE, $trainingLicense);
        $this->addReference(self::SAFETY_QUALIFICATION_REFERENCE, $safetyQualification);
        $this->addReference(self::SPECIAL_OPERATION_REFERENCE, $specialOperation);
        $this->addReference(self::EXPIRED_QUALIFICATION_REFERENCE, $expiredQualification);
    }

    public function getDependencies(): array
    {
        return [
            InstitutionFixtures::class,
        ];
    }
}
