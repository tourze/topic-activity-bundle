<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TopicActivityBundle\Repository\ActivityTemplateRepository;

#[ORM\Entity(repositoryClass: ActivityTemplateRepository::class)]
#[ORM\Table(name: 'topic_activity_template', options: ['comment' => '活动模板表'])]
class ActivityTemplate implements \Stringable
{
    use TimestampableAware;

    /** @var positive-int|null */
    /** @phpstan-ignore-next-line property.unusedType Doctrine auto-increment ID */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '模板名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '模板代码'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^[a-z0-9_-]+$/', message: '模板代码只能包含小写字母、数字、下划线和连字符')]
    private string $code = '';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, unique: true, options: ['comment' => '模板别名'])]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^[a-z0-9_-]*$/', message: '模板别名只能包含小写字母、数字、下划线和连字符')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板描述'])]
    #[Assert\Length(max: 10000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '模板分类'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $category = 'general';

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '缩略图URL'])]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    private ?string $thumbnail = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '布局配置'])]
    #[Assert\NotNull]
    private array $layoutConfig = [];

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '默认数据'])]
    #[Assert\NotNull]
    private array $defaultData = [];

    /** @var array<int, string> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '标签'])]
    #[Assert\NotNull]
    private array $tags = [];

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否激活'])]
    #[Assert\NotNull]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否系统模板'])]
    #[Assert\NotNull]
    private bool $isSystem = false;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '使用次数'])]
    #[Assert\PositiveOrZero]
    private int $usageCount = 0;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdOrFail(): int
    {
        if ($this->id === null) {
            throw new \LogicException('ActivityTemplate must be persisted before accessing ID');
        }
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
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
     * @return array<string, mixed>
     */
    public function getDefaultData(): array
    {
        return $this->defaultData;
    }

    /**
     * @param array<string, mixed> $defaultData
     */
    public function setDefaultData(array $defaultData): void
    {
        $this->defaultData = $defaultData;
    }

    /**
     * @return array<int, string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array<int, string> $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): void
    {
        $this->isSystem = $isSystem;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function incrementUsageCount(): self
    {
        ++$this->usageCount;

        return $this;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setSystem(bool $isSystem): void
    {
        $this->isSystem = $isSystem;
    }

    public function setUsageCount(int $usageCount): void
    {
        $this->usageCount = $usageCount;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Template #' . $this->id;
    }
}
