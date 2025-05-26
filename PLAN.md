# train-institution-bundle 开发计划

## 1. 功能描述

培训机构管理包，负责安全生产培训机构的全生命周期管理功能。包括机构基本信息管理、机构资质管理、场地设施管理、应急预案管理、制度建设管理、机构评估和认证等功能。符合AQ8011-2023培训机构基本条件要求，实现培训机构的规范化管理。

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
- [ ] 应急预案编制和演练管理
- [ ] 培训制度建立和维护

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

#### 2.2.5 应急预案管理

- [ ] 应急预案编制管理
- [ ] 应急演练计划管理
- [ ] 应急演练记录管理
- [ ] 应急设备配置管理
- [ ] 应急联系人管理
- [ ] 应急响应流程管理

#### 2.2.6 制度建设管理

- [ ] 培训制度建立
- [ ] 安全管理制度
- [ ] 质量管理制度
- [ ] 档案管理制度
- [ ] 制度执行监督
- [ ] 制度更新维护

#### 2.2.7 机构评估认证

- [ ] 机构能力评估
- [ ] 机构资质认证
- [ ] 机构等级评定
- [ ] 评估结果管理
- [ ] 认证证书管理
- [ ] 持续改进管理

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

#### EmergencyPlan（应急预案）

```php
class EmergencyPlan
{
    private string $id;
    private Institution $institution;
    private string $planType;  // 预案类型
    private string $planName;  // 预案名称
    private string $planVersion;  // 预案版本
    private array $emergencyScenarios;  // 应急场景
    private array $responseSteps;  // 应急步骤
    private array $emergencyContacts;  // 应急联系人
    private array $emergencyEquipment;  // 应急设备
    private string $planStatus;  // 预案状态
    private \DateTimeInterface $approvalDate;  // 批准日期
    private \DateTimeInterface $effectiveDate;  // 生效日期
    private \DateTimeInterface $reviewDate;  // 复审日期
    private \DateTimeInterface $createTime;
    private \DateTimeInterface $updateTime;
}
```

#### EmergencyDrill（应急演练）

```php
class EmergencyDrill
{
    private string $id;
    private EmergencyPlan $emergencyPlan;
    private string $drillName;  // 演练名称
    private string $drillType;  // 演练类型
    private \DateTimeInterface $drillDate;  // 演练日期
    private array $participants;  // 参与人员
    private array $drillScenarios;  // 演练场景
    private array $drillResults;  // 演练结果
    private array $foundProblems;  // 发现问题
    private array $improvementMeasures;  // 改进措施
    private string $drillStatus;  // 演练状态
    private string $drillEvaluator;  // 演练评估人
    private \DateTimeInterface $createTime;
}
```

#### InstitutionPolicy（机构制度）

```php
class InstitutionPolicy
{
    private string $id;
    private Institution $institution;
    private string $policyType;  // 制度类型
    private string $policyName;  // 制度名称
    private string $policyVersion;  // 制度版本
    private string $policyContent;  // 制度内容
    private array $applicableScope;  // 适用范围
    private string $policyStatus;  // 制度状态
    private \DateTimeInterface $approvalDate;  // 批准日期
    private \DateTimeInterface $effectiveDate;  // 生效日期
    private \DateTimeInterface $reviewDate;  // 复审日期
    private string $approver;  // 批准人
    private \DateTimeInterface $createTime;
    private \DateTimeInterface $updateTime;
}
```

#### InstitutionAssessment（机构评估）

```php
class InstitutionAssessment
{
    private string $id;
    private Institution $institution;
    private string $assessmentType;  // 评估类型
    private string $assessmentStandard;  // 评估标准
    private \DateTimeInterface $assessmentDate;  // 评估日期
    private array $assessmentItems;  // 评估项目
    private array $assessmentScores;  // 评估分数
    private float $totalScore;  // 总分
    private string $assessmentLevel;  // 评估等级
    private array $assessmentComments;  // 评估意见
    private array $improvementSuggestions;  // 改进建议
    private string $assessor;  // 评估人
    private string $assessmentStatus;  // 评估状态
    private \DateTimeInterface $createTime;
}
```

#### InstitutionCertification（机构认证）

```php
class InstitutionCertification
{
    private string $id;
    private Institution $institution;
    private InstitutionAssessment $assessment;
    private string $certificationType;  // 认证类型
    private string $certificationLevel;  // 认证等级
    private string $certificateNumber;  // 证书编号
    private \DateTimeInterface $certificationDate;  // 认证日期
    private \DateTimeInterface $validFrom;  // 有效期开始
    private \DateTimeInterface $validTo;  // 有效期结束
    private array $certificationScope;  // 认证范围
    private string $certificationStatus;  // 认证状态
    private string $certifyingBody;  // 认证机构
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

#### EmergencyPlanService

```php
class EmergencyPlanService
{
    public function createEmergencyPlan(string $institutionId, array $planData): EmergencyPlan;
    public function updateEmergencyPlan(string $planId, array $planData): EmergencyPlan;
    public function approveEmergencyPlan(string $planId, string $approver): EmergencyPlan;
    public function scheduleEmergencyDrill(string $planId, array $drillData): EmergencyDrill;
    public function conductEmergencyDrill(string $drillId, array $drillResults): EmergencyDrill;
    public function getEmergencyPlanEffectiveness(string $planId): array;
}
```

#### PolicyService

```php
class PolicyService
{
    public function createPolicy(string $institutionId, array $policyData): InstitutionPolicy;
    public function updatePolicy(string $policyId, array $policyData): InstitutionPolicy;
    public function approvePolicy(string $policyId, string $approver): InstitutionPolicy;
    public function schedulePolicy Review(string $policyId, \DateTimeInterface $reviewDate): void;
    public function getPolicyCompliance(string $institutionId): array;
    public function generatePolicyReport(string $institutionId): array;
}
```

#### AssessmentService

```php
class AssessmentService
{
    public function conductAssessment(string $institutionId, array $assessmentData): InstitutionAssessment;
    public function calculateAssessmentScore(string $assessmentId): float;
    public function determineAssessmentLevel(float $score): string;
    public function generateAssessmentReport(string $assessmentId): array;
    public function getAssessmentHistory(string $institutionId): array;
    public function compareAssessmentResults(array $assessmentIds): array;
}
```

#### CertificationService

```php
class CertificationService
{
    public function issueCertification(string $assessmentId, array $certificationData): InstitutionCertification;
    public function renewCertification(string $certificationId, array $renewalData): InstitutionCertification;
    public function revokeCertification(string $certificationId, string $reason): InstitutionCertification;
    public function validateCertification(string $certificationId): bool;
    public function getExpiringCertifications(int $days): array;
    public function generateCertificationReport(string $institutionId): array;
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

### 5.4 应急管理命令

#### EmergencyDrillScheduleCommand

```php
class EmergencyDrillScheduleCommand extends Command
{
    protected static $defaultName = 'institution:emergency:drill-schedule';
    
    // 安排应急演练
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### EmergencyPlanReviewCommand

```php
class EmergencyPlanReviewCommand extends Command
{
    protected static $defaultName = 'institution:emergency:plan-review';
    
    // 应急预案复审提醒
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.5 评估认证命令

#### AssessmentScheduleCommand

```php
class AssessmentScheduleCommand extends Command
{
    protected static $defaultName = 'institution:assessment:schedule';
    
    // 安排机构评估
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### CertificationExpiryCheckCommand

```php
class CertificationExpiryCheckCommand extends Command
{
    protected static $defaultName = 'institution:certification:expiry-check';
    
    // 检查认证到期情况
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.6 报告生成命令

#### InstitutionReportCommand

```php
class InstitutionReportCommand extends Command
{
    protected static $defaultName = 'institution:report:generate';
    
    // 生成机构报告（每月执行）
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### ComplianceReportCommand

```php
class ComplianceReportCommand extends Command
{
    protected static $defaultName = 'institution:compliance:report';
    
    // 生成合规报告
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

## 6. 配置和集成

### 6.1 Bundle配置

```yaml
# config/packages/train_institution.yaml
train_institution:
    institution:
        auto_approval: false  # 自动审批
        status_check_interval: 86400  # 状态检查间隔（秒）
        data_sync_enabled: true
        
    qualification:
        expiry_warning_days: [90, 30, 7]  # 到期提醒天数
        auto_renewal_enabled: false
        qualification_types:
            - training_license
            - safety_training_qualification
            - special_operation_training
            
    facility:
        inspection_frequency: 'quarterly'  # 检查频率
        maintenance_reminder_days: 7
        capacity_utilization_threshold: 0.8
        facility_types:
            - classroom
            - training_ground
            - office_area
            - safety_equipment_room
            
    emergency:
        drill_frequency: 'quarterly'  # 演练频率
        plan_review_frequency: 'annually'  # 预案复审频率
        drill_participation_threshold: 0.9
        
    assessment:
        assessment_frequency: 'annually'  # 评估频率
        scoring_system: 'percentage'
        pass_threshold: 80
        assessment_levels:
            - excellent  # 优秀 (90-100)
            - good      # 良好 (80-89)
            - qualified # 合格 (70-79)
            - unqualified # 不合格 (<70)
            
    certification:
        certification_validity_years: 3
        renewal_grace_period_days: 30
        auto_revocation_enabled: true
        
    policy:
        review_frequency: 'annually'
        approval_required: true
        version_control: true
        
    notifications:
        enabled: true
        email_notifications: true
        sms_notifications: false
        notification_types:
            - qualification_expiry
            - facility_inspection_due
            - emergency_drill_scheduled
            - assessment_scheduled
            - certification_expiry
            
    reporting:
        auto_generation: true
        report_formats: ['pdf', 'excel']
        report_retention_months: 60
        
    cache:
        enabled: true
        ttl: 3600  # 1小时
        qualification_ttl: 86400  # 24小时
```

### 6.2 依赖包

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
- [ ] 应急管理流程测试
- [ ] 评估认证流程测试

### 7.3 性能测试

- [ ] 大量机构数据处理测试
- [ ] 资质到期检查性能测试
- [ ] 报告生成性能测试

## 8. 部署和运维

### 8.1 部署要求

- PHP 8.2+
- MySQL 8.0+ / PostgreSQL 14+
- Redis（缓存）
- 足够的存储空间（文档和报告）
- 定时任务支持

### 8.2 监控指标

- 机构注册成功率
- 资质到期预警率
- 设施检查完成率
- 应急演练参与率
- 评估认证通过率

### 8.3 安全要求

- [ ] 机构数据访问控制
- [ ] 敏感信息加密存储
- [ ] 操作审计日志
- [ ] 文档权限管理

---

**文档版本**: v1.0
**创建日期**: 2024年12月
**负责人**: 开发团队
