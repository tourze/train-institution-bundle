<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;

/**
 * 机构变更记录实体
 */
#[ORM\Entity(repositoryClass: InstitutionChangeRecordRepository::class)]
#[ORM\Table(name: 'train_institution_change_record', options: ['comment' => '表描述'])]
class InstitutionChangeRecord implements \Stringable
{
    #[ORM\Id]
    #[ORM\CustomIdGenerator]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '记录ID'])]
    #[Assert\Length(max: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'changeRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private Institution $institution;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '变更类型'])]
    #[Assert\Length(max: 50)]
    private string $changeType;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '变更详情'])]
    #[Assert\Type(type: 'array', message: '变更详情必须是数组格式')]
    #[Assert\NotBlank(message: '变更详情不能为空')]
    private array $changeDetails;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '变更前数据'])]
    #[Assert\Type(type: 'array', message: '变更前数据必须是数组格式')]
    private array $beforeData;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '变更后数据'])]
    #[Assert\Type(type: 'array', message: '变更后数据必须是数组格式')]
    private array $afterData;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '变更原因'])]
    #[Assert\NotBlank(message: '变更原因不能为空')]
    #[Assert\Length(max: 500, maxMessage: '变更原因不能超过500个字符')]
    private string $changeReason;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '变更日期'])]
    #[Assert\NotNull(message: '变更日期不能为空')]
    #[Assert\LessThanOrEqual(value: 'now', message: '变更日期不能晚于当前时间')]
    private \DateTimeImmutable $changeDate;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '变更操作人'])]
    #[Assert\Length(max: 100)]
    private string $changeOperator;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '审批状态'])]
    #[Assert\Length(max: 20)]
    private string $approvalStatus;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '审批人'])]
    #[Assert\Length(max: 100)]
    private ?string $approver;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '审批时间'])]
    #[Assert\GreaterThan(propertyPath: 'changeDate', message: '审批时间必须晚于变更日期')]
    private ?\DateTimeImmutable $approvalDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '变更日期'])]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->changeDate = new \DateTimeImmutable();
        $this->approvalStatus = '待审批';
        $this->approver = null;
        $this->approvalDate = null;
        $this->createTime = new \DateTimeImmutable();
        $this->changeDetails = [];
        $this->beforeData = [];
        $this->afterData = [];
    }

    /**
     * @param array<string, mixed> $changeDetails
     * @param array<string, mixed> $beforeData
     * @param array<string, mixed> $afterData
     */
    public static function create(
        Institution $institution,
        string $changeType,
        array $changeDetails,
        array $beforeData,
        array $afterData,
        string $changeReason,
        string $changeOperator,
        string $approvalStatus = '待审批',
    ): self {
        $record = new self();
        $record->institution = $institution;
        $record->changeType = $changeType;
        $record->changeDetails = $changeDetails;
        $record->beforeData = $beforeData;
        $record->afterData = $afterData;
        $record->changeReason = $changeReason;
        $record->changeOperator = $changeOperator;
        $record->approvalStatus = $approvalStatus;

        return $record;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInstitution(): Institution
    {
        return $this->institution;
    }

    public function getChangeType(): string
    {
        return $this->changeType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChangeDetails(): array
    {
        return $this->changeDetails;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBeforeData(): array
    {
        return $this->beforeData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAfterData(): array
    {
        return $this->afterData;
    }

    public function getChangeReason(): string
    {
        return $this->changeReason;
    }

    public function getChangeDate(): \DateTimeImmutable
    {
        return $this->changeDate;
    }

    public function getChangeOperator(): string
    {
        return $this->changeOperator;
    }

    public function getApprovalStatus(): string
    {
        return $this->approvalStatus;
    }

    public function getApprover(): ?string
    {
        return $this->approver;
    }

    public function getApprovalDate(): ?\DateTimeImmutable
    {
        return $this->approvalDate;
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setChangeType(string $changeType): void
    {
        $this->changeType = $changeType;
    }

    /**
     * @param array<string, mixed> $changeDetails
     */
    public function setChangeDetails(array $changeDetails): void
    {
        $this->changeDetails = $changeDetails;
    }

    /**
     * @param array<string, mixed> $beforeData
     */
    public function setBeforeData(array $beforeData): void
    {
        $this->beforeData = $beforeData;
    }

    /**
     * @param array<string, mixed> $afterData
     */
    public function setAfterData(array $afterData): void
    {
        $this->afterData = $afterData;
    }

    public function setChangeReason(string $changeReason): void
    {
        $this->changeReason = $changeReason;
    }

    public function setChangeDate(\DateTimeImmutable $changeDate): void
    {
        $this->changeDate = $changeDate;
    }

    public function setChangeOperator(string $changeOperator): void
    {
        $this->changeOperator = $changeOperator;
    }

    public function setApprovalStatus(string $approvalStatus): void
    {
        $this->approvalStatus = $approvalStatus;
    }

    public function setApprover(?string $approver): void
    {
        $this->approver = $approver;
    }

    public function setApprovalDate(?\DateTimeImmutable $approvalDate): void
    {
        $this->approvalDate = $approvalDate;
    }

    public function approve(string $approver): self
    {
        $this->approvalStatus = '已审批';
        $this->approver = $approver;
        $this->approvalDate = new \DateTimeImmutable();

        return $this;
    }

    public function reject(string $approver): self
    {
        $this->approvalStatus = '已拒绝';
        $this->approver = $approver;
        $this->approvalDate = new \DateTimeImmutable();

        return $this;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
