<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;

/**
 * InstitutionFacilityRepository 单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionFacilityRepository::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionFacilityRepositoryTest extends AbstractRepositoryTestCase
{
    public function testRepositoryCanBeInjected(): void
    {
        $repository = self::getContainer()->get(InstitutionFacilityRepository::class);
        self::assertInstanceOf(InstitutionFacilityRepository::class, $repository);
    }

    public function testCanSaveAndFindEntity(): void
    {
        $entityManager = self::getEntityManager();

        $institution = Institution::create(
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

        $entityManager->persist($institution);
        $entityManager->flush();

        $facility = InstitutionFacility::create(
            $institution,
            '教室',
            '多媒体教室',
            '1楼101室',
            120.5,
            50,
            ['投影仪', '音响系统'],
            ['灭火器', '烟雾报警器'],
            '正常使用'
        );

        $entityManager->persist($facility);
        $entityManager->flush();

        /** @var InstitutionFacilityRepository $repository */
        $repository = self::getContainer()->get(InstitutionFacilityRepository::class);
        $foundFacility = $repository->find($facility->getId());

        self::assertInstanceOf(InstitutionFacility::class, $foundFacility);
        self::assertSame('教室', $foundFacility->getFacilityType());
        self::assertSame('多媒体教室', $foundFacility->getFacilityName());
    }

    protected function onSetUp(): void
    {
        // 集成测试的初始化逻辑
    }

    protected function createNewEntity(): object
    {
        $institution = Institution::create(
            '测试机构 ' . uniqid(),
            'TEST' . uniqid(),
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

        self::getEntityManager()->persist($institution);
        self::getEntityManager()->flush();

        return InstitutionFacility::create(
            $institution,
            '教室',
            '多媒体教室 ' . uniqid(),
            '1楼101室',
            120.5,
            50,
            ['投影仪', '音响系统'],
            ['灭火器', '烟雾报警器'],
            '正常使用'
        );
    }

    protected function getRepository(): InstitutionFacilityRepository
    {
        $repository = self::getContainer()->get(InstitutionFacilityRepository::class);
        self::assertInstanceOf(InstitutionFacilityRepository::class, $repository);

        return $repository;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    private function clearExistingData($entityManager): void
    {
        // 清理所有相关数据
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord')->execute();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\InstitutionQualification')->execute();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\InstitutionFacility')->execute();
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\Institution')->execute();
        $entityManager->flush();
    }

    public function testFindByInstitution(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建两个测试机构
        $institution1 = Institution::create(
            '测试机构1',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test1@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );
        $institution2 = Institution::create(
            '测试机构2',
            'TEST002',
            '企业培训机构',
            '王五',
            '赵六',
            '13900139000',
            'test2@example.com',
            '上海市浦东区',
            '职业技能培训',
            new \DateTimeImmutable('2020-02-01'),
            'REG234567'
        );
        $entityManager->persist($institution1);
        $entityManager->persist($institution2);

        // 为第一个机构创建设施
        $facility1 = InstitutionFacility::create(
            $institution1,
            '教室',
            '多媒体教室1',
            '1楼101室',
            120.5,
            50,
            ['投影仪', '音响系统'],
            ['灭火器', '烟雾报警器'],
            '正常使用'
        );
        $facility2 = InstitutionFacility::create(
            $institution1,
            '实验室',
            '安全实验室',
            '2楼201室',
            80.0,
            20,
            ['实验台', '通风设备'],
            ['喷淋系统', '紧急冲洗设备'],
            '正常使用'
        );

        // 为第二个机构创建设施
        $facility3 = InstitutionFacility::create(
            $institution2,
            '办公室',
            '教师办公室',
            '3楼301室',
            60.0,
            10,
            ['办公桌', '文件柜'],
            ['灭火器'],
            '正常使用'
        );

        $entityManager->persist($facility1);
        $entityManager->persist($facility2);
        $entityManager->persist($facility3);
        $entityManager->flush();

        // 测试按机构查找设施
        $institution1Facilities = $repository->findByInstitution($institution1);
        self::assertCount(2, $institution1Facilities);
        foreach ($institution1Facilities as $facility) {
            $institution = $facility->getInstitution();
            self::assertNotNull($institution);
            self::assertSame($institution1->getId(), $institution->getId());
        }

        $institution2Facilities = $repository->findByInstitution($institution2);
        self::assertCount(1, $institution2Facilities);
        $institution = $institution2Facilities[0]->getInstitution();
        self::assertNotNull($institution);
        self::assertSame($institution2->getId(), $institution->getId());
    }

    public function testFindByFacilityType(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建测试机构
        $institution = Institution::create(
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
        $entityManager->persist($institution);

        // 创建不同类型的设施
        $classroom1 = InstitutionFacility::create(
            $institution,
            '教室',
            '多媒体教室1',
            '1楼101室',
            120.5,
            50,
            ['投影仪', '音响系统'],
            ['灭火器', '烟雾报警器'],
            '正常使用'
        );
        $classroom2 = InstitutionFacility::create(
            $institution,
            '教室',
            '多媒体教室2',
            '1楼102室',
            100.0,
            40,
            ['投影仪'],
            ['灭火器'],
            '正常使用'
        );
        $laboratory = InstitutionFacility::create(
            $institution,
            '实验室',
            '安全实验室',
            '2楼201室',
            80.0,
            20,
            ['实验台', '通风设备'],
            ['喷淋系统', '紧急冲洗设备'],
            '正常使用'
        );
        $office = InstitutionFacility::create(
            $institution,
            '办公室',
            '教师办公室',
            '3楼301室',
            60.0,
            10,
            ['办公桌', '文件柜'],
            ['灭火器'],
            '正常使用'
        );

        $entityManager->persist($classroom1);
        $entityManager->persist($classroom2);
        $entityManager->persist($laboratory);
        $entityManager->persist($office);
        $entityManager->flush();

        // 测试按类型查找教室
        $classrooms = $repository->findByFacilityType('教室');
        self::assertCount(2, $classrooms);
        foreach ($classrooms as $classroom) {
            self::assertSame('教室', $classroom->getFacilityType());
        }

        // 测试按类型查找实验室
        $laboratories = $repository->findByFacilityType('实验室');
        self::assertCount(1, $laboratories);
        self::assertSame('实验室', $laboratories[0]->getFacilityType());
        self::assertSame('安全实验室', $laboratories[0]->getFacilityName());

        // 测试按类型查找办公室
        $offices = $repository->findByFacilityType('办公室');
        self::assertCount(1, $offices);
        self::assertSame('办公室', $offices[0]->getFacilityType());
        self::assertSame('教师办公室', $offices[0]->getFacilityName());

        // 测试不存在的设施类型
        $nonExistentType = $repository->findByFacilityType('不存在的类型');
        self::assertEmpty($nonExistentType);
    }

    public function testFindNeedingInspection(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建测试机构
        $institution = Institution::create(
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
        $entityManager->persist($institution);

        $now = new \DateTimeImmutable();

        // 创建需要检查的设施（下次检查日期为空）
        $facilityNullDate = InstitutionFacility::create(
            $institution,
            '教室',
            '需要检查的教室',
            '1楼101室',
            120.5,
            50,
            ['投影仪'],
            ['灭火器'],
            '正常使用'
        );
        // nextInspectionDate 为空，表示需要检查

        // 创建需要检查的设施（下次检查日期已过期）
        $facilityOverdue = InstitutionFacility::create(
            $institution,
            '实验室',
            '过期的实验室',
            '2楼201室',
            80.0,
            20,
            ['实验台'],
            ['喷淋系统'],
            '正常使用'
        );
        $facilityOverdue->setNextInspectionDate($now->modify('-10 days')); // 已过期

        // 创建不需要检查的设施（下次检查日期在未来）
        $facilityFuture = InstitutionFacility::create(
            $institution,
            '办公室',
            '未来检查的办公室',
            '3楼301室',
            60.0,
            10,
            ['办公桌'],
            ['灭火器'],
            '正常使用'
        );
        $facilityFuture->setNextInspectionDate($now->modify('+30 days')); // 未来日期

        // 创建需要检查的设施（今天需要检查）
        $facilityToday = InstitutionFacility::create(
            $institution,
            '储藏室',
            '今天检查的储藏室',
            '地下室B01',
            40.0,
            5,
            ['货架'],
            ['灭火器'],
            '正常使用'
        );
        $facilityToday->setNextInspectionDate($now); // 今天

        $entityManager->persist($facilityNullDate);
        $entityManager->persist($facilityOverdue);
        $entityManager->persist($facilityFuture);
        $entityManager->persist($facilityToday);
        $entityManager->flush();

        // 测试查找需要检查的设施
        $needingInspection = $repository->findNeedingInspection();

        // 应该包含3个设施：null日期的、过期的、今天的
        self::assertCount(3, $needingInspection);

        // 验证返回的设施确实需要检查
        $foundNames = [];
        foreach ($needingInspection as $facility) {
            $foundNames[] = $facility->getFacilityName();
            $nextDate = $facility->getNextInspectionDate();
            self::assertTrue(
                null === $nextDate || $nextDate <= $now,
                "设施 {$facility->getFacilityName()} 的下次检查日期应该为空或已过期"
            );
        }

        // 验证包含了预期的设施
        self::assertContains('需要检查的教室', $foundNames);
        self::assertContains('过期的实验室', $foundNames);
        self::assertContains('今天检查的储藏室', $foundNames);

        // 验证不包含未来的设施
        self::assertNotContains('未来检查的办公室', $foundNames);
    }

    public function testRemove(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建测试机构
        $institution = Institution::create(
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
        $entityManager->persist($institution);

        // 创建设施
        $facility = InstitutionFacility::create(
            $institution,
            '教室',
            '待删除的教室',
            '1楼101室',
            120.5,
            50,
            ['投影仪'],
            ['灭火器'],
            '正常使用'
        );
        $entityManager->persist($facility);
        $entityManager->flush();

        $facilityId = $facility->getId();

        // 验证设施已创建
        $foundFacility = $repository->find($facilityId);
        self::assertInstanceOf(InstitutionFacility::class, $foundFacility);

        // 删除设施
        $repository->remove($facility, true);

        // 验证设施已删除
        $deletedFacility = $repository->find($facilityId);
        self::assertNull($deletedFacility);
    }

    public function testRemoveWithoutFlushCustom(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建测试机构
        $institution = Institution::create(
            '测试机构',
            'TEST002',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test2@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG234567'
        );
        $entityManager->persist($institution);

        // 创建设施
        $facility = InstitutionFacility::create(
            $institution,
            '教室',
            '待删除的教室',
            '1楼101室',
            120.5,
            50,
            ['投影仪'],
            ['灭火器'],
            '正常使用'
        );
        $entityManager->persist($facility);
        $entityManager->flush();

        $facilityId = $facility->getId();

        // 删除设施但不立即 flush
        $repository->remove($facility, false);

        // 在 flush 前，直接查询数据库应该仍可找到设施
        $foundBeforeFlush = $entityManager->getConnection()
            ->executeQuery('SELECT id FROM train_institution_facility WHERE id = ?', [$facilityId])
            ->fetchOne()
        ;
        self::assertNotFalse($foundBeforeFlush, '删除标记后，flush前数据库中应仍存在记录');

        // 手动 flush
        $entityManager->flush();

        // flush 后设施已删除
        $entityManager->clear();
        $foundAfterFlush = $repository->find($facilityId);
        self::assertNull($foundAfterFlush);
    }
}
