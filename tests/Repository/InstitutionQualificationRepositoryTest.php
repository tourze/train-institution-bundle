<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository;

/**
 * InstitutionQualificationRepository 单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionQualificationRepository::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionQualificationRepositoryTest extends AbstractRepositoryTestCase
{
    public function testRepositoryCanBeInjected(): void
    {
        $repository = self::getContainer()->get(InstitutionQualificationRepository::class);
        self::assertInstanceOf(InstitutionQualificationRepository::class, $repository);
    }

    public function testCanSaveAndFindQualification(): void
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

        $qualification = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );

        $entityManager->persist($institution);
        $entityManager->persist($qualification);
        $entityManager->flush();

        /** @var InstitutionQualificationRepository $repository */
        $repository = self::getContainer()->get(InstitutionQualificationRepository::class);
        $foundQualification = $repository->find($qualification->getId());

        self::assertInstanceOf(InstitutionQualification::class, $foundQualification);
        self::assertSame('安全生产培训机构资质证书', $foundQualification->getQualificationName());
        self::assertSame('CERT001', $foundQualification->getCertificateNumber());
    }

    protected function onSetUp(): void
    {
        // 测试初始化逻辑
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

        return InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT' . uniqid(),
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );
    }

    protected function getRepository(): InstitutionQualificationRepository
    {
        $repository = self::getContainer()->get(InstitutionQualificationRepository::class);
        self::assertInstanceOf(InstitutionQualificationRepository::class, $repository);

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

    public function testFindByQualificationType(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建测试机构
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

        // 创建不同类型的资质
        $qualification1 = InstitutionQualification::create(
            $institution1,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );
        $qualification2 = InstitutionQualification::create(
            $institution2,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT002',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2026-02-01'),
            ['特种设备操作']
        );
        $qualification3 = InstitutionQualification::create(
            $institution1,
            '职业技能资质',
            '职业技能培训机构资质证书',
            'CERT003',
            '人力资源和社会保障部',
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2026-03-01'),
            ['电工操作', '焦工操作']
        );

        $entityManager->persist($qualification1);
        $entityManager->persist($qualification2);
        $entityManager->persist($qualification3);
        $entityManager->flush();

        // 测试按资质类型查承
        $safetyQualifications = $repository->findByQualificationType('安全培训资质');
        self::assertCount(2, $safetyQualifications);
        foreach ($safetyQualifications as $qualification) {
            self::assertSame('安全培训资质', $qualification->getQualificationType());
        }

        $skillQualifications = $repository->findByQualificationType('职业技能资质');
        self::assertCount(1, $skillQualifications);
        self::assertSame('职业技能资质', $skillQualifications[0]->getQualificationType());

        // 测试不存在的资质类型
        $nonExistentQualifications = $repository->findByQualificationType('不存在的类型');
        self::assertEmpty($nonExistentQualifications);
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

        // 为第一个机构创建多个资质
        $qualification1 = InstitutionQualification::create(
            $institution1,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );
        $qualification2 = InstitutionQualification::create(
            $institution1,
            '职业技能资质',
            '职业技能培训机构资质证书',
            'CERT002',
            '人力资源和社会保障部',
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2026-02-01'),
            ['电工操作']
        );

        // 为第二个机构创建一个资质
        $qualification3 = InstitutionQualification::create(
            $institution2,
            '安全培训资质',
            '安全生产培训机构资质证书',
            'CERT003',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2026-03-01'),
            ['特种设备操作']
        );

        $entityManager->persist($qualification1);
        $entityManager->persist($qualification2);
        $entityManager->persist($qualification3);
        $entityManager->flush();

        // 测试按机构查找资质
        $institution1Qualifications = $repository->findByInstitution($institution1);
        self::assertCount(2, $institution1Qualifications);
        foreach ($institution1Qualifications as $qualification) {
            $institution = $qualification->getInstitution();
            self::assertNotNull($institution);
            self::assertSame($institution1->getId(), $institution->getId());
        }

        $institution2Qualifications = $repository->findByInstitution($institution2);
        self::assertCount(1, $institution2Qualifications);
        $institution2Entity = $institution2Qualifications[0]->getInstitution();
        self::assertNotNull($institution2Entity);
        self::assertSame($institution2->getId(), $institution2Entity->getId());
    }

    public function testFindExpiringSoon(): void
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

        // 创建不同到期时间的资质
        $expiringSoonQualification = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '即将到期的资质',
            'CERT001',
            '国家安全监管总局',
            $now->modify('-1 year'),
            $now->modify('-1 year'),
            $now->modify('+15 days'), // 15天后到期
            ['特种作业培训']
        );
        $expiringSoonQualification->setQualificationStatus('有效');

        $farFutureQualification = InstitutionQualification::create(
            $institution,
            '职业技能资质',
            '远期到期的资质',
            'CERT002',
            '人力资源和社会保障部',
            $now->modify('-1 year'),
            $now->modify('-1 year'),
            $now->modify('+2 years'), // 2年后到期
            ['电工操作']
        );
        $farFutureQualification->setQualificationStatus('有效');

        $expiredQualification = InstitutionQualification::create(
            $institution,
            '已过期资质',
            '已过期的资质',
            'CERT003',
            '国家安全监管总局',
            $now->modify('-2 years'),
            $now->modify('-2 years'),
            $now->modify('-1 day'), // 已过期
            ['特种设备操作']
        );
        $expiredQualification->setQualificationStatus('有效'); // 状态仍为有效，但已过期

        $entityManager->persist($expiringSoonQualification);
        $entityManager->persist($farFutureQualification);
        $entityManager->persist($expiredQualification);
        $entityManager->flush();

        // 测试查找30天内即将到期的资质
        $expiringSoon = $repository->findExpiringSoon(30);
        self::assertCount(1, $expiringSoon);
        self::assertSame('CERT001', $expiringSoon[0]->getCertificateNumber());

        // 测试查找10天内即将到期的资质（应该为空）
        $expiringSoon10 = $repository->findExpiringSoon(10);
        self::assertEmpty($expiringSoon10);

        // 测试查找1000天内即将到期的资质（应该包含远期资质）
        $expiringSoon1000 = $repository->findExpiringSoon(1000);
        self::assertCount(2, $expiringSoon1000); // 不包含已过期的
    }

    public function testFindByInstitutionAndType(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        // 创建不同类型的资质
        $qual1 = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全资质1',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );
        $qual2 = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全资质2',
            'CERT002',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2026-02-01'),
            ['特种作业培训']
        );
        $qual3 = InstitutionQualification::create(
            $institution,
            '职业技能资质',
            '职业资质1',
            'CERT003',
            '人力资源和社会保障部',
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2026-03-01'),
            ['电工操作']
        );

        $entityManager->persist($qual1);
        $entityManager->persist($qual2);
        $entityManager->persist($qual3);
        $entityManager->flush();

        // 测试按机构和类型查找
        $safetyQuals = $repository->findByInstitutionAndType($institution, '安全培训资质');
        self::assertCount(2, $safetyQuals);
        foreach ($safetyQuals as $qual) {
            self::assertSame('安全培训资质', $qual->getQualificationType());
        }

        $skillQuals = $repository->findByInstitutionAndType($institution, '职业技能资质');
        self::assertCount(1, $skillQuals);
        self::assertSame('职业技能资质', $skillQuals[0]->getQualificationType());
    }

    public function testFindByIssuingAuthority(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        $qual1 = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全资质1',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );
        $qual2 = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全资质2',
            'CERT002',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2023-02-01'),
            new \DateTimeImmutable('2026-02-01'),
            ['特种作业培训']
        );
        $qual3 = InstitutionQualification::create(
            $institution,
            '职业技能资质',
            '职业资质1',
            'CERT003',
            '人力资源和社会保障部',
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2023-03-01'),
            new \DateTimeImmutable('2026-03-01'),
            ['电工操作']
        );

        $entityManager->persist($qual1);
        $entityManager->persist($qual2);
        $entityManager->persist($qual3);
        $entityManager->flush();

        $safetyAuthority = $repository->findByIssuingAuthority('国家安全监管总局');
        self::assertCount(2, $safetyAuthority);

        $hrAuthority = $repository->findByIssuingAuthority('人力资源和社会保障部');
        self::assertCount(1, $hrAuthority);
    }

    public function testFindByValidDateRange(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        $qual1 = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '2024年到期',
            'CERT001',
            '国家安全监管总局',
            new \DateTimeImmutable('2021-01-01'),
            new \DateTimeImmutable('2021-01-01'),
            new \DateTimeImmutable('2024-06-01'),
            ['特种作业培训']
        );
        $qual2 = InstitutionQualification::create(
            $institution,
            '职业技能资质',
            '2025年到期',
            'CERT002',
            '人力资源和社会保障部',
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2025-03-01'),
            ['电工操作']
        );
        $qual3 = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '2026年到期',
            'CERT003',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-12-01'),
            ['特种作业培训']
        );

        $entityManager->persist($qual1);
        $entityManager->persist($qual2);
        $entityManager->persist($qual3);
        $entityManager->flush();

        $results = $repository->findByValidDateRange(
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2025-12-31')
        );

        self::assertCount(2, $results);
    }

    public function testFindExpired(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        $expiredQual = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '已过期资质',
            'CERT001',
            '国家安全监管总局',
            $now->modify('-2 years'),
            $now->modify('-2 years'),
            $now->modify('-1 day'),
            ['特种作业培训']
        );
        $expiredQual->setQualificationStatus('有效');

        $validQual = InstitutionQualification::create(
            $institution,
            '职业技能资质',
            '有效资质',
            'CERT002',
            '人力资源和社会保障部',
            $now->modify('-1 year'),
            $now->modify('-1 year'),
            $now->modify('+1 year'),
            ['电工操作']
        );
        $validQual->setQualificationStatus('有效');

        $entityManager->persist($expiredQual);
        $entityManager->persist($validQual);
        $entityManager->flush();

        $expired = $repository->findExpired();

        self::assertCount(1, $expired);
        self::assertSame('CERT001', $expired[0]->getCertificateNumber());
    }

    public function testFindNeedingRenewalReminder(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        $needsReminder = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '需要提醒',
            'CERT001',
            '国家安全监管总局',
            $now->modify('-1 year'),
            $now->modify('-1 year'),
            $now->modify('+45 days'),
            ['特种作业培训']
        );
        $needsReminder->setQualificationStatus('有效');

        $noReminder = InstitutionQualification::create(
            $institution,
            '职业技能资质',
            '不需要提醒',
            'CERT002',
            '人力资源和社会保障部',
            $now->modify('-1 year'),
            $now->modify('-1 year'),
            $now->modify('+100 days'),
            ['电工操作']
        );
        $noReminder->setQualificationStatus('有效');

        $entityManager->persist($needsReminder);
        $entityManager->persist($noReminder);
        $entityManager->flush();

        $reminders = $repository->findNeedingRenewalReminder(60);

        self::assertCount(1, $reminders);
        self::assertSame('CERT001', $reminders[0]->getCertificateNumber());
    }

    public function testFindPaginated(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        // 创建多个资质
        for ($i = 1; $i <= 25; ++$i) {
            $qual = InstitutionQualification::create(
                $institution,
                '安全培训资质',
                "资质{$i}",
                'CERT' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                '国家安全监管总局',
                new \DateTimeImmutable('2023-01-01'),
                new \DateTimeImmutable('2023-01-01'),
                new \DateTimeImmutable('2026-01-01'),
                ['特种作业培训']
            );
            $entityManager->persist($qual);
        }
        $entityManager->flush();

        // 测试分页
        $result = $repository->findPaginated(1, 10);

        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('total', $result);
        self::assertArrayHasKey('page', $result);
        self::assertArrayHasKey('limit', $result);
        self::assertArrayHasKey('pages', $result);

        $data = $result['data'];
        self::assertIsArray($data);
        self::assertCount(10, $data);
        self::assertSame(25, $result['total']);
        self::assertSame(1, $result['page']);
        self::assertSame(10, $result['limit']);
        self::assertSame(3, $result['pages']);

        // 测试第二页
        $result2 = $repository->findPaginated(2, 10);
        $data2 = $result2['data'];
        self::assertIsArray($data2);
        self::assertCount(10, $data2);

        // 测试第三页
        $result3 = $repository->findPaginated(3, 10);
        $data3 = $result3['data'];
        self::assertIsArray($data3);
        self::assertCount(5, $data3);
    }

    public function testFindValidByInstitution(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        // 有效资质
        $validQual = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '有效资质',
            'CERT001',
            '国家安全监管总局',
            $now->modify('-1 year'),
            $now->modify('-1 year'),
            $now->modify('+1 year'),
            ['特种作业培训']
        );
        $validQual->setQualificationStatus('有效');

        // 已过期资质
        $expiredQual = InstitutionQualification::create(
            $institution,
            '职业技能资质',
            '已过期资质',
            'CERT002',
            '人力资源和社会保障部',
            $now->modify('-2 years'),
            $now->modify('-2 years'),
            $now->modify('-1 day'),
            ['电工操作']
        );
        $expiredQual->setQualificationStatus('有效');

        // 未生效资质
        $futureQual = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '未生效资质',
            'CERT003',
            '国家安全监管总局',
            $now->modify('+1 month'),
            $now->modify('+1 month'),
            $now->modify('+1 year'),
            ['特种作业培训']
        );
        $futureQual->setQualificationStatus('有效');

        $entityManager->persist($validQual);
        $entityManager->persist($expiredQual);
        $entityManager->persist($futureQual);
        $entityManager->flush();

        $validQuals = $repository->findValidByInstitution($institution);

        self::assertCount(1, $validQuals);
        self::assertSame('CERT001', $validQuals[0]->getCertificateNumber());
    }

    public function testFindByCertificateNumber(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        $this->clearExistingData($entityManager);

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

        $qualification = InstitutionQualification::create(
            $institution,
            '安全培训资质',
            '安全资质证书',
            'UNIQUE_CERT_12345',
            '国家安全监管总局',
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2026-01-01'),
            ['特种作业培训']
        );
        $entityManager->persist($qualification);
        $entityManager->flush();

        // 通过证书编号查找
        $found = $repository->findByCertificateNumber('UNIQUE_CERT_12345');
        self::assertInstanceOf(InstitutionQualification::class, $found);
        self::assertSame('UNIQUE_CERT_12345', $found->getCertificateNumber());

        // 查找不存在的证书
        $notFound = $repository->findByCertificateNumber('NON_EXISTENT_CERT');
        self::assertNull($notFound);
    }
}
