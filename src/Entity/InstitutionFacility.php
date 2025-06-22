<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository;

/**
 * 机构设施实体
 * 
 * 管理培训机构的场地设施信息，包括教室、实训场地、办公区域等
 * 符合AQ8011-2023对培训场地和设施的要求
 */
#[ORM\Entity(repositoryClass: InstitutionFacilityRepository::class)]
#[ORM\Table(name: 'train_institution_facility', options: ['comment' => '表描述'])]
class InstitutionFacility implements Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '设施ID'])]
    private readonly string $id;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'facilities')]
    #[ORM\JoinColumn(nullable: false)]
    private Institution $institution;

        #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '设施类型：教室、实训场地、办公区域、会议室、图书馆等'])]
    private string $facilityType;

        #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '设施名称'])]
    private string $facilityName;

        #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '设施位置：详细的位置描述，如楼层、房间号等'])]
    private string $facilityLocation;

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '设施面积（平方米）'])]
    private float $facilityArea;

        #[ORM\Column(type: Types::INTEGER, options: ['comment' => '容纳人数'])]
    private int $capacity;

        #[ORM\Column(type: Types::JSON, options: ['comment' => '设备清单：JSON格式存储设施内的设备信息'])]
    private array $equipmentList;

        #[ORM\Column(type: Types::JSON, options: ['comment' => '安全设备：JSON格式存储消防、安全等设备信息'])]
    private array $safetyEquipment;

        #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '设施状态：正常使用、维修中、停用、待检查等'])]
    private string $facilityStatus;

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '最后检查日期'])]
    private ?\DateTimeImmutable $lastInspectionDate;

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '下次检查日期'])]
    private ?\DateTimeImmutable $nextInspectionDate;

        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createTime;

        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    private \DateTimeImmutable $updateTime;

    public function __construct()
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->equipmentList = [];
        $this->safetyEquipment = [];
        $this->facilityStatus = '正常使用';
        $this->lastInspectionDate = null;
        $this->nextInspectionDate = null;
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * 创建新的机构设施实例
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
        string $facilityStatus = '正常使用'
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

    public function getFacilityType(): string
    {
        return $this->facilityType;
    }

    public function setFacilityType(string $facilityType): self
    {
        $this->facilityType = $facilityType;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getFacilityName(): string
    {
        return $this->facilityName;
    }

    public function setFacilityName(string $facilityName): self
    {
        $this->facilityName = $facilityName;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getFacilityLocation(): string
    {
        return $this->facilityLocation;
    }

    public function setFacilityLocation(string $facilityLocation): self
    {
        $this->facilityLocation = $facilityLocation;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getFacilityArea(): float
    {
        return $this->facilityArea;
    }

    public function setFacilityArea(float $facilityArea): self
    {
        $this->facilityArea = $facilityArea;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getEquipmentList(): array
    {
        return $this->equipmentList;
    }

    public function setEquipmentList(array $equipmentList): self
    {
        $this->equipmentList = $equipmentList;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getSafetyEquipment(): array
    {
        return $this->safetyEquipment;
    }

    public function setSafetyEquipment(array $safetyEquipment): self
    {
        $this->safetyEquipment = $safetyEquipment;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getFacilityStatus(): string
    {
        return $this->facilityStatus;
    }

    public function setFacilityStatus(string $facilityStatus): self
    {
        $this->facilityStatus = $facilityStatus;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getLastInspectionDate(): ?\DateTimeImmutable
    {
        return $this->lastInspectionDate;
    }

    public function setLastInspectionDate(?\DateTimeImmutable $lastInspectionDate): self
    {
        $this->lastInspectionDate = $lastInspectionDate;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function getNextInspectionDate(): ?\DateTimeImmutable
    {
        return $this->nextInspectionDate;
    }

    public function setNextInspectionDate(?\DateTimeImmutable $nextInspectionDate): self
    {
        $this->nextInspectionDate = $nextInspectionDate;
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
     * 检查设施是否符合AQ8011-2023要求
     */
    public function checkAQ8011Compliance(): array
    {
        $issues = [];

        // 检查面积要求（根据设施类型）
        $minAreaRequirements = [
            '教室' => 50.0,  // 最小50平方米
            '实训场地' => 100.0,  // 最小100平方米
            '办公区域' => 20.0,   // 最小20平方米
        ];

        if (isset($minAreaRequirements[$this->facilityType])) {
            $minArea = $minAreaRequirements[$this->facilityType];
            if ($this->facilityArea < $minArea) {
                $issues[] = "设施面积不足，最小要求{$minArea}平方米，当前{$this->facilityArea}平方米";
            }
        }

        // 检查人均面积（教室和实训场地）
        if (in_array($this->facilityType, ['教室', '实训场地'], true)) {
            $areaPerPerson = $this->facilityArea / $this->capacity;
            $minAreaPerPerson = $this->facilityType === '教室' ? 1.5 : 2.0;
            
            if ($areaPerPerson < $minAreaPerPerson) {
                $issues[] = "人均面积不足，要求{$minAreaPerPerson}平方米/人，当前{$areaPerPerson}平方米/人";
            }
        }

        // 检查安全设备
        $requiredSafetyEquipment = ['灭火器', '烟雾报警器', '应急照明'];
        foreach ($requiredSafetyEquipment as $equipment) {
            if (!$this->hasSafetyEquipment($equipment)) {
                $issues[] = "缺少必要的安全设备：{$equipment}";
            }
        }

        // 检查设施状态
        if ($this->facilityStatus !== '正常使用') {
            $issues[] = "设施状态异常：{$this->facilityStatus}";
        }

        return $issues;
    }

    /**
     * 检查是否有指定的安全设备
     */
    public function hasSafetyEquipment(string $equipmentName): bool
    {
        foreach ($this->safetyEquipment as $equipment) {
            if (is_array($equipment) && isset($equipment['name'])) {
                if ($equipment['name'] === $equipmentName) {
                    return true;
                }
            } elseif (is_string($equipment) && $equipment === $equipmentName) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查是否需要检查
     */
    public function needsInspection(): bool
    {
        if ($this->nextInspectionDate === null) {
            return true;
        }
        
        return $this->nextInspectionDate <= new \DateTimeImmutable();
    }

    /**
     * 计算设施利用率（需要传入使用记录）
     */
    public function calculateUtilizationRate(array $usageRecords): float
    {
        // 这里可以根据使用记录计算利用率
        // 简化实现，实际应该根据具体的使用记录数据结构来计算
        return 0.0;
    }

    /**
     * 添加设备
     */
    public function addEquipment(array $equipment): self
    {
        $this->equipmentList[] = $equipment;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    /**
     * 添加安全设备
     */
    public function addSafetyEquipment(array $equipment): self
    {
        $this->safetyEquipment[] = $equipment;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    /**
     * 安排检查
     */
    public function scheduleInspection(\DateTimeImmutable $inspectionDate): self
    {
        $this->nextInspectionDate = $inspectionDate;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    /**
     * 完成检查
     */
    public function completeInspection(\DateTimeImmutable $inspectionDate, \DateTimeImmutable $nextInspectionDate): self
    {
        $this->lastInspectionDate = $inspectionDate;
        $this->nextInspectionDate = $nextInspectionDate;
        $this->updateTime = new \DateTimeImmutable();
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
} 