<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\TopicActivityBundle\Repository\ActivityStatsRepository;

#[ORM\Entity(repositoryClass: ActivityStatsRepository::class)]
#[ORM\Table(name: 'topic_activity_stats', options: ['comment' => '活动统计表'])]
#[ORM\UniqueConstraint(name: 'uk_activity_date', columns: ['activity_id', 'date'])]
class ActivityStats implements \Stringable
{
    /** @var positive-int|null */
    /** @phpstan-ignore-next-line property.unusedType Doctrine auto-increment ID */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Activity::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Activity $activity = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '统计日期'])]
    #[IndexColumn]
    #[Assert\NotNull]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '页面浏览量'])]
    #[Assert\PositiveOrZero]
    private int $pv = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '独立访客数'])]
    #[Assert\PositiveOrZero]
    private int $uv = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '分享次数'])]
    #[Assert\PositiveOrZero]
    private int $shareCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '表单提交次数'])]
    #[Assert\PositiveOrZero]
    private int $formSubmitCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '转化次数'])]
    #[Assert\PositiveOrZero]
    private int $conversionCount = 0;

    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '停留时长(秒)'])]
    #[Assert\PositiveOrZero]
    private float $stayDuration = 0.0;

    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '跳出率'])]
    #[Assert\Range(min: 0, max: 100)]
    private float $bounceRate = 0.0;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '设备统计'])]
    #[Assert\Valid]
    private ?array $deviceStats = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '来源统计'])]
    #[Assert\Valid]
    private ?array $sourceStats = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '地区统计'])]
    #[Assert\Valid]
    private ?array $regionStats = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable('today');
        $this->createTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdOrFail(): int
    {
        if ($this->id === null) {
            throw new \LogicException('ActivityStats must be persisted before accessing ID');
        }
        return $this->id;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): void
    {
        $this->activity = $activity;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function incrementPv(int $count = 1): self
    {
        $this->pv += $count;

        return $this;
    }

    public function incrementUv(int $count = 1): self
    {
        $this->uv += $count;

        return $this;
    }

    public function incrementShareCount(int $count = 1): self
    {
        $this->shareCount += $count;

        return $this;
    }

    public function incrementFormSubmitCount(int $count = 1): self
    {
        $this->formSubmitCount += $count;

        return $this;
    }

    public function incrementConversionCount(int $count = 1): self
    {
        $this->conversionCount += $count;

        return $this;
    }

    public function addStayDuration(float $duration): self
    {
        $this->stayDuration += $duration;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDeviceStats(): ?array
    {
        return $this->deviceStats;
    }

    /**
     * @param array<string, mixed>|null $deviceStats
     */
    public function setDeviceStats(?array $deviceStats): void
    {
        $this->deviceStats = $deviceStats;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSourceStats(): ?array
    {
        return $this->sourceStats;
    }

    /**
     * @param array<string, mixed>|null $sourceStats
     */
    public function setSourceStats(?array $sourceStats): void
    {
        $this->sourceStats = $sourceStats;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRegionStats(): ?array
    {
        return $this->regionStats;
    }

    /**
     * @param array<string, mixed>|null $regionStats
     */
    public function setRegionStats(?array $regionStats): void
    {
        $this->regionStats = $regionStats;
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function getConversionRate(): float
    {
        if (0 === $this->uv) {
            return 0.0;
        }

        return round(($this->conversionCount / $this->uv) * 100, 2);
    }

    public function getAverageStayDuration(): float
    {
        if (0 === $this->pv) {
            return 0.0;
        }

        return round($this->stayDuration / $this->pv, 2);
    }

    public function merge(self $stats): self
    {
        $this->pv += $stats->getPv();
        $this->uv += $stats->getUv();
        $this->shareCount += $stats->getShareCount();
        $this->formSubmitCount += $stats->getFormSubmitCount();
        $this->conversionCount += $stats->getConversionCount();
        $this->stayDuration += $stats->getStayDuration();

        if ($this->pv > 0) {
            $this->bounceRate = ($this->bounceRate * ($this->pv - $stats->getPv()) + $stats->getBounceRate() * $stats->getPv()) / $this->pv;
        }

        return $this;
    }

    public function getPv(): int
    {
        return $this->pv;
    }

    public function setPv(int $pv): void
    {
        $this->pv = $pv;
    }

    public function getUv(): int
    {
        return $this->uv;
    }

    public function setUv(int $uv): void
    {
        $this->uv = $uv;
    }

    public function getShareCount(): int
    {
        return $this->shareCount;
    }

    public function setShareCount(int $shareCount): void
    {
        $this->shareCount = $shareCount;
    }

    public function getFormSubmitCount(): int
    {
        return $this->formSubmitCount;
    }

    public function setFormSubmitCount(int $formSubmitCount): void
    {
        $this->formSubmitCount = $formSubmitCount;
    }

    public function getConversionCount(): int
    {
        return $this->conversionCount;
    }

    public function setConversionCount(int $conversionCount): void
    {
        $this->conversionCount = $conversionCount;
    }

    public function getStayDuration(): float
    {
        return $this->stayDuration;
    }

    public function setStayDuration(float $stayDuration): void
    {
        $this->stayDuration = $stayDuration;
    }

    public function getBounceRate(): float
    {
        return $this->bounceRate;
    }

    public function setBounceRate(float $bounceRate): void
    {
        $this->bounceRate = $bounceRate;
    }

    public function __toString(): string
    {
        return sprintf('Activity Stats #%d for %s', $this->id, $this->date->format('Y-m-d'));
    }
}
