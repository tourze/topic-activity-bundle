<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Exception;

use LogicException as BaseLogicException;

class InvalidStatusTransitionException extends BaseLogicException
{
    public function __construct(string $message = 'Invalid status transition', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
