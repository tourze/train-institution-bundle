<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;

/**
 * 机构设施实体
 *
 * 管理培训机构的场地设施信息，包括教室、实训场地、办公区域等
 * 符合AQ8011-2023对培训场地和设施的要求
 */
#[ORM\Entity(repositoryClass: InstitutionFacilityRepository::class)]
#[ORM\Table(name: 'train_institution_facility', options: ['comment' => '表描述'])]
class InstitutionFacility implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\CustomIdGenerator]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '设施ID'])]
    #[Assert\Length(max: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'facilities')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Institution $institution = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '设施类型：教室、实训场地、办公区域、会议室、图书馆等'])]
    #[Assert\Length(max: 50)]
    private string $facilityType;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '设施名称'])]
    #[Assert\Length(max: 255)]
    private string $facilityName;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '设施位置：详细的位置描述，如楼层、房间号等'])]
    #[Assert\Length(max: 255)]
    private string $facilityLocation;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '设施面积（平方米）'])]
    #[Assert\GreaterThanOrEqual(value: 0.0, message: '设施面积不能为负数')]
    #[Assert\NotNull(message: '设施面积不能为空')]
    private float $facilityArea;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '容纳人数'])]
    #[Assert\GreaterThan(value: 0, message: '容纳人数必须大于0')]
    #[Assert\NotNull(message: '容纳人数不能为空')]
    private int $capacity;

    /**
     * @var array<int|string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '设备清单：JSON格式存储设施内的设备信息'])]
    #[Assert\Type(type: 'array', message: '设备清单必须是数组格式')]
    private array $equipmentList;

    /**
     * @var array<int|string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '安全设备：JSON格式存储消防、安全等设备信息'])]
    #[Assert\Type(type: 'array', message: '安全设备必须是数组格式')]
    private array $safetyEquipment;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '设施状态：正常使用、维修中、停用、待检查等'])]
    #[Assert\Length(max: 20)]
    private string $facilityStatus;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '最后检查日期'])]
    #[Assert\LessThanOrEqual(value: 'today', message: '最后检查日期不能晚于今天')]
    private ?\DateTimeImmutable $lastInspectionDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '下次检查日期'])]
    #[Assert\GreaterThan(propertyPath: 'lastInspectionDate', message: '下次检查日期必须晚于最后检查日期')]
    private ?\DateTimeImmutable $nextInspectionDate;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->equipmentList = [];
        $this->safetyEquipment = [];
        $this->facilityStatus = '正常使用';
        $this->lastInspectionDate = null;
        $this->nextInspectionDate = null;
    }

    /**
     * 创建新的机构设施实例
     * @param array<int|string, mixed> $equipmentList
     * @param array<int|string, mixed> $safetyEquipment
     */
    public static function create(
        Institution $institution,
        string $facilityType,
        string $facilityName,
        string $facilityLocation,
        float $facilityArea,
        int $capacity,
        array $equipmentList = [],
        array $safetyEquipment = [],
        string $facilityStatus = '正常使用',
    ): self {
        $facility = new self();
        $facility->institution = $institution;
        $facility->facilityType = $facilityType;
        $facility->facilityName = $facilityName;
        $facility->facilityLocation = $facilityLocation;
        $facility->facilityArea = $facilityArea;
        $facility->capacity = $capacity;
        $facility->equipmentList = $equipmentList;
        $facility->safetyEquipment = $safetyEquipment;
        $facility->facilityStatus = $facilityStatus;

        return $facility;
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

    public function getFacilityType(): string
    {
        return $this->facilityType;
    }

    public function setFacilityType(string $facilityType): void
    {
        $this->facilityType = $facilityType;
    }

    public function getFacilityName(): string
    {
        return $this->facilityName;
    }

    public function setFacilityName(string $facilityName): void
    {
        $this->facilityName = $facilityName;
    }

    public function getFacilityLocation(): string
    {
        return $this->facilityLocation;
    }

    public function setFacilityLocation(string $facilityLocation): void
    {
        $this->facilityLocation = $facilityLocation;
    }

    public function getFacilityArea(): float
    {
        return $this->facilityArea;
    }

    public function setFacilityArea(float $facilityArea): void
    {
        $this->facilityArea = $facilityArea;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): void
    {
        $this->capacity = $capacity;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getEquipmentList(): array
    {
        return $this->equipmentList;
    }

    /**
     * @param array<int|string, mixed> $equipmentList
     */
    public function setEquipmentList(array $equipmentList): void
    {
        $this->equipmentList = $equipmentList;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getSafetyEquipment(): array
    {
        return $this->safetyEquipment;
    }

    /**
     * @param array<int|string, mixed> $safetyEquipment
     */
    public function setSafetyEquipment(array $safetyEquipment): void
    {
        $this->safetyEquipment = $safetyEquipment;
    }

    public function getFacilityStatus(): string
    {
        return $this->facilityStatus;
    }

    public function setFacilityStatus(string $facilityStatus): void
    {
        $this->facilityStatus = $facilityStatus;
    }

    public function getLastInspectionDate(): ?\DateTimeImmutable
    {
        return $this->lastInspectionDate;
    }

    public function setLastInspectionDate(?\DateTimeImmutable $lastInspectionDate): void
    {
        $this->lastInspectionDate = $lastInspectionDate;
    }

    public function getNextInspectionDate(): ?\DateTimeImmutable
    {
        return $this->nextInspectionDate;
    }

    public function setNextInspectionDate(?\DateTimeImmutable $nextInspectionDate): void
    {
        $this->nextInspectionDate = $nextInspectionDate;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
