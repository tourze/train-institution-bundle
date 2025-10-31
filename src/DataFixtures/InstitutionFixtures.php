<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TrainInstitutionBundle\Entity\Institution;

#[When(env: 'test')]
#[When(env: 'dev')]
class InstitutionFixtures extends Fixture implements FixtureGroupInterface
{
    public const ENTERPRISE_INSTITUTION_REFERENCE = 'enterprise-institution';
    public const SOCIAL_INSTITUTION_REFERENCE = 'social-institution';
    public const GOVERNMENT_INSTITUTION_REFERENCE = 'government-institution';
    public const PENDING_INSTITUTION_REFERENCE = 'pending-institution';

    public static function getGroups(): array
    {
        return ['train-institution', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        // 企业培训机构
        $enterpriseInstitution = Institution::create(
            '北京安全技能培训中心',
            'INST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138001',
            'enterprise@test.local',
            '北京市朝阳区建国路88号',
            '安全生产培训、技能培训、职业资格认证',
            new \DateTimeImmutable('2020-01-15'),
            'REG123456789',
            '正常运营',
            [
                'department' => '培训部',
                'head_count' => 50,
                'structure' => ['管理层', '培训师', '行政人员'],
            ]
        );
        $manager->persist($enterpriseInstitution);

        // 社会培训机构
        $socialInstitution = Institution::create(
            '上海职业技能发展学院',
            'INST002',
            '社会培训机构',
            '王五',
            '赵六',
            '13800138002',
            'social@test.local',
            '上海市浦东新区张江高科技园区',
            '职业技能培训、继续教育、技术认证',
            new \DateTimeImmutable('2019-03-10'),
            'REG987654321',
            '正常运营',
            [
                'department' => '教学部',
                'head_count' => 80,
                'structure' => ['校务委员会', '教务处', '学生处'],
            ]
        );
        $manager->persist($socialInstitution);

        // 政府培训机构
        $governmentInstitution = Institution::create(
            '深圳市安全监督管理局培训中心',
            'INST003',
            '政府培训机构',
            '孙七',
            '周八',
            '13800138003',
            'government@test.local',
            '深圳市福田区福华路118号',
            '安全监管人员培训、企业安全培训',
            new \DateTimeImmutable('2018-07-01'),
            'REG456789123',
            '正常运营',
            [
                'department' => '培训科',
                'head_count' => 30,
                'structure' => ['局领导', '培训科', '监管科'],
            ]
        );
        $manager->persist($governmentInstitution);

        // 待审核机构
        $pendingInstitution = Institution::create(
            '广州新兴技能培训机构',
            'INST004',
            '企业培训机构',
            '吴九',
            '郑十',
            '13800138004',
            'pending@test.local',
            '广州市天河区珠江新城',
            '新兴技术培训、IT技能培训',
            new \DateTimeImmutable('2023-12-01'),
            'REG789123456',
            '待审核',
            [
                'department' => '技术部',
                'head_count' => 20,
                'structure' => ['技术总监', '培训师', '助教'],
            ]
        );
        $manager->persist($pendingInstitution);

        $manager->flush();

        // 添加引用
        $this->addReference(self::ENTERPRISE_INSTITUTION_REFERENCE, $enterpriseInstitution);
        $this->addReference(self::SOCIAL_INSTITUTION_REFERENCE, $socialInstitution);
        $this->addReference(self::GOVERNMENT_INSTITUTION_REFERENCE, $governmentInstitution);
        $this->addReference(self::PENDING_INSTITUTION_REFERENCE, $pendingInstitution);
    }
}
