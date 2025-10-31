<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;
use Tourze\TopicActivityBundle\EventListener\ActivityTemplateUpdateListener;

/**
 * @internal
 */
#[CoversClass(ActivityTemplateUpdateListener::class)]
class ActivityTemplateUpdateListenerTest extends TestCase
{
    public function testPreUpdate(): void
    {
        $listener = new ActivityTemplateUpdateListener();
        $template = new ActivityTemplate();

        // Mock the PreUpdateEventArgs
        $args = $this->createMock(PreUpdateEventArgs::class);

        // The listener should set the update time
        $listener->preUpdate($template, $args);

        // Check that update time is set (not null)
        $this->assertNotNull($template->getUpdateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $template->getUpdateTime());
    }
}
