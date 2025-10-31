<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Exception;

use LogicException as BaseLogicException;

/**
 * 活动状态转换异常
 */
class ActivityStatusTransitionException extends BaseLogicException
{
    public static function cannotTransition(string $currentStatus, string $targetStatus): self
    {
        return new self(sprintf('Activity cannot transition from %s to %s', $currentStatus, $targetStatus));
    }
}
