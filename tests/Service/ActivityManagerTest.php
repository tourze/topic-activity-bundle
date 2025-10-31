<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Service\ActivityManager;

/**
 * @internal
 */
#[CoversClass(ActivityManager::class)]
#[RunTestsInSeparateProcesses]
final class ActivityManagerTest extends AbstractIntegrationTestCase
{
    private ActivityManager $activityManager;

    protected function onSetUp(): void
    {
        $this->activityManager = self::getService(ActivityManager::class);
    }

    public function testCreateActivity(): void
    {
        $data = [
            'title' => 'Test Activity Integration',
            'description' => 'Integration Test Description',
            'coverImage' => 'integration-test.jpg',
        ];

        $activity = $this->activityManager->createActivity($data);

        $this->assertInstanceOf(Activity::class, $activity);
        $this->assertSame('Test Activity Integration', $activity->getTitle());
        $this->assertNotEmpty($activity->getSlug());
        $this->assertSame('Integration Test Description', $activity->getDescription());
        $this->assertSame('integration-test.jpg', $activity->getCoverImage());
        $this->assertSame(ActivityStatus::DRAFT, $activity->getStatus());
        $this->assertNotNull($activity->getId());
        $this->assertNotNull($activity->getCreateTime());
    }

    public function testCreateActivityWithDuplicateTitle(): void
    {
        $data = ['title' => 'Duplicate Title Activity'];

        // 创建第一个活动
        $firstActivity = $this->activityManager->createActivity($data);
        $firstSlug = $firstActivity->getSlug();

        // 创建第二个相同标题的活动
        $secondActivity = $this->activityManager->createActivity($data);
        $secondSlug = $secondActivity->getSlug();

        $this->assertNotEquals($firstSlug, $secondSlug);
        $this->assertIsString($firstSlug);
        $this->assertIsString($secondSlug);
        $this->assertStringStartsWith($firstSlug . '-', $secondSlug);
        $this->assertSame('Duplicate Title Activity', $firstActivity->getTitle());
        $this->assertSame('Duplicate Title Activity', $secondActivity->getTitle());
    }

    public function testUpdateActivity(): void
    {
        // 先创建一个活动
        $createData = ['title' => 'Original Activity Title'];
        $activity = $this->activityManager->createActivity($createData);

        $originalUpdateTime = $activity->getUpdateTime();

        // 等待一小段时间确保更新时间不同
        usleep(1000);

        $updateData = [
            'title' => 'Updated Activity Title',
            'description' => 'Updated Description Content',
        ];

        $updatedActivity = $this->activityManager->updateActivity($activity, $updateData);

        $this->assertSame($activity, $updatedActivity);
        $this->assertSame('Updated Activity Title', $activity->getTitle());
        $this->assertSame('Updated Description Content', $activity->getDescription());
        // 验证更新后的状态（如果updateTime字段存在且被设置的话）
        if (null !== $activity->getUpdateTime()) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getUpdateTime());
        }
    }

    public function testDeleteActivitySoft(): void
    {
        // 创建一个活动用于测试软删除
        $data = ['title' => 'Activity To Be Soft Deleted'];
        $activity = $this->activityManager->createActivity($data);

        $this->assertNull($activity->getDeleteTime());

        // 执行软删除
        $this->activityManager->deleteActivity($activity, false);

        // 验证软删除结果
        $this->assertNotNull($activity->getDeleteTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getDeleteTime());
    }

    public function testDeleteActivityHard(): void
    {
        // 创建一个活动用于测试硬删除
        $data = ['title' => 'Activity To Be Hard Deleted'];
        $activity = $this->activityManager->createActivity($data);
        $activityId = $activity->getId();

        // 执行硬删除
        $this->activityManager->deleteActivity($activity, true);

        // 验证实体已从数据库中移除
        $em = self::getService(EntityManagerInterface::class);
        $deletedActivity = $em->find(Activity::class, $activityId);
        $this->assertNull($deletedActivity);
    }

    public function testRestoreActivity(): void
    {
        // 创建并软删除一个活动
        $data = ['title' => 'Activity To Be Restored'];
        $activity = $this->activityManager->createActivity($data);
        $this->activityManager->deleteActivity($activity, false);

        $this->assertNotNull($activity->getDeleteTime());

        // 恢复活动
        $restoredActivity = $this->activityManager->restoreActivity($activity);

        $this->assertSame($activity, $restoredActivity);
        $this->assertNull($activity->getDeleteTime());
        $this->assertSame(ActivityStatus::DRAFT, $activity->getStatus());
    }

    public function testRestoreActivityNotDeleted(): void
    {
        // 创建一个未删除的活动
        $data = ['title' => 'Normal Activity'];
        $activity = $this->activityManager->createActivity($data);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Activity is not deleted');

        $this->activityManager->restoreActivity($activity);
    }

    public function testDuplicateActivity(): void
    {
        // 创建源活动
        $sourceData = [
            'title' => 'Source Activity For Duplication',
            'description' => 'Original activity description',
            'coverImage' => 'source-cover.jpg',
        ];
        $sourceActivity = $this->activityManager->createActivity($sourceData);

        // 添加组件到源活动
        $componentsData = [
            [
                'type' => 'text',
                'config' => ['content' => 'Sample text content'],
                'position' => 0,
                'visible' => true,
            ],
        ];
        $this->activityManager->updateActivityComponents($sourceActivity, $componentsData);

        // 复制活动
        $duplicatedActivity = $this->activityManager->duplicateActivity($sourceActivity);

        $this->assertSame('Source Activity For Duplication (Copy)', $duplicatedActivity->getTitle());
        $this->assertNotEquals($sourceActivity->getSlug(), $duplicatedActivity->getSlug());
        $this->assertSame('Original activity description', $duplicatedActivity->getDescription());
        $this->assertSame('source-cover.jpg', $duplicatedActivity->getCoverImage());
        $this->assertNotSame($sourceActivity->getId(), $duplicatedActivity->getId());
        $this->assertCount(1, $duplicatedActivity->getComponents());
    }

    public function testDuplicateActivityWithCustomTitle(): void
    {
        // 创建源活动
        $sourceData = ['title' => 'Source Activity For Custom Duplication'];
        $sourceActivity = $this->activityManager->createActivity($sourceData);

        // 使用自定义标题复制
        $customTitle = 'My Custom Duplicated Activity';
        $duplicatedActivity = $this->activityManager->duplicateActivity($sourceActivity, $customTitle);

        $this->assertSame($customTitle, $duplicatedActivity->getTitle());
        $this->assertNotEquals($sourceActivity->getSlug(), $duplicatedActivity->getSlug());
        $duplicatedSlug = $duplicatedActivity->getSlug();
        $this->assertNotNull($duplicatedSlug);
        $this->assertStringContainsString('my-custom-duplicated-activity', $duplicatedSlug);
    }

    public function testProcessScheduledActivities(): void
    {
        // 创建两个待发布的活动
        $activity1 = $this->activityManager->createActivity(['title' => 'Scheduled Activity 1']);
        $activity2 = $this->activityManager->createActivity(['title' => 'Scheduled Activity 2']);

        // 设置为计划发布状态，发布时间为过去时间（确保是SCHEDULED状态）
        $pastTime = new \DateTimeImmutable('-1 minute');
        $futureTime = new \DateTimeImmutable('+1 hour');

        // 先设置为未来时间（会变成SCHEDULED状态），然后修改startTime为过去时间
        $this->activityManager->publishActivity($activity1, $futureTime);
        $this->activityManager->publishActivity($activity2, $futureTime);

        // 手动设置startTime为过去，这样findScheduledForPublishing能找到它们
        $activity1->setStartTime($pastTime);
        $activity2->setStartTime($pastTime);

        // 验证状态为SCHEDULED
        $this->assertEquals(ActivityStatus::SCHEDULED, $activity1->getStatus());
        $this->assertEquals(ActivityStatus::SCHEDULED, $activity2->getStatus());

        // 手动flush确保数据库状态正确
        $em = self::getService(EntityManagerInterface::class);
        $em->flush();

        // 处理计划中的活动
        $processedCount = $this->activityManager->processScheduledActivities();

        $this->assertGreaterThanOrEqual(2, $processedCount);

        // 刷新实体以获取最新状态
        $em->refresh($activity1);
        $em->refresh($activity2);

        $this->assertEquals(ActivityStatus::PUBLISHED, $activity1->getStatus());
        $this->assertEquals(ActivityStatus::PUBLISHED, $activity2->getStatus());
    }

    public function testPublishActivityImmediate(): void
    {
        // 创建草稿活动
        $data = ['title' => 'Activity To Publish Immediately'];
        $activity = $this->activityManager->createActivity($data);

        $this->assertSame(ActivityStatus::DRAFT, $activity->getStatus());
        $this->assertNull($activity->getPublishTime());

        // 立即发布
        $publishedActivity = $this->activityManager->publishActivity($activity);

        $this->assertSame($activity, $publishedActivity);
        $this->assertSame(ActivityStatus::PUBLISHED, $activity->getStatus());
        $this->assertNotNull($activity->getPublishTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getPublishTime());
    }

    public function testPublishActivityScheduled(): void
    {
        // 创建草稿活动
        $data = ['title' => 'Activity To Schedule'];
        $activity = $this->activityManager->createActivity($data);

        $this->assertSame(ActivityStatus::DRAFT, $activity->getStatus());

        $scheduledTime = new \DateTimeImmutable('+2 hours');

        // 计划发布
        $publishedActivity = $this->activityManager->publishActivity($activity, $scheduledTime);

        $this->assertSame($activity, $publishedActivity);
        $this->assertSame(ActivityStatus::SCHEDULED, $activity->getStatus());
        $this->assertSame($scheduledTime, $activity->getStartTime());
        $this->assertNull($activity->getPublishTime());
    }

    public function testPublishActivityInvalidStatus(): void
    {
        // 创建并删除一个活动（DELETED状态不能转换到任何其他状态）
        $data = ['title' => 'Activity With Invalid Status'];
        $activity = $this->activityManager->createActivity($data);

        // 手动设置为DELETED状态（因为没有deleteActivity方法直接设为DELETED）
        $activity->setStatus(ActivityStatus::DELETED);

        $em = self::getService(EntityManagerInterface::class);
        $em->flush();

        $this->assertSame(ActivityStatus::DELETED, $activity->getStatus());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Activity cannot transition from deleted to published');

        $this->activityManager->publishActivity($activity);
    }

    public function testArchiveActivity(): void
    {
        // 创建并发布一个活动
        $data = ['title' => 'Activity To Archive'];
        $activity = $this->activityManager->createActivity($data);
        $this->activityManager->publishActivity($activity);

        $this->assertSame(ActivityStatus::PUBLISHED, $activity->getStatus());
        $this->assertNull($activity->getArchiveTime());

        // 归档活动
        $archivedActivity = $this->activityManager->archiveActivity($activity);

        $this->assertSame($activity, $archivedActivity);
        $this->assertSame(ActivityStatus::ARCHIVED, $activity->getStatus());
        $this->assertNotNull($activity->getArchiveTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $activity->getArchiveTime());
    }

    public function testUpdateActivityComponents(): void
    {
        // 创建活动
        $data = ['title' => 'Activity For Component Update'];
        $activity = $this->activityManager->createActivity($data);

        // 添加初始组件
        $initialComponents = [
            [
                'type' => 'text',
                'config' => ['content' => 'Initial text'],
                'position' => 0,
                'visible' => true,
            ],
        ];
        $this->activityManager->updateActivityComponents($activity, $initialComponents);
        $this->assertCount(1, $activity->getComponents());

        // 更新组件
        $newComponents = [
            [
                'type' => 'text',
                'config' => ['content' => 'Updated text content'],
                'position' => 0,
                'visible' => true,
            ],
            [
                'type' => 'image',
                'config' => ['src' => 'updated-image.jpg', 'alt' => 'Updated image'],
                'position' => 1,
                'visible' => true,
            ],
        ];

        $this->activityManager->updateActivityComponents($activity, $newComponents);

        // 刷新实体以获取最新的组件数据
        $em = self::getService(EntityManagerInterface::class);
        $em->refresh($activity);

        $this->assertCount(2, $activity->getComponents());

        $components = $activity->getComponents()->toArray();
        $this->assertNotEmpty($components, 'Components should not be empty after update');
        $this->assertCount(2, $components, 'Should have exactly 2 components');

        $this->assertSame('text', $components[0]->getComponentType());
        $this->assertSame(['content' => 'Updated text content'], $components[0]->getComponentConfig());
        $this->assertSame('image', $components[1]->getComponentType());
        $this->assertSame(['src' => 'updated-image.jpg', 'alt' => 'Updated image'], $components[1]->getComponentConfig());
    }

    public function testProcessExpiredActivities(): void
    {
        // 创建并发布过期的活动
        $expiredActivity1 = $this->activityManager->createActivity(['title' => 'Expired Activity 1']);
        $this->activityManager->publishActivity($expiredActivity1);
        $expiredActivity1->setEndTime(new \DateTimeImmutable('yesterday'));

        $expiredActivity2 = $this->activityManager->createActivity(['title' => 'Expired Activity 2']);
        $this->activityManager->publishActivity($expiredActivity2);
        $expiredActivity2->setEndTime(new \DateTimeImmutable('2 days ago'));

        // 创建未过期的活动作为对照
        $activeActivity = $this->activityManager->createActivity(['title' => 'Active Activity']);
        $this->activityManager->publishActivity($activeActivity);
        $activeActivity->setEndTime(new \DateTimeImmutable('tomorrow'));

        // 手动刷新到数据库
        $em = self::getService(EntityManagerInterface::class);
        $em->flush();

        $this->assertSame(ActivityStatus::PUBLISHED, $expiredActivity1->getStatus());
        $this->assertSame(ActivityStatus::PUBLISHED, $expiredActivity2->getStatus());
        $this->assertSame(ActivityStatus::PUBLISHED, $activeActivity->getStatus());

        // 处理过期活动
        $processedCount = $this->activityManager->processExpiredActivities();

        $this->assertGreaterThanOrEqual(2, $processedCount);

        // 刷新实体状态
        $em->refresh($expiredActivity1);
        $em->refresh($expiredActivity2);
        $em->refresh($activeActivity);

        $this->assertEquals(ActivityStatus::ARCHIVED, $expiredActivity1->getStatus());
        $this->assertEquals(ActivityStatus::ARCHIVED, $expiredActivity2->getStatus());
        $this->assertEquals(ActivityStatus::PUBLISHED, $activeActivity->getStatus());
        $this->assertNotNull($expiredActivity1->getArchiveTime());
        $this->assertNotNull($expiredActivity2->getArchiveTime());
    }
}
