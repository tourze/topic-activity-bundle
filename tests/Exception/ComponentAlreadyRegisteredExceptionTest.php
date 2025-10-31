<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TopicActivityBundle\Exception\ComponentAlreadyRegisteredException;

/**
 * @internal
 */
#[CoversClass(ComponentAlreadyRegisteredException::class)]
final class ComponentAlreadyRegisteredExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new ComponentAlreadyRegisteredException('button');

        $this->assertInstanceOf(ComponentAlreadyRegisteredException::class, $exception);
        $this->assertEquals('Component with type "button" is already registered', $exception->getMessage());
    }

    public function testExceptionWithDifferentType(): void
    {
        $exception = new ComponentAlreadyRegisteredException('image');

        $this->assertInstanceOf(ComponentAlreadyRegisteredException::class, $exception);
        $this->assertEquals('Component with type "image" is already registered', $exception->getMessage());
    }
}
