<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;

/**
 * 培训机构实体
 *
 * 符合AQ8011-2023培训机构基本条件要求
 * 管理培训机构的基本信息、联系方式、组织架构等
 */
#[ORM\Entity(repositoryClass: InstitutionRepository::class)]
#[ORM\Table(name: 'train_institution', options: ['comment' => '表描述'])]
class Institution implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\CustomIdGenerator]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '机构ID'])]
    #[Assert\Length(max: 36)]
    private readonly string $id;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '机构名称'])]
    #[Assert\Length(max: 255)]
    private string $institutionName;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, options: ['comment' => '机构代码'])]
    #[Assert\Length(max: 50)]
    private string $institutionCode;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '机构类型：企业培训机构、社会培训机构、政府培训机构等'])]
    #[Assert\Length(max: 50)]
    private string $institutionType;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '法人代表'])]
    #[Assert\Length(max: 100)]
    private string $legalPerson;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '联系人'])]
    #[Assert\Length(max: 100)]
    private string $contactPerson;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '联系电话'])]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^1[3-9]\d{9}$|^\d{3,4}-\d{7,8}$/', message: '联系电话格式不正确')]
    private string $contactPhone;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '联系邮箱'])]
    #[Assert\Length(max: 255)]
    #[Assert\Email]
    private string $contactEmail;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '机构地址'])]
    #[Assert\NotBlank(message: '机构地址不能为空')]
    #[Assert\Length(max: 500, maxMessage: '机构地址不能超过500个字符')]
    private string $address;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '经营范围'])]
    #[Assert\NotBlank(message: '经营范围不能为空')]
    #[Assert\Length(max: 1000, maxMessage: '经营范围不能超过1000个字符')]
    private string $businessScope;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '成立日期'])]
    #[Assert\NotNull(message: '成立日期不能为空')]
    #[Assert\LessThanOrEqual(value: 'today', message: '成立日期不能晚于今天')]
    private \DateTimeImmutable $establishDate;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '注册号'])]
    #[Assert\Length(max: 100)]
    private string $registrationNumber;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '机构状态：待审核、正常运营、暂停营业、注销等'])]
    #[Assert\Length(max: 20)]
    private string $institutionStatus;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '组织架构（JSON格式）'])]
    #[Assert\Type(type: 'array', message: '组织架构必须是数组格式')]
    private array $organizationStructure;

    /**
     * 机构资质集合
     * @var Collection<int, InstitutionQualification>
     */
    #[ORM\OneToMany(mappedBy: 'institution', targetEntity: InstitutionQualification::class, cascade: ['persist', 'remove'])]
    private Collection $qualifications;

    /**
     * 机构设施集合
     * @var Collection<int, InstitutionFacility>
     */
    #[ORM\OneToMany(mappedBy: 'institution', targetEntity: InstitutionFacility::class, cascade: ['persist', 'remove'])]
    private Collection $facilities;

    /**
     * 变更记录集合
     * @var Collection<int, InstitutionChangeRecord>
     */
    #[ORM\OneToMany(mappedBy: 'institution', targetEntity: InstitutionChangeRecord::class, cascade: ['persist', 'remove'])]
    private Collection $changeRecords;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->institutionStatus = '正常运营';
        $this->organizationStructure = [];

        $this->qualifications = new ArrayCollection();
        $this->facilities = new ArrayCollection();
        $this->changeRecords = new ArrayCollection();
    }

    /**
     * 创建新的培训机构实例
     * @param array<string, mixed> $organizationStructure
     */
    public static function create(
        string $institutionName,
        string $institutionCode,
        string $institutionType,
        string $legalPerson,
        string $contactPerson,
        string $contactPhone,
        string $contactEmail,
        string $address,
        string $businessScope,
        \DateTimeImmutable $establishDate,
        string $registrationNumber,
        string $institutionStatus = '待审核',
        /** @var array<string, mixed> */
        array $organizationStructure = [],
    ): self {
        $institution = new self();
        $institution->institutionName = $institutionName;
        $institution->institutionCode = $institutionCode;
        $institution->institutionType = $institutionType;
        $institution->legalPerson = $legalPerson;
        $institution->contactPerson = $contactPerson;
        $institution->contactPhone = $contactPhone;
        $institution->contactEmail = $contactEmail;
        $institution->address = $address;
        $institution->businessScope = $businessScope;
        $institution->establishDate = $establishDate;
        $institution->registrationNumber = $registrationNumber;
        $institution->institutionStatus = $institutionStatus;
        $institution->organizationStructure = $organizationStructure;

        return $institution;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInstitutionName(): string
    {
        return $this->institutionName;
    }

    public function setInstitutionName(string $institutionName): void
    {
        $this->institutionName = $institutionName;
    }

    public function getInstitutionCode(): string
    {
        return $this->institutionCode;
    }

    public function setInstitutionCode(string $institutionCode): void
    {
        $this->institutionCode = $institutionCode;
    }

    public function getInstitutionType(): string
    {
        return $this->institutionType;
    }

    public function setInstitutionType(string $institutionType): void
    {
        $this->institutionType = $institutionType;
    }

    public function getLegalPerson(): string
    {
        return $this->legalPerson;
    }

    public function setLegalPerson(string $legalPerson): void
    {
        $this->legalPerson = $legalPerson;
    }

    public function getContactPerson(): string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(string $contactPerson): void
    {
        $this->contactPerson = $contactPerson;
    }

    public function getContactPhone(): string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getBusinessScope(): string
    {
        return $this->businessScope;
    }

    public function setBusinessScope(string $businessScope): void
    {
        $this->businessScope = $businessScope;
    }

    public function getEstablishDate(): \DateTimeImmutable
    {
        return $this->establishDate;
    }

    public function setEstablishDate(\DateTimeImmutable $establishDate): void
    {
        $this->establishDate = $establishDate;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): void
    {
        $this->registrationNumber = $registrationNumber;
    }

    public function getInstitutionStatus(): string
    {
        return $this->institutionStatus;
    }

    public function setInstitutionStatus(string $institutionStatus): void
    {
        $this->institutionStatus = $institutionStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrganizationStructure(): array
    {
        return $this->organizationStructure;
    }

    /**
     * @param array<string, mixed> $organizationStructure
     */
    public function setOrganizationStructure(array $organizationStructure): void
    {
        $this->organizationStructure = $organizationStructure;
    }

    /**
     * @return Collection<int, InstitutionQualification>
     */
    public function getQualifications(): Collection
    {
        return $this->qualifications;
    }

    public function addQualification(InstitutionQualification $qualification): void
    {
        if (!$this->qualifications->contains($qualification)) {
            $this->qualifications->add($qualification);
        }
    }

    public function removeQualification(InstitutionQualification $qualification): self
    {
        $this->qualifications->removeElement($qualification);

        return $this;
    }

    /**
     * @param Collection<int, InstitutionQualification>|null $qualifications
     */
    public function setQualifications(?Collection $qualifications): void
    {
        // 为了测试兼容性，如果传入 null，清空集合而不是设置为 null
        if (null === $qualifications) {
            $this->qualifications->clear();
        } else {
            $this->qualifications = $qualifications;
        }
    }

    /**
     * @return Collection<int, InstitutionFacility>
     */
    public function getFacilities(): Collection
    {
        return $this->facilities;
    }

    public function addFacility(InstitutionFacility $facility): void
    {
        if (!$this->facilities->contains($facility)) {
            $this->facilities->add($facility);
        }
    }

    public function removeFacility(InstitutionFacility $facility): self
    {
        $this->facilities->removeElement($facility);

        return $this;
    }

    /**
     * @param Collection<int, InstitutionFacility>|null $facilities
     */
    public function setFacilities(?Collection $facilities): void
    {
        // 为了测试兼容性，如果传入 null，清空集合而不是设置为 null
        if (null === $facilities) {
            $this->facilities->clear();
        } else {
            $this->facilities = $facilities;
        }
    }

    /**
     * @return Collection<int, InstitutionChangeRecord>
     */
    public function getChangeRecords(): Collection
    {
        return $this->changeRecords;
    }

    public function addChangeRecord(InstitutionChangeRecord $changeRecord): self
    {
        if (!$this->changeRecords->contains($changeRecord)) {
            $this->changeRecords->add($changeRecord);
        }

        return $this;
    }

    public function removeChangeRecord(InstitutionChangeRecord $changeRecord): self
    {
        $this->changeRecords->removeElement($changeRecord);

        return $this;
    }

    /**
     * @param Collection<int, InstitutionChangeRecord>|null $changeRecords
     */
    public function setChangeRecords(?Collection $changeRecords): void
    {
        // 为了测试兼容性，如果传入 null，清空集合而不是设置为 null
        if (null === $changeRecords) {
            $this->changeRecords->clear();
        } else {
            $this->changeRecords = $changeRecords;
        }
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
