<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\TopicActivityBundle\Entity\Activity;

class ActivityLifecycleEvent extends Event
{
    public const BEFORE_CREATE = 'topic_activity.before_create';
    public const AFTER_CREATE = 'topic_activity.after_create';
    public const BEFORE_UPDATE = 'topic_activity.before_update';
    public const AFTER_UPDATE = 'topic_activity.after_update';
    public const BEFORE_PUBLISH = 'topic_activity.before_publish';
    public const AFTER_PUBLISH = 'topic_activity.after_publish';
    public const BEFORE_ARCHIVE = 'topic_activity.before_archive';
    public const AFTER_ARCHIVE = 'topic_activity.after_archive';
    public const BEFORE_DELETE = 'topic_activity.before_delete';
    public const AFTER_DELETE = 'topic_activity.after_delete';
    public const BEFORE_RESTORE = 'topic_activity.before_restore';
    public const AFTER_RESTORE = 'topic_activity.after_restore';

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private Activity $activity,
        private array $context = [],
    ) {
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    public function setContextValue(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    public function hasContextValue(string $key): bool
    {
        return array_key_exists($key, $this->context);
    }
}
