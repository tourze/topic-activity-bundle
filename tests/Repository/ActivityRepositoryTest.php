<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(ActivityRepository::class)]
#[RunTestsInSeparateProcesses]
final class ActivityRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // No setup required - using self::getService() directly in tests
    }

    protected function createNewEntity(): object
    {
        $activity = new Activity();
        $activity->setTitle('Test Activity');
        $activity->setSlug('test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        return $activity;
    }

    /**
     * @return ServiceEntityRepository<Activity>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(ActivityRepository::class);
    }

    public function testSaveAndFindActivityShouldWorkCorrectly(): void
    {
        $activity = new Activity();
        $activity->setTitle('Test Activity');
        $activity->setSlug('test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($activity, true);

        $this->assertNotNull($activity->getId());

        $foundActivity = $repository->find($activity->getId());

        $this->assertNotNull($foundActivity);
        $this->assertEquals($activity->getId(), $foundActivity->getId());
        $this->assertEquals('Test Activity', $foundActivity->getTitle());
        $this->assertEquals(ActivityStatus::DRAFT, $foundActivity->getStatus());
    }

    public function testFindActiveActivitiesShouldReturnOnlyPublishedActivities(): void
    {
        $draftActivity = new Activity();
        $draftActivity->setTitle('Draft Activity');
        $draftActivity->setSlug('draft-activity-' . uniqid());
        $draftActivity->setStatus(ActivityStatus::DRAFT);

        $publishedActivity = new Activity();
        $publishedActivity->setTitle('Published Activity');
        $publishedActivity->setSlug('published-activity-' . uniqid());
        $publishedActivity->setStatus(ActivityStatus::PUBLISHED);

        $archivedActivity = new Activity();
        $archivedActivity->setTitle('Archived Activity');
        $archivedActivity->setSlug('archived-activity-' . uniqid());
        $archivedActivity->setStatus(ActivityStatus::PUBLISHED);
        $archivedActivity->setStatus(ActivityStatus::ARCHIVED);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($draftActivity);
        $repository->save($publishedActivity);
        $repository->save($archivedActivity, true);

        $activeActivities = $repository->findActiveActivities();

        $this->assertCount(1, $activeActivities);
        $this->assertEquals(ActivityStatus::PUBLISHED, $activeActivities[0]->getStatus());
    }

    public function testFindBySlugShouldReturnCorrectActivity(): void
    {
        $activity1 = new Activity();
        $activity1->setTitle('First Activity');
        $activity1->setSlug('first-activity-' . uniqid());
        $activity1->setStatus(ActivityStatus::PUBLISHED);

        $activity2 = new Activity();
        $activity2->setTitle('Second Activity');
        $activity2->setSlug('second-activity-' . uniqid());
        $activity2->setStatus(ActivityStatus::PUBLISHED);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($activity1);
        $repository->save($activity2, true);

        $slug = $activity1->getSlug();
        $this->assertNotNull($slug);
        $foundActivity = $repository->findBySlug($slug);

        $this->assertNotNull($foundActivity);
        $this->assertEquals('First Activity', $foundActivity->getTitle());
        $this->assertEquals($activity1->getSlug(), $foundActivity->getSlug());
    }

    public function testFindBySlugShouldReturnNullForNonExistent(): void
    {
        $repository = self::getService(ActivityRepository::class);

        $foundActivity = $repository->findBySlug('non-existent-activity');

        $this->assertNull($foundActivity);
    }

    public function testFindByStatusShouldFilterCorrectly(): void
    {
        $draftActivity = new Activity();
        $draftActivity->setTitle('Draft Activity');
        $draftActivity->setSlug('draft-activity-' . uniqid());
        $draftActivity->setStatus(ActivityStatus::DRAFT);

        $publishedActivity = new Activity();
        $publishedActivity->setTitle('Published Activity');
        $publishedActivity->setSlug('published-activity-' . uniqid());
        $publishedActivity->setStatus(ActivityStatus::PUBLISHED);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($draftActivity);
        $repository->save($publishedActivity, true);

        $draftActivities = $repository->findByStatus(ActivityStatus::DRAFT);
        $publishedActivities = $repository->findByStatus(ActivityStatus::PUBLISHED);

        $this->assertGreaterThanOrEqual(1, count($draftActivities));
        $this->assertGreaterThanOrEqual(1, count($publishedActivities));

        // 验证我们创建的活动在结果中
        $draftActivityIds = array_map(fn ($a) => $a->getId(), $draftActivities);
        $publishedActivityIds = array_map(fn ($a) => $a->getId(), $publishedActivities);

        $this->assertContains($draftActivity->getId(), $draftActivityIds);
        $this->assertContains($publishedActivity->getId(), $publishedActivityIds);
    }

    public function testFindByDateRangeShouldFilterActivitiesCorrectly(): void
    {
        $inRangeActivity = new Activity();
        $inRangeActivity->setTitle('In Range Activity');
        $inRangeActivity->setSlug('in-range-activity-' . uniqid());
        $inRangeActivity->setStatus(ActivityStatus::PUBLISHED);
        self::getService(ActivityRepository::class)->save($inRangeActivity);

        $beforeActivity = new Activity();
        $beforeActivity->setTitle('Before Range Activity');
        $beforeActivity->setSlug('before-range-activity-' . uniqid());
        $beforeActivity->setStatus(ActivityStatus::PUBLISHED);
        self::getService(ActivityRepository::class)->save($beforeActivity);

        $afterActivity = new Activity();
        $afterActivity->setTitle('After Range Activity');
        $afterActivity->setSlug('after-range-activity-' . uniqid());
        $afterActivity->setStatus(ActivityStatus::PUBLISHED);

        self::getService(ActivityRepository::class)->save($afterActivity, true);

        $startDate = new \DateTimeImmutable('-1 day');
        $endDate = new \DateTimeImmutable('+1 day');

        $activitiesInRange = self::getService(ActivityRepository::class)->findByDateRange($startDate, $endDate);

        $this->assertGreaterThanOrEqual(1, count($activitiesInRange));

        foreach ($activitiesInRange as $activity) {
            $this->assertGreaterThanOrEqual($startDate, $activity->getCreateTime());
            $this->assertLessThanOrEqual($endDate, $activity->getCreateTime());
        }
    }

    public function testActivityWithComponentsShouldWorkCorrectly(): void
    {
        $activity = new Activity();
        $activity->setTitle('Activity with Components');
        $activity->setSlug('activity-with-components-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $component1 = new ActivityComponent();
        $component1->setActivity($activity);
        $component1->setComponentType('text');
        $component1->setPosition(1);
        $component1->setComponentConfig(['content' => 'First component']);

        $component2 = new ActivityComponent();
        $component2->setActivity($activity);
        $component2->setComponentType('image');
        $component2->setPosition(2);
        $component2->setComponentConfig(['src' => 'image.jpg']);

        $activity->addComponent($component1);
        $activity->addComponent($component2);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($activity, true);

        $foundActivity = $repository->find($activity->getId());

        $this->assertNotNull($foundActivity);
        $this->assertCount(2, $foundActivity->getComponents());

        $components = $foundActivity->getComponents()->toArray();
        $this->assertEquals('text', $components[0]->getComponentType());
        $this->assertEquals('image', $components[1]->getComponentType());
    }

    public function testRemoveActivityShouldDeleteFromDatabase(): void
    {
        $activity = new Activity();
        $activity->setTitle('Removable Activity');
        $activity->setSlug('removable-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($activity, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId);

        $repository->remove($activity, true);

        $foundActivity = $repository->find($activityId);
        $this->assertNull($foundActivity);
    }

    public function testCountByStatus(): void
    {
        $repository = self::getService(ActivityRepository::class);

        // Create activities with different statuses
        $draftActivity = new Activity();
        $draftActivity->setTitle('Draft Activity');
        $draftActivity->setSlug('draft-activity-' . uniqid());
        $draftActivity->setStatus(ActivityStatus::DRAFT);
        $repository->save($draftActivity, true);

        $publishedActivity = new Activity();
        $publishedActivity->setTitle('Published Activity');
        $publishedActivity->setSlug('published-activity-' . uniqid());
        $publishedActivity->setStatus(ActivityStatus::PUBLISHED);
        $repository->save($publishedActivity, true);

        $draftCount = $repository->countByStatus(ActivityStatus::DRAFT);
        $publishedCount = $repository->countByStatus(ActivityStatus::PUBLISHED);

        $this->assertGreaterThanOrEqual(1, $draftCount);
        $this->assertGreaterThanOrEqual(1, $publishedCount);
    }

    public function testFindExpiredActivities(): void
    {
        $repository = self::getService(ActivityRepository::class);

        // Create expired activity
        $expiredActivity = new Activity();
        $expiredActivity->setTitle('Expired Activity');
        $expiredActivity->setSlug('expired-activity-' . uniqid());
        $expiredActivity->setStatus(ActivityStatus::PUBLISHED);
        $expiredActivity->setEndTime(new \DateTimeImmutable('-1 day'));
        $repository->save($expiredActivity, true);

        $expiredActivities = $repository->findExpiredActivities();

        $this->assertGreaterThanOrEqual(1, count($expiredActivities));
        $expiredActivityIds = array_map(fn ($a) => $a->getId(), $expiredActivities);
        $this->assertContains($expiredActivity->getId(), $expiredActivityIds);
    }

    public function testFindInDateRange(): void
    {
        $repository = self::getService(ActivityRepository::class);

        $startDate = new \DateTimeImmutable('-1 day');
        $endDate = new \DateTimeImmutable('+1 day');

        $activity = new Activity();
        $activity->setTitle('Date Range Activity');
        $activity->setSlug('date-range-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);
        $repository->save($activity, true);

        $activitiesInRange = $repository->findInDateRange($startDate, $endDate);

        $this->assertGreaterThanOrEqual(1, count($activitiesInRange));
        $activityIds = array_map(fn ($a) => $a->getId(), $activitiesInRange);
        $this->assertContains($activity->getId(), $activityIds);
    }

    public function testFindPublished(): void
    {
        $repository = self::getService(ActivityRepository::class);

        $publishedActivity = new Activity();
        $publishedActivity->setTitle('Published Activity');
        $publishedActivity->setSlug('published-activity-' . uniqid());
        $publishedActivity->setStatus(ActivityStatus::PUBLISHED);
        $repository->save($publishedActivity, true);

        $publishedActivities = $repository->findPublished();

        $this->assertGreaterThanOrEqual(1, count($publishedActivities));
        $publishedActivityIds = array_map(fn ($a) => $a->getId(), $publishedActivities);
        $this->assertContains($publishedActivity->getId(), $publishedActivityIds);
    }

    public function testFindScheduledForPublishing(): void
    {
        $repository = self::getService(ActivityRepository::class);

        $scheduledActivity = new Activity();
        $scheduledActivity->setTitle('Scheduled Activity');
        $scheduledActivity->setSlug('scheduled-activity-' . uniqid());
        $scheduledActivity->setStatus(ActivityStatus::SCHEDULED);
        $scheduledActivity->setStartTime(new \DateTimeImmutable('-1 hour'));
        $repository->save($scheduledActivity, true);

        $scheduledActivities = $repository->findScheduledForPublishing();

        $this->assertGreaterThanOrEqual(1, count($scheduledActivities));
        $scheduledActivityIds = array_map(fn ($a) => $a->getId(), $scheduledActivities);
        $this->assertContains($scheduledActivity->getId(), $scheduledActivityIds);
    }

    public function testFindWithComponents(): void
    {
        $repository = self::getService(ActivityRepository::class);

        $activity = new Activity();
        $activity->setTitle('Activity With Components');
        $activity->setSlug('activity-with-components-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);
        $repository->save($activity, true);

        $activitiesWithComponents = $repository->findWithComponents();

        $this->assertGreaterThanOrEqual(1, count($activitiesWithComponents));
        $activityIds = array_map(fn ($a) => $a->getId(), $activitiesWithComponents);
        $this->assertContains($activity->getId(), $activityIds);
    }

    public function testRestore(): void
    {
        $repository = self::getService(ActivityRepository::class);

        $activity = new Activity();
        $activity->setTitle('Restorable Activity');
        $activity->setSlug('restorable-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);
        $repository->save($activity, true);

        // Soft delete the activity
        $repository->softDelete($activity, true);
        $this->assertNotNull($activity->getDeleteTime());

        // Restore the activity
        $repository->restore($activity, true);
        $this->assertNull($activity->getDeleteTime());
    }

    public function testSoftDelete(): void
    {
        $repository = self::getService(ActivityRepository::class);

        $activity = new Activity();
        $activity->setTitle('Soft Delete Activity');
        $activity->setSlug('soft-delete-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);
        $repository->save($activity, true);

        $this->assertNull($activity->getDeleteTime());

        $repository->softDelete($activity, true);

        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getDeleteTime());
    }

    public function testFlush(): void
    {
        $repository = self::getService(ActivityRepository::class);
        $activity = new Activity();
        $activity->setTitle('Flush Test Activity');
        $activity->setSlug('flush-test-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $repository->save($activity, false);

        // Call flush explicitly
        $repository->flush();

        // Entity should be persisted after flush
        $this->assertNotNull($activity->getId());
    }
}
