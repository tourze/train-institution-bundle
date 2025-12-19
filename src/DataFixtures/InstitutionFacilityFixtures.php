<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

#[When(env: 'test')]
#[When(env: 'dev')]
final class InstitutionFacilityFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const CLASSROOM_FACILITY_REFERENCE = 'classroom-facility';
    public const TRAINING_FACILITY_REFERENCE = 'training-facility';
    public const OFFICE_FACILITY_REFERENCE = 'office-facility';
    public const MAINTENANCE_FACILITY_REFERENCE = 'maintenance-facility';

    public static function getGroups(): array
    {
        return ['train-institution', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $enterpriseInstitution = $this->getReference(InstitutionFixtures::ENTERPRISE_INSTITUTION_REFERENCE, Institution::class);
        $socialInstitution = $this->getReference(InstitutionFixtures::SOCIAL_INSTITUTION_REFERENCE, Institution::class);

        // 多媒体教室
        $classroom = InstitutionFacility::create(
            $enterpriseInstitution,
            '教室',
            '多媒体教室A101',
            '1楼101室',
            120.0,
            60,
            [
                'projector' => '投影仪',
                'screen' => '幕布',
                'sound_system' => '音响系统',
                'computers' => '电脑30台',
                'desks' => '课桌30张',
                'chairs' => '椅子60张',
            ],
            [
                'fire_extinguisher' => '灭火器4个',
                'smoke_detector' => '烟雾探测器',
                'emergency_exit' => '安全出口标识',
                'first_aid_kit' => '医疗急救包',
            ],
            '正常使用'
        );
        $manager->persist($classroom);

        // 实训场地
        $trainingFacility = InstitutionFacility::create(
            $enterpriseInstitution,
            '实训场地',
            '安全操作实训中心',
            '2楼201-205室',
            300.0,
            40,
            [
                'safety_equipment' => '安全防护设备',
                'training_machines' => '实训设备10台',
                'tools' => '专用工具箱',
                'workbenches' => '工作台20个',
                'storage' => '器材储存柜',
            ],
            [
                'emergency_shower' => '紧急冲淋装置',
                'eyewash_station' => '洗眼器',
                'fire_suppression' => '自动灭火系统',
                'ventilation' => '通风系统',
                'alarm_system' => '报警系统',
            ],
            '正常使用'
        );
        $manager->persist($trainingFacility);

        // 办公区域
        $office = InstitutionFacility::create(
            $socialInstitution,
            '办公区域',
            '行政办公区',
            '3楼整层',
            200.0,
            25,
            [
                'computers' => '办公电脑25台',
                'printers' => '打印机5台',
                'meeting_table' => '会议桌2张',
                'filing_cabinets' => '文件柜10个',
                'office_furniture' => '办公桌椅25套',
            ],
            [
                'fire_extinguisher' => '灭火器6个',
                'smoke_detector' => '烟雾探测器',
                'security_system' => '安防系统',
                'emergency_lighting' => '应急照明',
            ],
            '正常使用'
        );
        $manager->persist($office);

        // 维修中的设施
        $maintenanceFacility = InstitutionFacility::create(
            $socialInstitution,
            '教室',
            '理论教室B201',
            '2楼201室',
            80.0,
            40,
            [
                'whiteboard' => '白板',
                'desks' => '课桌20张',
                'chairs' => '椅子40张',
            ],
            [
                'fire_extinguisher' => '灭火器2个',
                'smoke_detector' => '烟雾探测器',
            ],
            '维修中'
        );
        $manager->persist($maintenanceFacility);

        $manager->flush();

        // 添加引用
        $this->addReference(self::CLASSROOM_FACILITY_REFERENCE, $classroom);
        $this->addReference(self::TRAINING_FACILITY_REFERENCE, $trainingFacility);
        $this->addReference(self::OFFICE_FACILITY_REFERENCE, $office);
        $this->addReference(self::MAINTENANCE_FACILITY_REFERENCE, $maintenanceFacility);
    }

    public function getDependencies(): array
    {
        return [
            InstitutionFixtures::class,
        ];
    }
}
