<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\TopicActivityBundle\DependencyInjection\TopicActivityExtension;

/**
 * @internal
 */
#[CoversClass(TopicActivityExtension::class)]
final class TopicActivityExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function getExtensionClass(): string
    {
        return TopicActivityExtension::class;
    }

    public function testExtensionAliasShouldBeCorrect(): void
    {
        $extension = new TopicActivityExtension();
        $alias = $extension->getAlias();
        $this->assertEquals('topic_activity', $alias);
    }
}
