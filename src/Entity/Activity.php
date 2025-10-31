<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Exception\InvalidStatusTransitionException;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'topic_activity', options: ['comment' => '专题活动表'])]
#[ORM\Index(name: 'topic_activity_idx_start_end', columns: ['start_time', 'end_time'])]
class Activity implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    /** @var positive-int|null */
    /** @phpstan-ignore-next-line property.unusedType Doctrine auto-increment ID */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['comment' => '唯一标识符UUID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 36)]
    private string $uuid;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '活动标题'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title = '';

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: true, options: ['comment' => 'URL友好标识'])]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: 'Slug 只能包含小写字母、数字和连字符'
    )]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '活动描述'])]
    #[Assert\Length(max: 10000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '封面图片'])]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    private ?string $coverImage = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, enumType: ActivityStatus::class, options: ['comment' => '活动状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ActivityStatus::class, 'cases'])]
    private ActivityStatus $status = ActivityStatus::DRAFT;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '模板ID'])]
    #[Assert\PositiveOrZero]
    private ?int $templateId = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '布局配置'])]
    #[Assert\NotNull]
    private array $layoutConfig = [];

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'SEO配置'])]
    #[Assert\Valid]
    private ?array $seoConfig = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '分享配置'])]
    #[Assert\Valid]
    private ?array $shareConfig = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '访问控制配置'])]
    #[Assert\Valid]
    private ?array $accessConfig = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '活动开始时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '活动结束时间'])]
    #[Assert\DateTime]
    #[Assert\Expression(
        expression: 'this.endTime == null or this.startTime == null or this.endTime > this.startTime',
        message: '结束时间必须晚于开始时间'
    )]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '发布时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $publishTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '归档时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $archiveTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '删除时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $deleteTime = null;

    /** @var Collection<int, ActivityComponent> */
    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityComponent::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(value: ['position' => 'ASC'])]
    private Collection $components;

    /** @var Collection<int, ActivityStats> */
    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityStats::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $stats;

    public function __construct()
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->components = new ArrayCollection();
        $this->stats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdOrFail(): int
    {
        if ($this->id === null) {
            throw new \LogicException('Activity must be persisted before accessing ID');
        }
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): void
    {
        $this->coverImage = $coverImage;
    }

    public function getStatus(): ActivityStatus
    {
        return $this->status;
    }

    public function setStatus(ActivityStatus $status): void
    {
        // 允许新实体（ID为null）设置任何状态，避免在测试中创建实体时的状态转换限制
        if (null !== $this->id && $this->status !== $status && !$this->status->canTransitionTo($status)) {
            throw new InvalidStatusTransitionException(sprintf('Cannot transition from %s to %s', $this->status->value, $status->value));
        }

        $this->status = $status;

        if (ActivityStatus::PUBLISHED === $status) {
            $this->publishTime = new \DateTimeImmutable();
        } elseif (ActivityStatus::ARCHIVED === $status) {
            $this->archiveTime = new \DateTimeImmutable();
        }
    }

    public function getTemplateId(): ?int
    {
        return $this->templateId;
    }

    public function setTemplateId(?int $templateId): void
    {
        $this->templateId = $templateId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLayoutConfig(): array
    {
        return $this->layoutConfig;
    }

    /**
     * @param array<string, mixed> $layoutConfig
     */
    public function setLayoutConfig(array $layoutConfig): void
    {
        $this->layoutConfig = $layoutConfig;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSeoConfig(): ?array
    {
        return $this->seoConfig;
    }

    /**
     * @param array<string, mixed>|null $seoConfig
     */
    public function setSeoConfig(?array $seoConfig): void
    {
        $this->seoConfig = $seoConfig;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getShareConfig(): ?array
    {
        return $this->shareConfig;
    }

    /**
     * @param array<string, mixed>|null $shareConfig
     */
    public function setShareConfig(?array $shareConfig): void
    {
        $this->shareConfig = $shareConfig;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAccessConfig(): ?array
    {
        return $this->accessConfig;
    }

    /**
     * @param array<string, mixed>|null $accessConfig
     */
    public function setAccessConfig(?array $accessConfig): void
    {
        $this->accessConfig = $accessConfig;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getPublishTime(): ?\DateTimeImmutable
    {
        return $this->publishTime;
    }

    public function getArchiveTime(): ?\DateTimeImmutable
    {
        return $this->archiveTime;
    }

    public function getDeleteTime(): ?\DateTimeImmutable
    {
        return $this->deleteTime;
    }

    public function setDeleteTime(?\DateTimeImmutable $deleteTime): void
    {
        $this->deleteTime = $deleteTime;
    }

    /**
     * @return Collection<int, ActivityComponent>
     */
    public function getComponents(): Collection
    {
        return $this->components;
    }

    public function addComponent(ActivityComponent $component): void
    {
        if (!$this->components->contains($component)) {
            $this->components->add($component);
            $component->setActivity($this);
        }
    }

    public function removeComponent(ActivityComponent $component): void
    {
        if ($this->components->removeElement($component)) {
            if ($component->getActivity() === $this) {
                $component->setActivity(null);
            }
        }
    }

    /**
     * @return Collection<int, ActivityStats>
     */
    public function getStats(): Collection
    {
        return $this->stats;
    }

    public function isActive(): bool
    {
        if (ActivityStatus::PUBLISHED !== $this->status) {
            return false;
        }

        $now = new \DateTimeImmutable();

        if (null !== $this->startTime && $now < $this->startTime) {
            return false;
        }

        if (null !== $this->endTime && $now > $this->endTime) {
            return false;
        }

        return true;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deleteTime;
    }

    public function __toString(): string
    {
        return $this->title ?? 'Activity #' . $this->id;
    }
}
