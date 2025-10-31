<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Exception;

use LogicException as BaseLogicException;

/**
 * 活动状态异常
 */
class ActivityStateException extends BaseLogicException
{
    public static function notDeleted(): self
    {
        return new self('Activity is not deleted');
    }

    public static function alreadyArchived(): self
    {
        return new self('Activity is already archived');
    }
}
