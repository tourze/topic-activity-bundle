<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TopicActivityBundle\Exception\ActivityStatusTransitionException;

/**
 * @internal
 */
#[CoversClass(ActivityStatusTransitionException::class)]
final class ActivityStatusTransitionExceptionTest extends AbstractExceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup logic if needed
    }

    #[Test]
    public function testCannotTransition(): void
    {
        $exception = ActivityStatusTransitionException::cannotTransition('draft', 'published');

        $this->assertInstanceOf(ActivityStatusTransitionException::class, $exception);
        $this->assertStringContainsString('draft', $exception->getMessage());
        $this->assertStringContainsString('published', $exception->getMessage());
    }

    #[Test]
    public function testExceptionInheritance(): void
    {
        $exception = new ActivityStatusTransitionException('test');

        $this->assertInstanceOf(\LogicException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
