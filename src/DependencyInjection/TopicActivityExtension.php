<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class TopicActivityExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../../config';
    }
}
