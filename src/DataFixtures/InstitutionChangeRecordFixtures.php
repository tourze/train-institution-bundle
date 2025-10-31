<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;

#[When(env: 'test')]
#[When(env: 'dev')]
class InstitutionChangeRecordFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const CONTACT_CHANGE_REFERENCE = 'contact-change';
    public const ADDRESS_CHANGE_REFERENCE = 'address-change';
    public const STATUS_CHANGE_REFERENCE = 'status-change';
    public const PENDING_CHANGE_REFERENCE = 'pending-change';

    public static function getGroups(): array
    {
        return ['train-institution', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $enterpriseInstitution = $this->getReference(InstitutionFixtures::ENTERPRISE_INSTITUTION_REFERENCE, Institution::class);
        $socialInstitution = $this->getReference(InstitutionFixtures::SOCIAL_INSTITUTION_REFERENCE, Institution::class);

        // 联系方式变更记录 - 已审批
        $contactChange = InstitutionChangeRecord::create(
            $enterpriseInstitution,
            '联系方式变更',
            [
                'field' => 'contact_phone',
                'change_description' => '更新机构联系电话',
                'change_category' => '基本信息变更',
            ],
            [
                'contact_phone' => '13800138000',
                'contact_person' => '李四',
            ],
            [
                'contact_phone' => '13800138001',
                'contact_person' => '李四',
            ],
            '原电话号码已停用，更换为新的联系电话',
            '系统管理员',
            '已审批'
        );

        // 设置审批信息
        $contactChange->approve('审核主管');
        $manager->persist($contactChange);

        // 地址变更记录 - 已审批
        $addressChange = InstitutionChangeRecord::create(
            $socialInstitution,
            '地址变更',
            [
                'field' => 'address',
                'change_description' => '机构搬迁至新地址',
                'change_category' => '重要信息变更',
                'requires_site_visit' => true,
            ],
            [
                'address' => '上海市浦东新区原地址100号',
                'business_scope' => '职业技能培训、继续教育、技术认证',
            ],
            [
                'address' => '上海市浦东新区张江高科技园区',
                'business_scope' => '职业技能培训、继续教育、技术认证',
            ],
            '为了扩大办学规模，机构搬迁至设施更完善的新地址',
            '机构负责人',
            '已审批'
        );

        $addressChange->approve('区教育局');
        $manager->persist($addressChange);

        // 机构状态变更记录 - 已审批
        $statusChange = InstitutionChangeRecord::create(
            $enterpriseInstitution,
            '状态变更',
            [
                'field' => 'institution_status',
                'change_description' => '机构状态由待审核变更为正常运营',
                'change_category' => '状态变更',
            ],
            [
                'institution_status' => '待审核',
                'approval_date' => null,
            ],
            [
                'institution_status' => '正常运营',
                'approval_date' => '2023-02-01',
            ],
            '机构通过了所有资质审核和现场检查，符合办学条件',
            '监管部门',
            '已审批'
        );

        $statusChange->approve('应急管理局');
        $manager->persist($statusChange);

        // 待审批的变更记录
        $pendingChange = InstitutionChangeRecord::create(
            $socialInstitution,
            '经营范围变更',
            [
                'field' => 'business_scope',
                'change_description' => '增加新的培训项目',
                'change_category' => '业务范围扩展',
                'new_qualifications_needed' => true,
            ],
            [
                'business_scope' => '职业技能培训、继续教育、技术认证',
            ],
            [
                'business_scope' => '职业技能培训、继续教育、技术认证、网络安全培训、人工智能培训',
            ],
            '应市场需求，增加网络安全和人工智能相关培训项目',
            '业务主管',
            '待审批'
        );
        $manager->persist($pendingChange);

        $manager->flush();

        // 添加引用
        $this->addReference(self::CONTACT_CHANGE_REFERENCE, $contactChange);
        $this->addReference(self::ADDRESS_CHANGE_REFERENCE, $addressChange);
        $this->addReference(self::STATUS_CHANGE_REFERENCE, $statusChange);
        $this->addReference(self::PENDING_CHANGE_REFERENCE, $pendingChange);
    }

    public function getDependencies(): array
    {
        return [
            InstitutionFixtures::class,
        ];
    }
}
