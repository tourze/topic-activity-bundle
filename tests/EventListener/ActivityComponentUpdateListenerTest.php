<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\EventListener\ActivityComponentUpdateListener;

/**
 * @internal
 */
#[CoversClass(ActivityComponentUpdateListener::class)]
class ActivityComponentUpdateListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 自定义初始化逻辑
    }

    public function testPreUpdate(): void
    {
        $listener = new ActivityComponentUpdateListener();
        $component = new ActivityComponent();

        // Mock the PreUpdateEventArgs
        $args = $this->createMock(PreUpdateEventArgs::class);

        // The listener should set the update time
        $listener->preUpdate($component, $args);

        // Check that update time is set (not null)
        $this->assertNotNull($component->getUpdateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $component->getUpdateTime());
    }
}
