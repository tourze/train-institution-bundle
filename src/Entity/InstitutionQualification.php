<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * 机构资质实体
 * 
 * 管理培训机构的各类资质证书，包括办学许可证、安全培训资质等
 * 支持资质有效期监控和续期提醒功能
 */
#[ORM\Entity(repositoryClass: 'Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository')]
#[ORM\Table(name: 'train_institution_qualification')]
#[ORM\HasLifecycleCallbacks]
class InstitutionQualification
{
    /**
     * 资质ID
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private readonly string $id;

    /**
     * 所属机构
     */
    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'qualifications')]
    #[ORM\JoinColumn(nullable: false)]
    private Institution $institution;

    /**
     * 资质类型
     * 如：办学许可证、安全培训资质、特种作业培训资质等
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $qualificationType;

    /**
     * 资质名称
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $qualificationName;

    /**
     * 证书编号
     */
    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $certificateNumber;

    /**
     * 发证机关
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $issuingAuthority;

    /**
     * 发证日期
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $issueDate;

    /**
     * 有效期开始日期
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $validFrom;

    /**
     * 有效期结束日期
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $validTo;

    /**
     * 资质范围
     * JSON格式存储资质适用的培训范围
     */
    #[ORM\Column(type: Types::JSON)]
    private array $qualificationScope;

    /**
     * 资质状态
     * 如：有效、已过期、已撤销、暂停等
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $qualificationStatus;

    /**
     * 附件信息
     * JSON格式存储证书扫描件等附件信息
     */
    #[ORM\Column(type: Types::JSON)]
    private array $attachments;

    /**
     * 创建时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createTime;

    /**
     * 更新时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updateTime;

    public function __construct()
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->qualificationScope = [];
        $this->qualificationStatus = '有效';
        $this->attachments = [];
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * 创建新的机构资质实例
     */
    public static function create(
        Institution $institution,
        string $qualificationType,
        string $qualificationName,
        string $certificateNumber,
        string $issuingAuthority,
        \DateTimeImmutable $issueDate,
        \DateTimeImmutable $validFrom,
        \DateTimeImmutable $validTo,
        array $qualificationScope = [],
        string $qualificationStatus = '有效',
        array $attachments = []
    ): self {
        $qualification = new self();
        $qualification->institution = $institution;
        $qualification->qualificationType = $qualificationType;
        $qualification->qualificationName = $qualificationName;
        $qualification->certificateNumber = $certificateNumber;
        $qualification->issuingAuthority = $issuingAuthority;
        $qualification->issueDate = $issueDate;
        $qualification->validFrom = $validFrom;
        $qualification->validTo = $validTo;
        $qualification->qualificationScope = $qualificationScope;
        $qualification->qualificationStatus = $qualificationStatus;
        $qualification->attachments = $attachments;
        
        return $qualification;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInstitution(): Institution
    {
        return $this->institution;
    }

    public function setInstitution(Institution $institution): self
    {
        $this->institution = $institution;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getQualificationType(): string
    {
        return $this->qualificationType;
    }

    public function setQualificationType(string $qualificationType): self
    {
        $this->qualificationType = $qualificationType;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getQualificationName(): string
    {
        return $this->qualificationName;
    }

    public function setQualificationName(string $qualificationName): self
    {
        $this->qualificationName = $qualificationName;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getCertificateNumber(): string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(string $certificateNumber): self
    {
        $this->certificateNumber = $certificateNumber;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getIssuingAuthority(): string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(string $issuingAuthority): self
    {
        $this->issuingAuthority = $issuingAuthority;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeImmutable $issueDate): self
    {
        $this->issueDate = $issueDate;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getValidFrom(): \DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): self
    {
        $this->validFrom = $validFrom;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getValidTo(): \DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(\DateTimeImmutable $validTo): self
    {
        $this->validTo = $validTo;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getQualificationScope(): array
    {
        return $this->qualificationScope;
    }

    public function setQualificationScope(array $qualificationScope): self
    {
        $this->qualificationScope = $qualificationScope;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getQualificationStatus(): string
    {
        return $this->qualificationStatus;
    }

    public function setQualificationStatus(string $qualificationStatus): self
    {
        $this->qualificationStatus = $qualificationStatus;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function getUpdateTime(): \DateTimeImmutable
    {
        return $this->updateTime;
    }

    /**
     * 检查资质是否有效
     */
    public function isValid(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->qualificationStatus === '有效' 
            && $this->validFrom <= $now 
            && $this->validTo > $now;
    }

    /**
     * 检查资质是否即将到期（指定天数内）
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        $futureDate = new \DateTimeImmutable("+{$days} days");
        return $this->isValid() && $this->validTo <= $futureDate;
    }

    /**
     * 获取剩余有效天数
     */
    public function getRemainingDays(): int
    {
        $now = new \DateTimeImmutable();
        if ($this->validTo <= $now) {
            return 0;
        }
        
        return $now->diff($this->validTo)->days;
    }

    /**
     * 检查资质范围是否包含指定的培训类型
     */
    public function coversTrainingType(string $trainingType): bool
    {
        return in_array($trainingType, $this->qualificationScope, true);
    }

    /**
     * 续期资质
     */
    public function renew(\DateTimeImmutable $newValidTo, ?string $newCertificateNumber = null): self
    {
        $this->validTo = $newValidTo;
        if ($newCertificateNumber !== null) {
            $this->certificateNumber = $newCertificateNumber;
        }
        $this->qualificationStatus = '有效';
        $this->updateTime = new \DateTimeImmutable();
        
        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updateTime = new \DateTimeImmutable();
    }
} 