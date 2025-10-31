# train-institution-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP版本](https://img.shields.io/packagist/php-v/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![许可证](https://img.shields.io/packagist/l/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![最新版本](https://img.shields.io/packagist/v/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![总下载量](https://img.shields.io/packagist/dt/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![构建状态](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?style=flat-square)](
https://github.com/tourze/php-monorepo/actions)
[![代码覆盖率](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](
https://codecov.io/gh/tourze/php-monorepo)

培训机构管理Bundle，提供培训机构信息管理、资质管理、设施管理和变更记录等功能，
符合《安全生产培训机构基本条件》(AQ8011-2023)标准。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [依赖项](#依赖项)
- [配置](#配置)
- [使用方法](#使用方法)
  - [实体模型](#实体模型)
  - [服务使用](#服务使用)
  - [Console Commands](#console-commands)
  - [异常处理](#异常处理)
- [高级用法](#高级用法)
- [安全性](#安全性)
- [测试](#测试)
- [贡献指南](#贡献指南)
- [许可证](#许可证)
- [参考文档](#参考文档)

## 功能特性

- 培训机构信息管理（基本信息、联系方式、经营范围等）
- 资质证书管理（资质类型、有效期、发证机关等）
- 培训设施管理（教室、实训场地、设备等）
- 变更记录管理（机构信息变更、资质变更、设施变更等）
- 自动化任务（资质到期提醒、设施检查安排、状态监控等）
- 完整的异常处理机制

## 安装

```bash
composer require tourze/train-institution-bundle
```

## 依赖项

本 Bundle 需要以下依赖项：

### 必需的 PHP 扩展
- `ext-filter` - 数据过滤
- `ext-json` - JSON处理

### Symfony 组件
- `symfony/config: ^7.3` - 配置管理
- `symfony/console: ^7.3` - 控制台命令支持
- `symfony/dependency-injection: ^7.3` - 服务容器
- `symfony/framework-bundle: ^7.3` - Symfony核心功能
- `symfony/http-kernel: ^7.3` - HTTP内核
- `symfony/routing: ^7.3` - 路由组件
- `symfony/uid: ^7.3` - UID生成
- `symfony/yaml: ^7.3` - YAML处理

### Doctrine 组件
- `doctrine/collections: ^2.3` - 集合工具
- `doctrine/dbal: ^4.0` - 数据库抽象层
- `doctrine/doctrine-bundle: ^2.13` - Doctrine Bundle 集成
- `doctrine/orm: ^3.0` - 对象关系映射
- `doctrine/persistence: ^4.1` - 持久化抽象

### Tourze 组件
- `tourze/bundle-dependency: 0.0.*` - Bundle依赖管理

### 开发依赖项
- `phpstan/phpstan: ^2.1` - 静态分析工具
- `phpunit/phpunit: ^11.5` - 测试框架

## 配置

此Bundle遵循Symfony的零配置原则，安装后即可使用。服务已自动注册到容器中。

## 使用方法

### 实体模型

#### Institution（培训机构）
```php
use Tourze\TrainInstitutionBundle\Entity\Institution;

// 创建培训机构
$institution = Institution::create(
    '北京安全培训中心',
    'BJAQPX001',
    '企业培训机构',
    '张三',
    '李四',
    '13800138000',
    'contact@example.com',
    '北京市朝阳区安全路1号',
    '安全生产培训',
    new \DateTimeImmutable('2020-01-01'),
    'REG123456789'
);
```

#### InstitutionQualification（机构资质）
```php
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

// 创建资质证书
$qualification = InstitutionQualification::create(
    $institution,
    '安全培训资质',
    '安全生产培训机构资质证书',
    'CERT001',
    '应急管理部',
    new \DateTimeImmutable('2023-01-01'),
    new \DateTimeImmutable('2023-01-01'),
    new \DateTimeImmutable('2026-01-01'),
    ['特种作业培训', '安全管理人员培训']
);
```

#### InstitutionFacility（培训设施）
```php
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

// 创建培训设施
$facility = InstitutionFacility::create(
    $institution,
    '教室',
    '多媒体教室A101',
    '教学楼1层101室',
    80.0,
    50,
    ['投影仪', '音响系统', '空调'],
    ['符合消防安全要求', '通风良好']
);
```

#### InstitutionChangeRecord（变更记录）
```php
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;

// 创建变更记录
$changeRecord = InstitutionChangeRecord::create(
    $institution,
    '机构信息变更',
    ['field' => 'institutionName', 'oldValue' => '旧名称', 'newValue' => '新名称'],
    ['institutionName' => '旧名称'],
    ['institutionName' => '新名称'],
    '品牌升级',
    '管理员张三'
);
```

### 服务使用

#### InstitutionService
```php
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

// 注入服务
public function __construct(private InstitutionService $institutionService) {}

// 创建机构
$institution = $this->institutionService->createInstitution([
    'name' => '培训机构名称',
    'code' => 'CODE001',
    'type' => '企业培训机构',
    // ...
]);

// 查找机构
$institution = $this->institutionService->findByCode('CODE001');
$activeInstitutions = $this->institutionService->findActive();

// 更新状态
$this->institutionService->updateStatus($institution, '暂停运营');
```

#### QualificationService
```php
use Tourze\TrainInstitutionBundle\Service\QualificationService;

// 添加资质
$qualification = $this->qualificationService->addQualification($institution, [
    'type' => '安全培训资质',
    'name' => '资质名称',
    'certificateNumber' => 'CERT001',
    // ...
]);

// 检查资质
$hasValid = $this->qualificationService->hasValidQualification($institution, '安全培训资质');
$expiring = $this->qualificationService->findExpiringSoon(30);

// 更新资质状态
$this->qualificationService->updateStatus($qualification, '暂停');
```

#### FacilityService
```php
use Tourze\TrainInstitutionBundle\Service\FacilityService;

// 添加设施
$facility = $this->facilityService->addFacility($institution, [
    'type' => '教室',
    'name' => '教室A101',
    'location' => '教学楼1层',
    'area' => 80.0,
    'capacity' => 50
]);

// 查找设施
$facilities = $this->facilityService->findByType('教室');
$needingInspection = $this->facilityService->findNeedingInspection();

// 安排检查
$this->facilityService->scheduleInspection($facility, new \DateTimeImmutable('+7 days'));
```

#### ChangeRecordService
```php
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;

// 记录变更
$record = $this->changeRecordService->recordChange(
    $institution,
    '机构信息变更',
    ['field' => 'name', 'oldValue' => '旧名称', 'newValue' => '新名称'],
    '品牌升级',
    '管理员'
);

// 审批变更
$this->changeRecordService->approveChange($record, '审批人', '符合要求');
```

## Console Commands

## 资质到期检查
检查即将到期的培训机构资质证书，生成提醒报告。

```bash
# 检查30天内到期的资质（默认）
php bin/console institution:qualification:expiry-check

# 检查60天内到期的资质
php bin/console institution:qualification:expiry-check --days=60

# 以JSON格式输出
php bin/console institution:qualification:expiry-check --format=json

# 干运行模式（不发送通知）
php bin/console institution:qualification:expiry-check --dry-run
```

**建议配置cron任务**：
```bash
# 每天早上9点执行
0 9 * * * cd /path/to/project && php bin/console institution:qualification:expiry-check
```

## 设施检查安排
自动安排需要检查的培训设施，确保设施安全和合规。

```bash
# 自动安排所有需要检查的设施
php bin/console institution:facility:inspection-schedule --auto-schedule

# 设置检查开始日期
php bin/console institution:facility:inspection-schedule --start-date="2024-01-15"

# 设置检查间隔（天）
php bin/console institution:facility:inspection-schedule --interval=14

# 干运行模式
php bin/console institution:facility:inspection-schedule --dry-run
```

**建议配置cron任务**：
```bash
# 每周一上午10点执行
0 10 * * 1 cd /path/to/project && php bin/console institution:facility:inspection-schedule -a
```

## 机构状态检查
检查培训机构的状态和合规性，确保机构符合AQ8011-2023标准。

```bash
# 检查所有机构状态
php bin/console institution:status:check

# 检查特定状态的机构
php bin/console institution:status:check --status="待审核"

# 检查特定机构
php bin/console institution:status:check --institution-id=123

# 只检查合规性问题
php bin/console institution:status:check --compliance-only

# 以JSON格式输出
php bin/console institution:status:check --format=json
```

**建议配置cron任务**：
```bash
# 每天早上8点执行
0 8 * * * cd /path/to/project && php bin/console institution:status:check
```

## 机构报告生成
生成培训机构的综合报告，包括机构状态、资质情况、设施状况等。

```bash
# 生成所有机构的汇总报告
php bin/console institution:report:generate

# 生成特定机构的详细报告
php bin/console institution:report:generate --institution-id=123 --type=detailed

# 生成合规性报告
php bin/console institution:report:generate --type=compliance

# 生成统计报告并输出到文件
php bin/console institution:report:generate --type=statistics --output-file=/tmp/report.csv --format=csv

# 指定日期范围
php bin/console institution:report:generate --date-range="2024-01-01,2024-12-31"
```

**建议配置cron任务**：
```bash
# 每月1号早上6点生成月度报告
0 6 1 * * cd /path/to/project && php bin/console institution:report:generate --type=statistics
```

## 数据同步
同步培训机构数据，确保数据一致性和完整性。

```bash
# 从数据库同步（默认）
php bin/console institution:data:sync

# 从API同步
php bin/console institution:data:sync --source=api

# 从文件同步
php bin/console institution:data:sync --source=file

# 强制同步（覆盖现有数据）
php bin/console institution:data:sync --force

# 设置批处理大小
php bin/console institution:data:sync --batch-size=50

# 干运行模式
php bin/console institution:data:sync --dry-run
```

**建议配置cron任务**：
```bash
# 每天凌晨2点执行
0 2 * * * cd /path/to/project && php bin/console institution:data:sync
```

## 异常处理

本Bundle提供了完整的异常体系：

- `TrainInstitutionException` - 基础异常类
- `InstitutionNotFoundException` - 机构不存在
- `DuplicateInstitutionCodeException` - 机构代码重复
- `QualificationNotFoundException` - 资质不存在
- `QualificationExpiredException` - 资质已过期
- `FacilityNotFoundException` - 设施不存在
- `InvalidInstitutionDataException` - 机构数据无效
- 等等...

```php
try {
    $institution = $this->institutionService->findByCode('INVALID');
} catch (InstitutionNotFoundException $e) {
    // 处理机构不存在的情况
}
```

## 高级用法

## 自定义事件监听器

您可以监听机构相关事件来实现自定义业务逻辑：

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\TrainInstitutionBundle\Event\InstitutionCreatedEvent;

class CustomInstitutionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            InstitutionCreatedEvent::class => 'onInstitutionCreated',
        ];
    }
    
    public function onInstitutionCreated(InstitutionCreatedEvent $event): void
    {
        $institution = $event->getInstitution();
        // 机构创建时的自定义逻辑
    }
}
```

## 扩展实体

您可以扩展基础实体以添加自定义字段：

```php
use Doctrine\ORM\Mapping as ORM;
use Tourze\TrainInstitutionBundle\Entity\Institution as BaseInstitution;

#[ORM\Entity]
class CustomInstitution extends BaseInstitution
{
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $customField = null;
    
    public function getCustomField(): ?string
    {
        return $this->customField;
    }
    
    public function setCustomField(?string $customField): void
    {
        $this->customField = $customField;
    }
}
```

## 自定义验证规则

为机构数据实现自定义验证：

```php
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InstitutionCodeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $value)) {
            $this->context->buildViolation('机构代码格式错误。')
                ->addViolation();
        }
    }
}
```

## 性能优化

### 数据库优化
```sql
-- 添加索引以提高查询性能
CREATE INDEX idx_institution_status ON institution (status);
CREATE INDEX idx_qualification_expiry ON institution_qualification (expiry_date);
CREATE INDEX idx_facility_type ON institution_facility (type);
```

### 缓存配置
```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            institution.cache:
                adapter: cache.adapter.redis
                default_lifetime: 3600
```

## 安全性

## 数据保护

本 Bundle 处理敏感的培训机构数据。请确保：

1. **数据库安全**：使用加密连接和适当的访问控制
2. **输入验证**：所有用户输入都通过 Symfony 的验证组件进行验证
3. **访问控制**：实现适当的身份验证和授权
4. **审计日志**：所有变更都通过变更记录系统进行记录

## 推荐的安全实践

```php
// 使用参数化查询（服务中已实现）
$query = $this->entityManager->createQuery(
    'SELECT i FROM Institution i WHERE i.code = :code'
);
$query->setParameter('code', $institutionCode);

// 验证所有输入
use Symfony\Component\Validator\Validator\ValidatorInterface;

public function createInstitution(array $data, ValidatorInterface $validator): Institution
{
    $violations = $validator->validate($data, $this->getValidationConstraints());
    if (count($violations) > 0) {
        throw new InvalidInstitutionDataException('验证失败');
    }
    // 创建机构...
}
```

## 安全注意事项

- 敏感的资质数据应进行静态加密
- 为API端点实现限流
- 所有通信都使用HTTPS
- 定期对机构数据访问进行安全审计
- 实现适当的备份和灾难恢复程序

## 测试

运行测试：
```bash
# 运行所有测试
./vendor/bin/phpunit packages/train-institution-bundle/tests

# 运行特定测试
./vendor/bin/phpunit packages/train-institution-bundle/tests/Service/InstitutionServiceTest.php
```

## 贡献指南

1. Fork 这个仓库
2. 创建你的功能分支 (`git checkout -b feature/amazing-feature`)
3. 提交你的更改 (`git commit -m 'Add some amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 打开一个 Pull Request

请确保适当地更新测试并遵循编码标准。

## 许可证

MIT

## 参考文档

- [安全生产培训机构基本条件 AQ8011-2023](https://www.mem.gov.cn/)
- [Symfony Bundle文档](https://symfony.com/doc/current/bundles.html)
- [Doctrine ORM文档](https://www.doctrine-project.org/projects/orm.html)
