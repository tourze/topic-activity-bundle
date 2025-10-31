<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TopicActivityBundle\Repository\ActivityComponentRepository;

#[ORM\Entity(repositoryClass: ActivityComponentRepository::class)]
#[ORM\Table(name: 'topic_activity_component', options: ['comment' => '活动组件表'])]
#[ORM\Index(name: 'topic_activity_component_idx_activity_position', columns: ['activity_id', 'position'])]
class ActivityComponent implements \Stringable
{
    use TimestampableAware;

    /** @var positive-int|null */
    /** @phpstan-ignore-next-line property.unusedType Doctrine auto-increment ID */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Activity::class, inversedBy: 'components')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Activity $activity = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '组件类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $componentType = '';

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '组件配置'])]
    #[Assert\NotNull]
    private array $componentConfig = [];

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '排序位置'])]
    #[Assert\PositiveOrZero]
    private int $position = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否可见'])]
    #[Assert\NotNull]
    private bool $isVisible = true;

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
            throw new \LogicException('ActivityComponent must be persisted before accessing ID');
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

    public function getComponentType(): string
    {
        return $this->componentType;
    }

    public function setComponentType(string $componentType): void
    {
        $this->componentType = $componentType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getComponentConfig(): array
    {
        return $this->componentConfig;
    }

    /**
     * @param array<string, mixed> $componentConfig
     */
    public function setComponentConfig(array $componentConfig): void
    {
        $this->componentConfig = $componentConfig;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->componentConfig[$key] ?? $default;
    }

    public function setConfigValue(string $key, mixed $value): void
    {
        $this->componentConfig[$key] = $value;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): void
    {
        $this->isVisible = $isVisible;
    }

    public function moveUp(): self
    {
        $this->position = max(0, $this->position - 1);

        return $this;
    }

    public function moveDown(): self
    {
        ++$this->position;

        return $this;
    }

    public function duplicate(): self
    {
        $clone = new self();
        $clone->setComponentType($this->componentType);
        $clone->setComponentConfig($this->componentConfig);
        $clone->setPosition($this->position);
        $clone->setIsVisible($this->isVisible);

        return $clone;
    }

    public function __toString(): string
    {
        return sprintf('%s Component #%d', $this->componentType, $this->id);
    }
}
