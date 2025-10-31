<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\EventListener\ActivityUpdateListener;

/**
 * @internal
 */
#[CoversClass(ActivityUpdateListener::class)]
class ActivityUpdateListenerTest extends TestCase
{
    public function testPreUpdate(): void
    {
        $listener = new ActivityUpdateListener();
        $activity = new Activity();

        // Mock the PreUpdateEventArgs
        $args = $this->createMock(PreUpdateEventArgs::class);

        // The listener should set the update time
        $listener->preUpdate($activity, $args);

        // Check that update time is set (not null)
        $this->assertNotNull($activity->getUpdateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getUpdateTime());
    }
}
