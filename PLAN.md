# train-institution-bundle 开发计划

## 1. 功能描述

培训机构管理包，负责安全生产培训机构的全生命周期管理功能。包括机构基本信息管理、机构资质管理、场地设施管理等功能。符合AQ8011-2023培训机构基本条件要求，实现培训机构的规范化管理。

## 2. 完整能力要求

### 2.1 现有能力

- ✅ 基础Bundle结构 - TrainInstitutionBundle类
- ✅ 依赖注入配置 - DependencyInjection支持
- ✅ 资源配置 - Resources目录结构
- ✅ 基础框架搭建完成

### 2.2 需要增强的能力

#### 2.2.1 符合AQ8011-2023机构基本条件

- [ ] 机构注册和审核管理
- [ ] 机构资质证书管理
- [ ] 场地面积和设施配置管理
- [ ] 消防安全设备管理

#### 2.2.2 机构基本信息管理

- [ ] 机构基本信息维护
- [ ] 机构联系方式管理
- [ ] 机构法人信息管理
- [ ] 机构经营范围管理
- [ ] 机构组织架构管理
- [ ] 机构历史变更记录

#### 2.2.3 资质证书管理

- [ ] 办学许可证管理
- [ ] 安全培训资质证书管理
- [ ] 特种作业培训资质管理
- [ ] 证书有效期监控
- [ ] 证书续期提醒
- [ ] 证书变更记录

#### 2.2.4 场地设施管理

- [ ] 培训场地信息管理
- [ ] 场地面积配置管理
- [ ] 教学设施设备管理
- [ ] 安全设施配置管理
- [ ] 设施维护保养管理
- [ ] 设施使用记录管理

## 3. 实体设计

### 3.1 需要新增的实体

#### Institution（培训机构）

```php
class Institution
{
    private string $id;
    private string $institutionName;  // 机构名称
    private string $institutionCode;  // 机构代码
    private string $institutionType;  // 机构类型
    private string $legalPerson;  // 法人代表
    private string $contactPerson;  // 联系人
    private string $contactPhone;  // 联系电话
    private string $contactEmail;  // 联系邮箱
    private string $address;  // 机构地址
    private string $businessScope;  // 经营范围
    private \DateTimeInterface $establishDate;  // 成立日期
    private string $registrationNumber;  // 注册号
    private string $institutionStatus;  // 机构状态
    private array $organizationStructure;  // 组织架构
    private \DateTimeInterface $createTime;
    private \DateTimeInterface $updateTime;
}
```

#### InstitutionQualification（机构资质）

```php
class InstitutionQualification
{
    private string $id;
    private Institution $institution;
    private string $qualificationType;  // 资质类型
    private string $qualificationName;  // 资质名称
    private string $certificateNumber;  // 证书编号
    private string $issuingAuthority;  // 发证机关
    private \DateTimeInterface $issueDate;  // 发证日期
    private \DateTimeInterface $validFrom;  // 有效期开始
    private \DateTimeInterface $validTo;  // 有效期结束
    private array $qualificationScope;  // 资质范围
    private string $qualificationStatus;  // 资质状态
    private array $attachments;  // 附件
    private \DateTimeInterface $createTime;
    private \DateTimeInterface $updateTime;
}
```

#### InstitutionFacility（机构设施）

```php
class InstitutionFacility
{
    private string $id;
    private Institution $institution;
    private string $facilityType;  // 设施类型（教室、实训场地、办公区域）
    private string $facilityName;  // 设施名称
    private string $facilityLocation;  // 设施位置
    private float $facilityArea;  // 设施面积
    private int $capacity;  // 容纳人数
    private array $equipmentList;  // 设备清单
    private array $safetyEquipment;  // 安全设备
    private string $facilityStatus;  // 设施状态
    private \DateTimeInterface $lastInspectionDate;  // 最后检查日期
    private \DateTimeInterface $nextInspectionDate;  // 下次检查日期
    private \DateTimeInterface $createTime;
    private \DateTimeInterface $updateTime;
}
```

#### InstitutionChangeRecord（机构变更记录）

```php
class InstitutionChangeRecord
{
    private string $id;
    private Institution $institution;
    private string $changeType;  // 变更类型
    private array $changeDetails;  // 变更详情
    private array $beforeData;  // 变更前数据
    private array $afterData;  // 变更后数据
    private string $changeReason;  // 变更原因
    private \DateTimeInterface $changeDate;  // 变更日期
    private string $changeOperator;  // 变更操作人
    private string $approvalStatus;  // 审批状态
    private string $approver;  // 审批人
    private \DateTimeInterface $approvalDate;  // 审批日期
    private \DateTimeInterface $createTime;
}
```

## 4. 服务设计

### 4.1 核心服务

#### InstitutionService

```php
class InstitutionService
{
    public function createInstitution(array $institutionData): Institution;
    public function updateInstitution(string $institutionId, array $institutionData): Institution;
    public function getInstitutionById(string $institutionId): ?Institution;
    public function getInstitutionsByStatus(string $status): array;
    public function validateInstitutionData(array $institutionData): array;
    public function changeInstitutionStatus(string $institutionId, string $status, string $reason): Institution;
}
```

#### QualificationService

```php
class QualificationService
{
    public function addQualification(string $institutionId, array $qualificationData): InstitutionQualification;
    public function updateQualification(string $qualificationId, array $qualificationData): InstitutionQualification;
    public function checkQualificationExpiry(string $institutionId): array;
    public function renewQualification(string $qualificationId, array $renewalData): InstitutionQualification;
    public function getExpiringQualifications(int $days): array;
    public function validateQualificationScope(string $qualificationId, array $scope): bool;
}
```

#### FacilityService

```php
class FacilityService
{
    public function addFacility(string $institutionId, array $facilityData): InstitutionFacility;
    public function updateFacility(string $facilityId, array $facilityData): InstitutionFacility;
    public function scheduleFacilityInspection(string $facilityId, \DateTimeInterface $inspectionDate): void;
    public function getFacilityUtilization(string $facilityId): array;
    public function validateFacilityRequirements(string $institutionId): array;
    public function generateFacilityReport(string $institutionId): array;
}
```

#### ChangeRecordService

```php
class ChangeRecordService
{
    public function recordChange(string $institutionId, array $changeData): InstitutionChangeRecord;
    public function approveChange(string $recordId, string $approver): InstitutionChangeRecord;
    public function rejectChange(string $recordId, string $reason): InstitutionChangeRecord;
    public function getChangeHistory(string $institutionId): array;
    public function generateChangeReport(string $institutionId): array;
}
```

## 5. Command设计

### 5.1 机构管理命令

#### InstitutionDataSyncCommand

```php
class InstitutionDataSyncCommand extends Command
{
    protected static $defaultName = 'institution:data:sync';
    
    // 同步机构数据（每日执行）
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### InstitutionStatusCheckCommand

```php
class InstitutionStatusCheckCommand extends Command
{
    protected static $defaultName = 'institution:status:check';
    
    // 检查机构状态
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.2 资质管理命令

#### QualificationExpiryCheckCommand

```php
class QualificationExpiryCheckCommand extends Command
{
    protected static $defaultName = 'institution:qualification:expiry-check';
    
    // 检查资质到期情况（每日执行）
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### QualificationRenewalReminderCommand

```php
class QualificationRenewalReminderCommand extends Command
{
    protected static $defaultName = 'institution:qualification:renewal-reminder';
    
    // 发送资质续期提醒
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.3 设施管理命令

#### FacilityInspectionScheduleCommand

```php
class FacilityInspectionScheduleCommand extends Command
{
    protected static $defaultName = 'institution:facility:inspection-schedule';
    
    // 安排设施检查
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### FacilityMaintenanceReminderCommand

```php
class FacilityMaintenanceReminderCommand extends Command
{
    protected static $defaultName = 'institution:facility:maintenance-reminder';
    
    // 发送设施维护提醒
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.4 报告生成命令

#### InstitutionReportCommand

```php
class InstitutionReportCommand extends Command
{
    protected static $defaultName = 'institution:report:generate';
    
    // 生成机构报告（每月执行）
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

## 6. 依赖包

- `real-name-authentication-bundle` - 实名认证
- `doctrine-entity-checker-bundle` - 实体检查
- `doctrine-timestamp-bundle` - 时间戳管理
- `doctrine-uuid-bundle` - UUID管理

## 7. 测试计划

### 7.1 单元测试

- [ ] Institution实体测试
- [ ] InstitutionQualification实体测试
- [ ] InstitutionService测试
- [ ] QualificationService测试
- [ ] FacilityService测试

### 7.2 集成测试

- [ ] 机构注册流程测试
- [ ] 资质管理流程测试
- [ ] 设施管理流程测试

---

**文档版本**: v1.0
**创建日期**: 2024年12月
**负责人**: 开发团队
