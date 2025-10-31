<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Exception\InvalidStatusTransitionException;

/**
 * @internal
 */
#[CoversClass(Activity::class)]
final class ActivityTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Activity();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'title' => ['title', 'Test Activity'],
            'slug' => ['slug', 'test-activity-' . uniqid()],
            'description' => ['description', 'Test description'],
            'coverImage' => ['coverImage', '/uploads/test.jpg'],
            'layoutConfig' => ['layoutConfig', ['components' => []]],
            'seoConfig' => ['seoConfig', ['title' => 'SEO Title']],
            'shareConfig' => ['shareConfig', ['title' => 'Share Title']],
            'accessConfig' => ['accessConfig', ['password' => 'secret']],
            'templateId' => ['templateId', 123],
            'createdBy' => ['createdBy', '1'],
            'updatedBy' => ['updatedBy', '2'],
        ];
    }

    public function testEntityInitialization(): void
    {
        $activity = new Activity();

        $this->assertNull($activity->getId());
        $this->assertNotEmpty($activity->getUuid());
        $this->assertSame('', $activity->getTitle());
        $this->assertNull($activity->getSlug());
        $this->assertNull($activity->getDescription());
        $this->assertNull($activity->getCoverImage());
        $this->assertSame(ActivityStatus::DRAFT, $activity->getStatus());
        $this->assertSame([], $activity->getLayoutConfig());
        $this->assertNull($activity->getCreateTime());
        $this->assertCount(0, $activity->getComponents());
        $this->assertCount(0, $activity->getStats());
    }

    public function testSettersAndGetters(): void
    {
        $activity = new Activity();

        $activity->setTitle('Test Activity');
        $this->assertSame('Test Activity', $activity->getTitle());

        $slug = 'test-activity-' . uniqid();
        $activity->setSlug($slug);
        $this->assertSame($slug, $activity->getSlug());

        $activity->setDescription('Test description');
        $this->assertSame('Test description', $activity->getDescription());

        $activity->setCoverImage('/uploads/test.jpg');
        $this->assertSame('/uploads/test.jpg', $activity->getCoverImage());

        $layoutConfig = ['components' => []];
        $activity->setLayoutConfig($layoutConfig);
        $this->assertSame($layoutConfig, $activity->getLayoutConfig());

        $seoConfig = ['title' => 'SEO Title'];
        $activity->setSeoConfig($seoConfig);
        $this->assertSame($seoConfig, $activity->getSeoConfig());

        $shareConfig = ['title' => 'Share Title'];
        $activity->setShareConfig($shareConfig);
        $this->assertSame($shareConfig, $activity->getShareConfig());

        $accessConfig = ['password' => 'secret'];
        $activity->setAccessConfig($accessConfig);
        $this->assertSame($accessConfig, $activity->getAccessConfig());

        $activity->setTemplateId(123);
        $this->assertSame(123, $activity->getTemplateId());

        $activity->setCreatedBy('1');
        $this->assertSame('1', $activity->getCreatedBy());

        $activity->setUpdatedBy('2');
        $this->assertSame('2', $activity->getUpdatedBy());
    }

    public function testDateTimeFields(): void
    {
        $activity = new Activity();

        $startTime = new \DateTimeImmutable('2025-01-01 00:00:00');
        $activity->setStartTime($startTime);
        $this->assertSame($startTime, $activity->getStartTime());

        $endTime = new \DateTimeImmutable('2025-12-31 23:59:59');
        $activity->setEndTime($endTime);
        $this->assertSame($endTime, $activity->getEndTime());

        $this->assertNull($activity->getPublishTime());
        $this->assertNull($activity->getArchiveTime());
        $this->assertNull($activity->getUpdateTime());
        $this->assertNull($activity->getDeleteTime());
    }

    public function testStatusTransitions(): void
    {
        $activity = new Activity();

        // Initial status is DRAFT
        $this->assertSame(ActivityStatus::DRAFT, $activity->getStatus());

        // Can transition from DRAFT to PUBLISHED
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $this->assertSame(ActivityStatus::PUBLISHED, $activity->getStatus());
        $this->assertNotNull($activity->getPublishTime());

        // Can transition from PUBLISHED to ARCHIVED
        $activity->setStatus(ActivityStatus::ARCHIVED);
        $this->assertSame(ActivityStatus::ARCHIVED, $activity->getStatus());
        $this->assertNotNull($activity->getArchiveTime());
    }

    public function testInvalidStatusTransition(): void
    {
        $activity = new Activity();

        // Use reflection to set ID to simulate a persisted entity
        $reflection = new \ReflectionClass($activity);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($activity, 1);

        // Transition to deleted
        $activity->setStatus(ActivityStatus::DELETED);

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Cannot transition from deleted to published');

        // Try to transition from deleted to published (which is not allowed)
        $activity->setStatus(ActivityStatus::PUBLISHED);
    }

    public function testComponentRelationship(): void
    {
        $activity = new Activity();
        $component = new ActivityComponent();
        $component->setComponentType('text');
        $component->setPosition(0);

        $activity->addComponent($component);

        $this->assertCount(1, $activity->getComponents());
        $this->assertTrue($activity->getComponents()->contains($component));
        $this->assertSame($activity, $component->getActivity());

        $activity->removeComponent($component);

        $this->assertCount(0, $activity->getComponents());
        $this->assertNull($component->getActivity());
    }

    public function testIsActive(): void
    {
        $activity = new Activity();

        // Not active when status is DRAFT
        $this->assertFalse($activity->isActive());

        // Active when published with no time constraints
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $this->assertTrue($activity->isActive());

        // Not active when published but before start time
        $activity->setStartTime(new \DateTimeImmutable('+1 day'));
        $this->assertFalse($activity->isActive());

        // Active when published and within time range
        $activity->setStartTime(new \DateTimeImmutable('-1 day'));
        $activity->setEndTime(new \DateTimeImmutable('+1 day'));
        $this->assertTrue($activity->isActive());

        // Not active when published but after end time
        $activity->setStartTime(new \DateTimeImmutable('-2 days'));
        $activity->setEndTime(new \DateTimeImmutable('-1 day'));
        $this->assertFalse($activity->isActive());
    }

    public function testIsDeleted(): void
    {
        $activity = new Activity();

        $this->assertFalse($activity->isDeleted());

        $activity->setDeleteTime(new \DateTimeImmutable());
        $this->assertTrue($activity->isDeleted());

        $activity->setDeleteTime(null);
        $this->assertFalse($activity->isDeleted());
    }

    public function testUuidIsUnique(): void
    {
        $activity1 = new Activity();
        $activity2 = new Activity();

        $this->assertNotSame($activity1->getUuid(), $activity2->getUuid());
    }
}
