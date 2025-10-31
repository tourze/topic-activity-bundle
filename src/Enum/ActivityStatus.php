<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ActivityStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::SCHEDULED => '待发布',
            self::PUBLISHED => '已发布',
            self::ARCHIVED => '已下架',
            self::DELETED => '已删除',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SCHEDULED => 'warning',
            self::PUBLISHED => 'success',
            self::ARCHIVED => 'info',
            self::DELETED => 'danger',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::SCHEDULED, self::PUBLISHED, self::DELETED], true),
            self::SCHEDULED => in_array($newStatus, [self::DRAFT, self::PUBLISHED, self::DELETED], true),
            self::PUBLISHED => in_array($newStatus, [self::DRAFT, self::ARCHIVED], true),
            self::ARCHIVED => in_array($newStatus, [self::PUBLISHED, self::DELETED], true),
            self::DELETED => false,
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::SCHEDULED, self::PUBLISHED], true);
    }

    public function isVisible(): bool
    {
        return self::PUBLISHED === $this;
    }
}
