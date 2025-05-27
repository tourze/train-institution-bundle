<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * 培训机构实体
 * 
 * 符合AQ8011-2023培训机构基本条件要求
 * 管理培训机构的基本信息、联系方式、组织架构等
 */
#[ORM\Entity(repositoryClass: 'Tourze\TrainInstitutionBundle\Repository\InstitutionRepository')]
#[ORM\Table(name: 'train_institution')]
#[ORM\HasLifecycleCallbacks]
class Institution
{
    /**
     * 机构ID
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private readonly string $id;

    /**
     * 机构名称
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $institutionName;

    /**
     * 机构代码
     */
    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    private string $institutionCode;

    /**
     * 机构类型
     * 如：企业培训机构、社会培训机构、政府培训机构等
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $institutionType;

    /**
     * 法人代表
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $legalPerson;

    /**
     * 联系人
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $contactPerson;

    /**
     * 联系电话
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $contactPhone;

    /**
     * 联系邮箱
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $contactEmail;

    /**
     * 机构地址
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $address;

    /**
     * 经营范围
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $businessScope;

    /**
     * 成立日期
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $establishDate;

    /**
     * 注册号
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $registrationNumber;

    /**
     * 机构状态
     * 如：待审核、正常运营、暂停营业、注销等
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $institutionStatus;

    /**
     * 组织架构
     * JSON格式存储组织架构信息
     */
    #[ORM\Column(type: Types::JSON)]
    private array $organizationStructure;

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

    /**
     * 机构资质集合
     */
    #[ORM\OneToMany(mappedBy: 'institution', targetEntity: InstitutionQualification::class, cascade: ['persist', 'remove'])]
    private Collection $qualifications;

    /**
     * 机构设施集合
     */
    #[ORM\OneToMany(mappedBy: 'institution', targetEntity: InstitutionFacility::class, cascade: ['persist', 'remove'])]
    private Collection $facilities;

    /**
     * 变更记录集合
     */
    #[ORM\OneToMany(mappedBy: 'institution', targetEntity: InstitutionChangeRecord::class, cascade: ['persist', 'remove'])]
    private Collection $changeRecords;

    public function __construct()
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->institutionStatus = '正常运营';
        $this->organizationStructure = [];
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
        
        $this->qualifications = new ArrayCollection();
        $this->facilities = new ArrayCollection();
        $this->changeRecords = new ArrayCollection();
    }

    /**
     * 创建新的培训机构实例
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
        array $organizationStructure = []
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

    public function setInstitutionName(string $institutionName): self
    {
        $this->institutionName = $institutionName;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getInstitutionCode(): string
    {
        return $this->institutionCode;
    }

    public function setInstitutionCode(string $institutionCode): self
    {
        $this->institutionCode = $institutionCode;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getInstitutionType(): string
    {
        return $this->institutionType;
    }

    public function setInstitutionType(string $institutionType): self
    {
        $this->institutionType = $institutionType;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getLegalPerson(): string
    {
        return $this->legalPerson;
    }

    public function setLegalPerson(string $legalPerson): self
    {
        $this->legalPerson = $legalPerson;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getContactPerson(): string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(string $contactPerson): self
    {
        $this->contactPerson = $contactPerson;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getContactPhone(): string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getBusinessScope(): string
    {
        return $this->businessScope;
    }

    public function setBusinessScope(string $businessScope): self
    {
        $this->businessScope = $businessScope;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getEstablishDate(): \DateTimeImmutable
    {
        return $this->establishDate;
    }

    public function setEstablishDate(\DateTimeImmutable $establishDate): self
    {
        $this->establishDate = $establishDate;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getInstitutionStatus(): string
    {
        return $this->institutionStatus;
    }

    public function setInstitutionStatus(string $institutionStatus): self
    {
        $this->institutionStatus = $institutionStatus;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getOrganizationStructure(): array
    {
        return $this->organizationStructure;
    }

    public function setOrganizationStructure(array $organizationStructure): self
    {
        $this->organizationStructure = $organizationStructure;
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
     * @return Collection<int, InstitutionQualification>
     */
    public function getQualifications(): Collection
    {
        return $this->qualifications;
    }

    public function addQualification(InstitutionQualification $qualification): self
    {
        if (!$this->qualifications->contains($qualification)) {
            $this->qualifications->add($qualification);
        }
        return $this;
    }

    public function removeQualification(InstitutionQualification $qualification): self
    {
        $this->qualifications->removeElement($qualification);
        return $this;
    }

    /**
     * @return Collection<int, InstitutionFacility>
     */
    public function getFacilities(): Collection
    {
        return $this->facilities;
    }

    public function addFacility(InstitutionFacility $facility): self
    {
        if (!$this->facilities->contains($facility)) {
            $this->facilities->add($facility);
        }
        return $this;
    }

    public function removeFacility(InstitutionFacility $facility): self
    {
        $this->facilities->removeElement($facility);
        return $this;
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
     * 检查机构是否符合AQ8011-2023基本条件
     */
    public function checkAQ8011Compliance(): array
    {
        $issues = [];

        // 检查基本信息完整性
        if (empty($this->institutionName)) {
            $issues[] = '机构名称不能为空';
        }
        
        if (empty($this->legalPerson)) {
            $issues[] = '法人代表不能为空';
        }
        
        if (empty($this->contactPhone)) {
            $issues[] = '联系电话不能为空';
        }

        // 检查机构状态
        if ($this->institutionStatus !== '正常运营') {
            $issues[] = '机构状态必须为正常运营';
        }

        return $issues;
    }

    /**
     * 获取有效的资质证书
     */
    public function getValidQualifications(): Collection
    {
        return $this->qualifications->filter(function (InstitutionQualification $qualification) {
            return $qualification->getQualificationStatus() === '有效' 
                && $qualification->getValidTo() > new \DateTimeImmutable();
        });
    }

    /**
     * 获取即将到期的资质证书（30天内）
     */
    public function getExpiringQualifications(): Collection
    {
        $thirtyDaysLater = new \DateTimeImmutable('+30 days');
        
        return $this->qualifications->filter(function (InstitutionQualification $qualification) use ($thirtyDaysLater) {
            return $qualification->getQualificationStatus() === '有效' 
                && $qualification->getValidTo() <= $thirtyDaysLater
                && $qualification->getValidTo() > new \DateTimeImmutable();
        });
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updateTime = new \DateTimeImmutable();
    }
} 