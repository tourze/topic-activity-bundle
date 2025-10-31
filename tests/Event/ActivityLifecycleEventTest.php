<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Event\ActivityLifecycleEvent;

/**
 * @internal
 */
#[CoversClass(ActivityLifecycleEvent::class)]
final class ActivityLifecycleEventTest extends AbstractEventTestCase
{
    public function testConstructorAndGetters(): void
    {
        $activity = new Activity();
        $activity->setTitle('Test Activity');

        $context = ['key' => 'value', 'number' => 42];

        $event = new ActivityLifecycleEvent($activity, $context);

        $this->assertSame($activity, $event->getActivity());
        $this->assertSame($context, $event->getContext());
    }

    public function testConstructorWithEmptyContext(): void
    {
        $activity = new Activity();

        $event = new ActivityLifecycleEvent($activity);

        $this->assertSame($activity, $event->getActivity());
        $this->assertSame([], $event->getContext());
    }

    public function testGetContextValue(): void
    {
        $activity = new Activity();
        $context = ['key1' => 'value1', 'key2' => 123, 'key3' => null];

        $event = new ActivityLifecycleEvent($activity, $context);

        $this->assertSame('value1', $event->getContextValue('key1'));
        $this->assertSame(123, $event->getContextValue('key2'));
        $this->assertNull($event->getContextValue('key3'));
    }

    public function testGetContextValueWithDefault(): void
    {
        $activity = new Activity();
        $event = new ActivityLifecycleEvent($activity, []);

        $this->assertSame('default', $event->getContextValue('non_existent', 'default'));
        $this->assertNull($event->getContextValue('non_existent'));
        $this->assertSame(42, $event->getContextValue('missing_key', 42));
    }

    public function testSetContextValue(): void
    {
        $activity = new Activity();
        $event = new ActivityLifecycleEvent($activity, []);

        $event->setContextValue('new_key', 'new_value');
        $this->assertSame('new_value', $event->getContextValue('new_key'));
        $this->assertArrayHasKey('new_key', $event->getContext());
    }

    public function testSetContextValueOverwrite(): void
    {
        $activity = new Activity();
        $event = new ActivityLifecycleEvent($activity, ['existing' => 'old_value']);

        $event->setContextValue('existing', 'new_value');

        $this->assertSame('new_value', $event->getContextValue('existing'));
    }

    public function testHasContextValue(): void
    {
        $activity = new Activity();
        $context = ['existing_key' => 'value', 'null_key' => null];

        $event = new ActivityLifecycleEvent($activity, $context);

        $this->assertTrue($event->hasContextValue('existing_key'));
        $this->assertTrue($event->hasContextValue('null_key')); // null values are considered as existing
        $this->assertFalse($event->hasContextValue('non_existent_key'));
    }

    public function testFluentInterface(): void
    {
        $activity = new Activity();
        $event = new ActivityLifecycleEvent($activity);

        $event->setContextValue('key1', 'value1');
        $event->setContextValue('key2', 'value2');
        $event->setContextValue('key3', 'value3');
        $this->assertSame('value1', $event->getContextValue('key1'));
        $this->assertSame('value2', $event->getContextValue('key2'));
        $this->assertSame('value3', $event->getContextValue('key3'));
    }

    public function testConstants(): void
    {
        $this->assertSame('topic_activity.before_create', ActivityLifecycleEvent::BEFORE_CREATE);
        $this->assertSame('topic_activity.after_create', ActivityLifecycleEvent::AFTER_CREATE);
        $this->assertSame('topic_activity.before_update', ActivityLifecycleEvent::BEFORE_UPDATE);
        $this->assertSame('topic_activity.after_update', ActivityLifecycleEvent::AFTER_UPDATE);
        $this->assertSame('topic_activity.before_publish', ActivityLifecycleEvent::BEFORE_PUBLISH);
        $this->assertSame('topic_activity.after_publish', ActivityLifecycleEvent::AFTER_PUBLISH);
        $this->assertSame('topic_activity.before_archive', ActivityLifecycleEvent::BEFORE_ARCHIVE);
        $this->assertSame('topic_activity.after_archive', ActivityLifecycleEvent::AFTER_ARCHIVE);
        $this->assertSame('topic_activity.before_delete', ActivityLifecycleEvent::BEFORE_DELETE);
        $this->assertSame('topic_activity.after_delete', ActivityLifecycleEvent::AFTER_DELETE);
        $this->assertSame('topic_activity.before_restore', ActivityLifecycleEvent::BEFORE_RESTORE);
        $this->assertSame('topic_activity.after_restore', ActivityLifecycleEvent::AFTER_RESTORE);
    }

    public function testContextWithComplexTypes(): void
    {
        $activity = new Activity();
        $complexContext = [
            'datetime' => new \DateTimeImmutable('2024-01-01'),
            'array' => ['nested' => ['value' => 123]],
            'object' => new \stdClass(),
            'boolean' => true,
            'float' => 3.14,
        ];

        $event = new ActivityLifecycleEvent($activity, $complexContext);

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getContextValue('datetime'));
        $this->assertSame(['nested' => ['value' => 123]], $event->getContextValue('array'));
        $this->assertInstanceOf(\stdClass::class, $event->getContextValue('object'));
        $this->assertTrue($event->getContextValue('boolean'));
        $this->assertSame(3.14, $event->getContextValue('float'));
    }
}
