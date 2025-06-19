<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

/**
 * 机构变更记录实体
 */
#[ORM\Entity(repositoryClass: 'Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository')]
#[ORM\Table(name: 'train_institution_change_record', options: ['comment' => '表描述'])]
class InstitutionChangeRecord implements Stringable
{
    #[ORM\Id]
#[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '字段说明'])]
    private readonly string $id;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'changeRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private Institution $institution;

#[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '字段说明'])]
    private string $changeType;

#[ORM\Column(type: Types::JSON, options: ['comment' => '字段说明'])]
    private array $changeDetails;

#[ORM\Column(type: Types::JSON, options: ['comment' => '字段说明'])]
    private array $beforeData;

#[ORM\Column(type: Types::JSON, options: ['comment' => '字段说明'])]
    private array $afterData;

#[ORM\Column(type: Types::TEXT, options: ['comment' => '字段说明'])]
    private string $changeReason;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '字段说明'])]
    private \DateTimeImmutable $changeDate;

#[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '字段说明'])]
    private string $changeOperator;

#[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '字段说明'])]
    private string $approvalStatus;

#[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '字段说明'])]
    private ?string $approver;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '字段说明'])]
    private ?\DateTimeImmutable $approvalDate;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '字段说明'])]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->changeDate = new \DateTimeImmutable();
        $this->approvalStatus = '待审批';
        $this->approver = null;
        $this->approvalDate = null;
        $this->createTime = new \DateTimeImmutable();
    }

    public static function create(
        Institution $institution,
        string $changeType,
        array $changeDetails,
        array $beforeData,
        array $afterData,
        string $changeReason,
        string $changeOperator,
        string $approvalStatus = '待审批'
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

    public function getChangeDetails(): array
    {
        return $this->changeDetails;
    }

    public function getBeforeData(): array
    {
        return $this->beforeData;
    }

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
        return (string) $this->id;
    }
} 