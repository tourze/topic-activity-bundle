<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;

/**
 * @internal
 */
#[CoversClass(ActivityStatus::class)]
final class ActivityStatusTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('草稿', ActivityStatus::DRAFT->getLabel());
        $this->assertSame('待发布', ActivityStatus::SCHEDULED->getLabel());
        $this->assertSame('已发布', ActivityStatus::PUBLISHED->getLabel());
        $this->assertSame('已下架', ActivityStatus::ARCHIVED->getLabel());
        $this->assertSame('已删除', ActivityStatus::DELETED->getLabel());
    }

    public function testGetColor(): void
    {
        $this->assertSame('secondary', ActivityStatus::DRAFT->getColor());
        $this->assertSame('warning', ActivityStatus::SCHEDULED->getColor());
        $this->assertSame('success', ActivityStatus::PUBLISHED->getColor());
        $this->assertSame('info', ActivityStatus::ARCHIVED->getColor());
        $this->assertSame('danger', ActivityStatus::DELETED->getColor());
    }

    #[TestWith([ActivityStatus::DRAFT, ActivityStatus::SCHEDULED, true])]
    #[TestWith([ActivityStatus::DRAFT, ActivityStatus::PUBLISHED, true])]
    #[TestWith([ActivityStatus::DRAFT, ActivityStatus::DELETED, true])]
    #[TestWith([ActivityStatus::DRAFT, ActivityStatus::ARCHIVED, false])]
    #[TestWith([ActivityStatus::DRAFT, ActivityStatus::DRAFT, false])]
    #[TestWith([ActivityStatus::SCHEDULED, ActivityStatus::DRAFT, true])]
    #[TestWith([ActivityStatus::SCHEDULED, ActivityStatus::PUBLISHED, true])]
    #[TestWith([ActivityStatus::SCHEDULED, ActivityStatus::DELETED, true])]
    #[TestWith([ActivityStatus::SCHEDULED, ActivityStatus::ARCHIVED, false])]
    #[TestWith([ActivityStatus::SCHEDULED, ActivityStatus::SCHEDULED, false])]
    #[TestWith([ActivityStatus::PUBLISHED, ActivityStatus::DRAFT, true])]
    #[TestWith([ActivityStatus::PUBLISHED, ActivityStatus::ARCHIVED, true])]
    #[TestWith([ActivityStatus::PUBLISHED, ActivityStatus::SCHEDULED, false])]
    #[TestWith([ActivityStatus::PUBLISHED, ActivityStatus::DELETED, false])]
    #[TestWith([ActivityStatus::PUBLISHED, ActivityStatus::PUBLISHED, false])]
    #[TestWith([ActivityStatus::ARCHIVED, ActivityStatus::PUBLISHED, true])]
    #[TestWith([ActivityStatus::ARCHIVED, ActivityStatus::DELETED, true])]
    #[TestWith([ActivityStatus::ARCHIVED, ActivityStatus::DRAFT, false])]
    #[TestWith([ActivityStatus::ARCHIVED, ActivityStatus::SCHEDULED, false])]
    #[TestWith([ActivityStatus::ARCHIVED, ActivityStatus::ARCHIVED, false])]
    #[TestWith([ActivityStatus::DELETED, ActivityStatus::DRAFT, false])]
    #[TestWith([ActivityStatus::DELETED, ActivityStatus::SCHEDULED, false])]
    #[TestWith([ActivityStatus::DELETED, ActivityStatus::PUBLISHED, false])]
    #[TestWith([ActivityStatus::DELETED, ActivityStatus::ARCHIVED, false])]
    #[TestWith([ActivityStatus::DELETED, ActivityStatus::DELETED, false])]
    public function testCanTransitionTo(ActivityStatus $from, ActivityStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function testIsEditable(): void
    {
        $this->assertTrue(ActivityStatus::DRAFT->isEditable());
        $this->assertTrue(ActivityStatus::SCHEDULED->isEditable());
        $this->assertTrue(ActivityStatus::PUBLISHED->isEditable());
        $this->assertFalse(ActivityStatus::ARCHIVED->isEditable());
        $this->assertFalse(ActivityStatus::DELETED->isEditable());
    }

    public function testIsVisible(): void
    {
        $this->assertFalse(ActivityStatus::DRAFT->isVisible());
        $this->assertFalse(ActivityStatus::SCHEDULED->isVisible());
        $this->assertTrue(ActivityStatus::PUBLISHED->isVisible());
        $this->assertFalse(ActivityStatus::ARCHIVED->isVisible());
        $this->assertFalse(ActivityStatus::DELETED->isVisible());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('draft', ActivityStatus::DRAFT->value);
        $this->assertSame('scheduled', ActivityStatus::SCHEDULED->value);
        $this->assertSame('published', ActivityStatus::PUBLISHED->value);
        $this->assertSame('archived', ActivityStatus::ARCHIVED->value);
        $this->assertSame('deleted', ActivityStatus::DELETED->value);
    }

    public function testToArray(): void
    {
        $result = ActivityStatus::DRAFT->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);

        $this->assertSame('draft', $result['value']);
        $this->assertSame('草稿', $result['label']);
    }

    public function testAllCasesAreCovered(): void
    {
        $cases = ActivityStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(ActivityStatus::DRAFT, $cases);
        $this->assertContains(ActivityStatus::SCHEDULED, $cases);
        $this->assertContains(ActivityStatus::PUBLISHED, $cases);
        $this->assertContains(ActivityStatus::ARCHIVED, $cases);
        $this->assertContains(ActivityStatus::DELETED, $cases);
    }
}
