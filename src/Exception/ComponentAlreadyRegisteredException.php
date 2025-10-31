<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Exception;

class ComponentAlreadyRegisteredException extends \RuntimeException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Component with type "%s" is already registered', $type));
    }
}
