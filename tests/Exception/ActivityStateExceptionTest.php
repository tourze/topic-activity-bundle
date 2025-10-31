<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TopicActivityBundle\Exception\ActivityStateException;

/**
 * @internal
 */
#[CoversClass(ActivityStateException::class)]
final class ActivityStateExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup logic if needed
    }

    #[Test]
    public function testNotDeleted(): void
    {
        $exception = ActivityStateException::notDeleted();

        $this->assertInstanceOf(ActivityStateException::class, $exception);
        $this->assertEquals('Activity is not deleted', $exception->getMessage());
    }

    #[Test]
    public function testAlreadyArchived(): void
    {
        $exception = ActivityStateException::alreadyArchived();

        $this->assertInstanceOf(ActivityStateException::class, $exception);
        $this->assertEquals('Activity is already archived', $exception->getMessage());
    }

    #[Test]
    public function testExceptionInheritance(): void
    {
        $exception = new ActivityStateException('test');

        $this->assertInstanceOf(\LogicException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
