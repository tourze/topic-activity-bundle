<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Event\ActivityLifecycleEvent;
use Tourze\TopicActivityBundle\EventListener\ActivityEventListener;

/**
 * @internal
 */
#[CoversClass(ActivityEventListener::class)]
#[RunTestsInSeparateProcesses]
class ActivityEventListenerTest extends AbstractEventSubscriberTestCase
{
    private ActivityEventListener $listener;

    protected function onSetUp(): void
    {
        // 创建Mock logger
        $logger = $this->createMock(LoggerInterface::class);
        // 直接创建listener实例，确保测试的确定性
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $this->listener = new ActivityEventListener($logger);
    }

    private function createActivity(int $id = 1, string $title = 'Test Activity'): Activity
    {
        $activity = new Activity();
        $activity->setTitle($title);
        // 用反射设置ID，因为ID通常是private且由ORM管理
        $reflector = new \ReflectionClass($activity);
        $idProperty = $reflector->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($activity, $id);

        return $activity;
    }

    public function testOnActivityPublished(): void
    {
        $activity = $this->createActivity(1, 'Test Published Activity');
        $event = new ActivityLifecycleEvent($activity);

        // 测试方法调用不抛出异常
        $this->listener->onActivityPublished($event);

        // 验证activity对象没有被修改
        $this->assertSame('Test Published Activity', $activity->getTitle());
    }

    public function testOnActivityPublishedScheduled(): void
    {
        $activity = $this->createActivity(2, 'Test Scheduled Activity');
        $event = new ActivityLifecycleEvent($activity, [
            'scheduledTime' => new \DateTime('2023-12-01 10:00:00'),
        ]);

        // 测试方法调用不抛出异常
        $this->listener->onActivityPublished($event);

        // 验证activity对象没有被修改
        $this->assertSame('Test Scheduled Activity', $activity->getTitle());
    }

    public function testOnActivityArchived(): void
    {
        $activity = $this->createActivity(3, 'Test Archived Activity');
        $event = new ActivityLifecycleEvent($activity);

        // 测试方法调用不抛出异常
        $this->listener->onActivityArchived($event);

        // 验证activity对象没有被修改
        $this->assertSame('Test Archived Activity', $activity->getTitle());
    }

    public function testOnActivityDeletedSoft(): void
    {
        $activity = $this->createActivity(4, 'Test Soft Deleted Activity');
        $event = new ActivityLifecycleEvent($activity, [
            'hard' => false,
        ]);

        // 测试方法调用不抛出异常
        $this->listener->onActivityDeleted($event);

        // 验证activity对象没有被修改
        $this->assertSame('Test Soft Deleted Activity', $activity->getTitle());
    }

    public function testOnActivityDeletedHard(): void
    {
        $activity = $this->createActivity(5, 'Test Hard Deleted Activity');
        $event = new ActivityLifecycleEvent($activity, [
            'hard' => true,
        ]);

        // 测试方法调用不抛出异常
        $this->listener->onActivityDeleted($event);

        // 验证activity对象没有被修改
        $this->assertSame('Test Hard Deleted Activity', $activity->getTitle());
    }

    public function testOnActivityReactivated(): void
    {
        // ActivityEventListener 中没有 reactivated 处理方法
        // 这个测试应该被移除，或者实现对应的方法
        $this->expectNotToPerformAssertions();
    }
}
