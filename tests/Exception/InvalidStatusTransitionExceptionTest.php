<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TopicActivityBundle\Exception\InvalidStatusTransitionException;

/**
 * @internal
 */
#[CoversClass(InvalidStatusTransitionException::class)]
final class InvalidStatusTransitionExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new InvalidStatusTransitionException('Cannot transition from draft to deleted');

        $this->assertInstanceOf(InvalidStatusTransitionException::class, $exception);
        $this->assertEquals('Cannot transition from draft to deleted', $exception->getMessage());
    }

    public function testExceptionWithDefaultMessage(): void
    {
        $exception = new InvalidStatusTransitionException();

        $this->assertInstanceOf(InvalidStatusTransitionException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }
}
