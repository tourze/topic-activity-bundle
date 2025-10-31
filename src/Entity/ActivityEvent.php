<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;

#[ORM\Entity(repositoryClass: ActivityEventRepository::class)]
#[ORM\Table(name: 'topic_activity_event', options: ['comment' => '活动事件表'])]
#[ORM\Index(name: 'topic_activity_event_idx_activity_event', columns: ['activity_id', 'event_type'])]
class ActivityEvent implements \Stringable
{
    public const EVENT_VIEW = 'view';
    public const EVENT_CLICK = 'click';
    public const EVENT_SHARE = 'share';
    public const EVENT_FORM_SUBMIT = 'form_submit';
    public const EVENT_CONVERSION = 'conversion';
    public const EVENT_COMPONENT_INTERACT = 'component_interact';

    /** @var positive-int|null */
    /** @phpstan-ignore-next-line property.unusedType Doctrine auto-increment ID */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(name: 'activity_id', type: Types::INTEGER, options: ['comment' => '活动ID'])]
    #[Assert\Positive]
    private int $activityId = 0;

    #[ORM\Column(name: 'session_id', type: Types::STRING, length: 64, options: ['comment' => '会话ID'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $sessionId = '';

    #[ORM\Column(name: 'user_id', type: Types::INTEGER, nullable: true, options: ['comment' => '用户ID'])]
    #[Assert\PositiveOrZero]
    private ?int $userId = null;

    #[ORM\Column(name: 'event_type', type: Types::STRING, length: 50, options: ['comment' => '事件类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: [
        self::EVENT_VIEW,
        self::EVENT_CLICK,
        self::EVENT_SHARE,
        self::EVENT_FORM_SUBMIT,
        self::EVENT_CONVERSION,
        self::EVENT_COMPONENT_INTERACT,
    ])]
    private string $eventType = '';

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'event_data', type: Types::JSON, nullable: true, options: ['comment' => '事件数据'])]
    #[Assert\Valid]
    private ?array $eventData = null;

    #[ORM\Column(name: 'client_ip', type: Types::STRING, length: 45, nullable: true, options: ['comment' => '客户端IP'])]
    #[Assert\Length(max: 45)]
    #[Assert\Ip]
    private ?string $clientIp = null;

    #[ORM\Column(name: 'user_agent', type: Types::TEXT, nullable: true, options: ['comment' => '用户代理'])]
    #[Assert\Length(max: 1000)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '来源页面'])]
    #[Assert\Length(max: 1000)]
    #[Assert\Url]
    private ?string $referer = null;

    #[ORM\Column(name: 'create_time', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    #[IndexColumn]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
    }

    /**
     * 创建具有指定活动ID的ActivityEvent实例的工厂方法
     * 推荐在业务逻辑中使用此方法
     */
    public static function create(int $activityId): self
    {
        $event = new self();
        $event->activityId = $activityId;
        return $event;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdOrFail(): int
    {
        if ($this->id === null) {
            throw new \LogicException('ActivityEvent must be persisted before accessing ID');
        }
        return $this->id;
    }

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function setActivityId(int $activityId): void
    {
        $this->activityId = $activityId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventData(): ?array
    {
        return $this->eventData;
    }

    /**
     * @param array<string, mixed>|null $eventData
     */
    public function setEventData(?array $eventData): void
    {
        $this->eventData = $eventData;
    }

    public function getEventDataValue(string $key, mixed $default = null): mixed
    {
        return $this->eventData[$key] ?? $default;
    }

    public function setEventDataValue(string $key, mixed $value): void
    {
        if (null === $this->eventData) {
            $this->eventData = [];
        }
        $this->eventData[$key] = $value;
    }

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    public function setClientIp(?string $clientIp): void
    {
        $this->clientIp = $clientIp;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(?string $referer): void
    {
        $this->referer = $referer;
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setTimestamp(\DateTime $timestamp): void
    {
        $this->createTime = \DateTimeImmutable::createFromMutable($timestamp);
    }

    public function getType(): string
    {
        return $this->eventType;
    }

    public function isViewEvent(): bool
    {
        return self::EVENT_VIEW === $this->eventType;
    }

    public function isClickEvent(): bool
    {
        return self::EVENT_CLICK === $this->eventType;
    }

    public function isShareEvent(): bool
    {
        return self::EVENT_SHARE === $this->eventType;
    }

    public function isFormSubmitEvent(): bool
    {
        return self::EVENT_FORM_SUBMIT === $this->eventType;
    }

    public function isConversionEvent(): bool
    {
        return self::EVENT_CONVERSION === $this->eventType;
    }

    public function getDeviceType(): string
    {
        if (null === $this->userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($this->userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    public function getSource(): string
    {
        if (null === $this->referer) {
            return 'direct';
        }

        $host = parse_url($this->referer, PHP_URL_HOST);

        if (false === $host || null === $host) {
            return 'unknown';
        }

        if (str_contains($host, 'google') || str_contains($host, 'baidu') || str_contains($host, 'bing')) {
            return 'search';
        }

        if (str_contains($host, 'facebook') || str_contains($host, 'twitter') || str_contains($host, 'weibo') || str_contains($host, 'wechat')) {
            return 'social';
        }

        return 'referral';
    }

    public function __toString(): string
    {
        return sprintf('%s Event #%d', $this->eventType, $this->id);
    }
}
