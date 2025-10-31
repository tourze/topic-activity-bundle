<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(ActivityEventRepository::class)]
#[RunTestsInSeparateProcesses]
final class ActivityEventRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // No setup required - using self::getService() directly in tests
    }

    protected function createNewEntity(): object
    {
        $activity = $this->createActivity('Test Activity', 'test-activity');

        $event = ActivityEvent::create($this->assertValidActivityId($activity));
        $event->setEventType(ActivityEvent::EVENT_VIEW);
        $event->setSessionId('test-session-' . uniqid());

        return $event;
    }

    /** @return ServiceEntityRepository<ActivityEvent> */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(ActivityEventRepository::class);
    }

    protected function createActivity(string $title, string $slug): Activity
    {
        $activity = new Activity();
        $activity->setTitle($title);
        $activity->setSlug($slug . '-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($activity, true);

        return $activity;
    }

    protected function assertValidActivityId(Activity $activity): int
    {
        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null after persistence');

        return $activityId;
    }

    public function testCreateEventShouldReturnProperlyConfiguredEvent(): void
    {
        $activity = $this->createActivity('Test Activity', 'test-activity');

        $eventData = [
            'ip' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'custom_field' => 'custom_value',
        ];

        $event = self::getService(ActivityEventRepository::class)->createEvent($activity, 'page_view', $eventData);

        $this->assertInstanceOf(ActivityEvent::class, $event);
        $this->assertEquals($this->assertValidActivityId($activity), $event->getActivityId());
        $this->assertEquals('page_view', $event->getEventType());
        $this->assertEquals($eventData, $event->getEventData());
        $this->assertNotEmpty($event->getSessionId());
    }

    public function testCreateEventWithoutEventDataShouldUseEmptyArray(): void
    {
        $activity = $this->createActivity('Empty Data Activity', 'empty-data-activity');

        $event = self::getService(ActivityEventRepository::class)->createEvent($activity, 'click');

        $this->assertInstanceOf(ActivityEvent::class, $event);
        $this->assertEquals($this->assertValidActivityId($activity), $event->getActivityId());
        $this->assertEquals('click', $event->getEventType());
        $this->assertEmpty($event->getEventData());
        $this->assertNotEmpty($event->getSessionId());
    }

    public function testFindByActivityShouldReturnEventsForGivenActivity(): void
    {
        $activity1 = $this->createActivity('Activity 1', 'activity-1');
        $activity2 = $this->createActivity('Activity 2', 'activity-2');

        $event1 = ActivityEvent::create($this->assertValidActivityId($activity1));
        $event1->setEventType('first_event');
        $event1->setSessionId('session_1');

        $event2 = ActivityEvent::create($this->assertValidActivityId($activity2));
        $event2->setEventType('second_event');
        $event2->setSessionId('session_2');

        $event3 = ActivityEvent::create($this->assertValidActivityId($activity1));
        $event3->setEventType('third_event');
        $event3->setSessionId('session_3');

        $repository = self::getService(ActivityEventRepository::class);
        $repository->save($event1);
        $repository->save($event2);
        $repository->save($event3, true);

        $activity1Events = $repository->findByActivity($activity1);
        $activity2Events = $repository->findByActivity($activity2);

        $this->assertCount(2, $activity1Events);
        $this->assertCount(1, $activity2Events);

        foreach ($activity1Events as $event) {
            $this->assertEquals($activity1->getId(), $event->getActivityId());
        }

        foreach ($activity2Events as $event) {
            $this->assertEquals($activity2->getId(), $event->getActivityId());
        }
    }

    public function testFindByActivityAndSessionShouldReturnEventsOrderedByTime(): void
    {
        $activity = $this->createActivity('Session Activity', 'session-activity');
        $sessionId = 'test_session_' . uniqid();

        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType('middle_event');
        $event2->setSessionId($sessionId);
        $event2->setTimestamp(new \DateTime('2024-01-01 11:00:00'));
        self::getService(ActivityEventRepository::class)->save($event2);

        $event1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1->setEventType('first_event');
        $event1->setSessionId($sessionId);
        $event1->setTimestamp(new \DateTime('2024-01-01 10:00:00'));
        self::getService(ActivityEventRepository::class)->save($event1);

        $event3 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event3->setEventType('last_event');
        $event3->setSessionId($sessionId);
        $event3->setTimestamp(new \DateTime('2024-01-01 12:00:00'));
        self::getService(ActivityEventRepository::class)->save($event3, true);

        $events = self::getService(ActivityEventRepository::class)->findByActivityAndSession($activity, $sessionId);

        $this->assertCount(3, $events);
        $this->assertEquals('first_event', $events[0]->getEventType());
        $this->assertEquals('middle_event', $events[1]->getEventType());
        $this->assertEquals('last_event', $events[2]->getEventType());
    }

    public function testFindByDateRangeShouldFilterEventsCorrectly(): void
    {
        $activity = $this->createActivity('Date Range Activity', 'date-range-activity');

        $event1a = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1a->setEventType('page_view');
        $event1a->setSessionId('session_1');
        $event1a->setTimestamp(new \DateTime('2024-01-15 10:00:00'));
        self::getService(ActivityEventRepository::class)->save($event1a);

        $event1b = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1b->setEventType('button_click');
        $event1b->setSessionId('session_1');
        $event1b->setTimestamp(new \DateTime('2024-01-15 10:05:00'));
        self::getService(ActivityEventRepository::class)->save($event1b);

        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType('page_view');
        $event2->setSessionId('session_2');
        $event2->setTimestamp(new \DateTime('2024-01-16 10:00:00'));
        self::getService(ActivityEventRepository::class)->save($event2);

        $event3 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event3->setEventType('page_view');
        $event3->setSessionId('session_3');
        $event3->setTimestamp(new \DateTime('2024-02-01 10:00:00'));
        self::getService(ActivityEventRepository::class)->save($event3, true);

        $startDate = new \DateTimeImmutable('2024-01-15 00:00:00');
        $endDate = new \DateTimeImmutable('2024-01-16 23:59:59');

        $eventsInRange = self::getService(ActivityEventRepository::class)->findByDateRange($activity, $startDate, $endDate);

        $this->assertCount(3, $eventsInRange);

        foreach ($eventsInRange as $event) {
            $this->assertGreaterThanOrEqual($startDate, $event->getCreateTime());
            $this->assertLessThanOrEqual($endDate, $event->getCreateTime());
        }
    }

    public function testFindByEventTypeShouldFilterCorrectly(): void
    {
        $activity = $this->createActivity('Event Type Activity', 'event-type-activity');
        $sessionId = 'type_test_session';

        $event3 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event3->setEventType('form_submit');
        $event3->setSessionId($sessionId);
        $event3->setTimestamp(new \DateTime('2024-01-01 12:00:00'));
        self::getService(ActivityEventRepository::class)->save($event3);

        $event1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1->setEventType('page_view');
        $event1->setSessionId($sessionId);
        $event1->setTimestamp(new \DateTime('2024-01-01 10:00:00'));
        self::getService(ActivityEventRepository::class)->save($event1);

        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType('button_click');
        $event2->setSessionId($sessionId);
        $event2->setTimestamp(new \DateTime('2024-01-01 11:00:00'));
        self::getService(ActivityEventRepository::class)->save($event2, true);

        $pageViewEvents = self::getService(ActivityEventRepository::class)->findByEventType($activity, 'page_view');
        $buttonClickEvents = self::getService(ActivityEventRepository::class)->findByEventType($activity, 'button_click');
        $formSubmitEvents = self::getService(ActivityEventRepository::class)->findByEventType($activity, 'form_submit');

        $this->assertCount(1, $pageViewEvents);
        $this->assertCount(1, $buttonClickEvents);
        $this->assertCount(1, $formSubmitEvents);

        $this->assertEquals('page_view', $pageViewEvents[0]->getEventType());
        $this->assertEquals('button_click', $buttonClickEvents[0]->getEventType());
        $this->assertEquals('form_submit', $formSubmitEvents[0]->getEventType());
    }

    public function testCleanupOldEventsShouldRemoveExpiredEvents(): void
    {
        $activity = $this->createActivity('Cleanup Activity', 'cleanup-activity');

        $oldEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $oldEvent->setEventType('old_event');
        $oldEvent->setSessionId('old_session');
        $oldEvent->setTimestamp(new \DateTime('-95 days'));
        self::getService(ActivityEventRepository::class)->save($oldEvent);

        $recentEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $recentEvent->setEventType('recent_event');
        $recentEvent->setSessionId('recent_session');
        $recentEvent->setTimestamp(new \DateTime('-30 days'));
        self::getService(ActivityEventRepository::class)->save($recentEvent, true);

        $deletedCount = self::getService(ActivityEventRepository::class)->cleanupOldEvents(90);

        $this->assertGreaterThan(0, $deletedCount);

        $allEvents = self::getService(ActivityEventRepository::class)->findByActivity($activity);
        $this->assertCount(1, $allEvents);
        $this->assertEquals('recent_event', $allEvents[0]->getEventType());
    }

    public function testFindActiveSessionsShouldReturnRecentSessions(): void
    {
        $activity = $this->createActivity('Active Sessions Activity', 'active-sessions-activity');

        $recentEvent1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $recentEvent1->setEventType('page_view');
        $recentEvent1->setSessionId('active_session_1');
        $recentEvent1->setTimestamp(new \DateTime('-2 minutes'));
        self::getService(ActivityEventRepository::class)->save($recentEvent1);

        $recentEvent2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $recentEvent2->setEventType('page_view');
        $recentEvent2->setSessionId('active_session_2');
        $recentEvent2->setTimestamp(new \DateTime('-1 minute'));
        self::getService(ActivityEventRepository::class)->save($recentEvent2);

        $recentEvent3 = ActivityEvent::create($this->assertValidActivityId($activity));
        $recentEvent3->setEventType('button_click');
        $recentEvent3->setSessionId('active_session_1');
        $recentEvent3->setTimestamp(new \DateTime('-1 minute'));
        self::getService(ActivityEventRepository::class)->save($recentEvent3);

        $oldEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $oldEvent->setEventType('page_view');
        $oldEvent->setSessionId('inactive_session');
        $oldEvent->setTimestamp(new \DateTime('-10 minutes'));
        self::getService(ActivityEventRepository::class)->save($oldEvent, true);

        $activeSessions = self::getService(ActivityEventRepository::class)->findActiveSessions($activity, 5);

        $this->assertCount(2, $activeSessions);
        $this->assertContains('active_session_1', $activeSessions);
        $this->assertContains('active_session_2', $activeSessions);
        $this->assertNotContains('inactive_session', $activeSessions);
    }

    public function testCountTodayEventsShouldReturnCorrectCount(): void
    {
        $activity = $this->createActivity('Today Events Activity', 'today-events-activity');

        $todayEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $todayEvent->setEventType('today_event');
        $todayEvent->setSessionId('today_session');
        $todayEvent->setTimestamp(new \DateTime('today 10:00:00'));
        self::getService(ActivityEventRepository::class)->save($todayEvent);

        $yesterdayEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $yesterdayEvent->setEventType('yesterday_event');
        $yesterdayEvent->setSessionId('yesterday_session');
        $yesterdayEvent->setTimestamp(new \DateTime('yesterday 10:00:00'));
        self::getService(ActivityEventRepository::class)->save($yesterdayEvent, true);

        $todayCount = self::getService(ActivityEventRepository::class)->countTodayEvents($activity);

        $this->assertEquals(1, $todayCount);
    }

    public function testCleanOldEvents(): void
    {
        $repository = self::getService(ActivityEventRepository::class);

        // Create old event (100 days ago)
        $activity = $this->createActivity('Old Event Activity', 'old-event-activity-' . uniqid());
        $oldEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $oldEvent->setEventType(ActivityEvent::EVENT_VIEW);
        $oldEvent->setSessionId('old-session-' . uniqid());
        $oldEvent->setTimestamp(new \DateTime('-100 days'));
        $repository->save($oldEvent, true);

        // Create recent event (10 days ago)
        $recentEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $recentEvent->setEventType(ActivityEvent::EVENT_VIEW);
        $recentEvent->setSessionId('recent-session-' . uniqid());
        $recentEvent->setTimestamp(new \DateTime('-10 days'));
        $repository->save($recentEvent, true);

        $deletedCount = $repository->cleanOldEvents(90);

        $this->assertGreaterThanOrEqual(1, $deletedCount);
    }

    public function testCountUniqueVisitors(): void
    {
        $repository = self::getService(ActivityEventRepository::class);
        $activity = $this->createActivity('Unique Visitors Activity', 'unique-visitors-activity-' . uniqid());

        $startDate = new \DateTimeImmutable('-1 day');
        $endDate = new \DateTimeImmutable('+1 day');

        // Create events with different session IDs
        $event1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1->setEventType(ActivityEvent::EVENT_VIEW);
        $event1->setSessionId('session-1-' . uniqid());
        $repository->save($event1, true);

        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType(ActivityEvent::EVENT_VIEW);
        $event2->setSessionId('session-2-' . uniqid());
        $repository->save($event2, true);

        // Create another event with same session ID as event1
        $event3 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event3->setEventType(ActivityEvent::EVENT_CLICK);
        $event3->setSessionId($event1->getSessionId());
        $repository->save($event3, true);

        $uniqueVisitors = $repository->countUniqueVisitors($this->assertValidActivityId($activity), $startDate, $endDate);

        $this->assertEquals(2, $uniqueVisitors);
    }

    public function testFindByActivityId(): void
    {
        $repository = self::getService(ActivityEventRepository::class);
        $activity = $this->createActivity('Find By Activity ID Activity', 'find-by-activity-id-' . uniqid());

        $event1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1->setEventType(ActivityEvent::EVENT_VIEW);
        $event1->setSessionId('session-1-' . uniqid());
        $repository->save($event1, true);

        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType(ActivityEvent::EVENT_CLICK);
        $event2->setSessionId('session-2-' . uniqid());
        $repository->save($event2, true);

        $events = $repository->findByActivityId($this->assertValidActivityId($activity));

        $this->assertCount(2, $events);
        $this->assertEquals($this->assertValidActivityId($activity), $events[0]->getActivityId());
        $this->assertEquals($this->assertValidActivityId($activity), $events[1]->getActivityId());
    }

    public function testFindBySessionId(): void
    {
        $repository = self::getService(ActivityEventRepository::class);
        $activity = $this->createActivity('Find By Session ID Activity', 'find-by-session-id-' . uniqid());

        $sessionId = 'test-session-' . uniqid();

        $event1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1->setEventType(ActivityEvent::EVENT_VIEW);
        $event1->setSessionId($sessionId);
        $repository->save($event1, true);

        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType(ActivityEvent::EVENT_CLICK);
        $event2->setSessionId($sessionId);
        $repository->save($event2, true);

        $events = $repository->findBySessionId($sessionId);

        $this->assertCount(2, $events);
        $this->assertEquals($sessionId, $events[0]->getSessionId());
        $this->assertEquals($sessionId, $events[1]->getSessionId());
    }

    public function testFindTodayEvents(): void
    {
        $repository = self::getService(ActivityEventRepository::class);
        $activity = $this->createActivity('Find Today Events Activity', 'find-today-events-' . uniqid());

        // Create today event
        $todayEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $todayEvent->setEventType(ActivityEvent::EVENT_VIEW);
        $todayEvent->setSessionId('today-session-' . uniqid());
        $repository->save($todayEvent, true);

        // Create yesterday event
        $yesterdayEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $yesterdayEvent->setEventType(ActivityEvent::EVENT_VIEW);
        $yesterdayEvent->setSessionId('yesterday-session-' . uniqid());
        $yesterdayEvent->setTimestamp(new \DateTime('yesterday'));
        $repository->save($yesterdayEvent, true);

        $todayEvents = $repository->findTodayEvents($activity);

        $this->assertCount(1, $todayEvents);
        $this->assertEquals($this->assertValidActivityId($activity), $todayEvents[0]->getActivityId());
    }

    public function testFindVisitorEvent(): void
    {
        $repository = self::getService(ActivityEventRepository::class);
        $activity = $this->createActivity('Find Visitor Event Activity', 'find-visitor-event-' . uniqid());

        $sessionId = 'visitor-session-' . uniqid();
        $eventType = ActivityEvent::EVENT_VIEW;

        $event = ActivityEvent::create($this->assertValidActivityId($activity));
        $event->setEventType($eventType);
        $event->setSessionId($sessionId);
        $repository->save($event, true);

        $foundEvent = $repository->findVisitorEvent($this->assertValidActivityId($activity), $sessionId, $eventType);

        $this->assertInstanceOf(ActivityEvent::class, $foundEvent);
        $this->assertEquals($this->assertValidActivityId($activity), $foundEvent->getActivityId());
        $this->assertEquals($sessionId, $foundEvent->getSessionId());
        $this->assertEquals($eventType, $foundEvent->getEventType());

        // Test with non-existent combination
        $notFoundEvent = $repository->findVisitorEvent($this->assertValidActivityId($activity), 'non-existent-session', $eventType);
        $this->assertNull($notFoundEvent);
    }

    public function testFlush(): void
    {
        $repository = self::getService(ActivityEventRepository::class);
        $activity = $this->createActivity('Flush Test Activity', 'flush-test-' . uniqid());

        $event = ActivityEvent::create($this->assertValidActivityId($activity));
        $event->setEventType(ActivityEvent::EVENT_VIEW);
        $event->setSessionId('flush-session-' . uniqid());

        $repository->save($event, false);

        // Call flush explicitly
        $repository->flush();

        // Entity should be persisted after flush
        $this->assertNotNull($event->getId());
    }
}
