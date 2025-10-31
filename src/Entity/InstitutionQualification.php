<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository;

/**
 * 机构资质实体
 *
 * 管理培训机构的各类资质证书，包括办学许可证、安全培训资质等
 * 支持资质有效期监控和续期提醒功能
 */
#[ORM\Entity(repositoryClass: InstitutionQualificationRepository::class)]
#[ORM\Table(name: 'train_institution_qualification', options: ['comment' => '表描述'])]
class InstitutionQualification implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '资质ID'])]
    #[ORM\CustomIdGenerator]
    #[Assert\Length(max: 36)]
    private readonly string $id;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'qualifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Institution $institution = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '资质类型：办学许可证、安全培训资质、特种作业培训资质等'])]
    #[Assert\Length(max: 100)]
    private string $qualificationType;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '资质名称'])]
    #[Assert\Length(max: 255)]
    private string $qualificationName;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, options: ['comment' => '证书编号'])]
    #[Assert\Length(max: 100)]
    private string $certificateNumber;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '发证机关'])]
    #[Assert\Length(max: 255)]
    private string $issuingAuthority;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '发证日期'])]
    #[Assert\NotNull(message: '发证日期不能为空')]
    #[Assert\LessThanOrEqual(value: 'today', message: '发证日期不能晚于今天')]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '有效期开始日期'])]
    #[Assert\NotNull(message: '有效期开始日期不能为空')]
    #[Assert\GreaterThanOrEqual(propertyPath: 'issueDate', message: '有效期开始日期不能早于发证日期')]
    private \DateTimeImmutable $validFrom;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '有效期结束日期'])]
    #[Assert\NotNull(message: '有效期结束日期不能为空')]
    #[Assert\GreaterThan(propertyPath: 'validFrom', message: '有效期结束日期必须晚于开始日期')]
    private \DateTimeImmutable $validTo;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '资质范围：JSON格式存储资质适用的培训范围'])]
    #[Assert\Type(type: 'array', message: '资质范围必须是数组格式')]
    #[Assert\NotBlank(message: '资质范围不能为空')]
    private array $qualificationScope;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '资质状态：有效、已过期、已撤销、暂停等'])]
    #[Assert\Length(max: 20)]
    private string $qualificationStatus;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '附件信息：JSON格式存储证书扫描件等附件信息'])]
    #[Assert\Type(type: 'array', message: '附件信息必须是数组格式')]
    private array $attachments;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->qualificationScope = [];
        $this->qualificationStatus = '有效';
        $this->attachments = [];
    }

    /**
     * 创建新的机构资质实例
     * @param array<int, string> $qualificationScope
     * @param array<string, mixed> $attachments
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
        array $attachments = [],
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

    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    public function setInstitution(?Institution $institution): void
    {
        $this->institution = $institution;
    }

    public function getQualificationType(): string
    {
        return $this->qualificationType;
    }

    public function setQualificationType(string $qualificationType): void
    {
        $this->qualificationType = $qualificationType;
    }

    public function getQualificationName(): string
    {
        return $this->qualificationName;
    }

    public function setQualificationName(string $qualificationName): void
    {
        $this->qualificationName = $qualificationName;
    }

    public function getCertificateNumber(): string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(string $certificateNumber): void
    {
        $this->certificateNumber = $certificateNumber;
    }

    public function getIssuingAuthority(): string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(string $issuingAuthority): void
    {
        $this->issuingAuthority = $issuingAuthority;
    }

    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeImmutable $issueDate): void
    {
        $this->issueDate = $issueDate;
    }

    public function getValidFrom(): \DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo(): \DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(\DateTimeImmutable $validTo): void
    {
        $this->validTo = $validTo;
    }

    /**
     * @return array<int, string>
     */
    public function getQualificationScope(): array
    {
        return $this->qualificationScope;
    }

    /**
     * @param array<int, string> $qualificationScope
     */
    public function setQualificationScope(array $qualificationScope): void
    {
        $this->qualificationScope = $qualificationScope;
    }

    public function getQualificationStatus(): string
    {
        return $this->qualificationStatus;
    }

    public function setQualificationStatus(string $qualificationStatus): void
    {
        $this->qualificationStatus = $qualificationStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param array<string, mixed> $attachments
     */
    public function setAttachments(array $attachments): void
    {
        $this->attachments = $attachments;
    }

    /**
     * 检查资质是否有效
     */
    public function isValid(): bool
    {
        $now = new \DateTimeImmutable();

        return '有效' === $this->qualificationStatus
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

        $diff = $now->diff($this->validTo);

        return false === $diff->days ? 0 : (int) $diff->days;
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
        if (null !== $newCertificateNumber) {
            $this->certificateNumber = $newCertificateNumber;
        }
        $this->qualificationStatus = '有效';

        return $this;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
