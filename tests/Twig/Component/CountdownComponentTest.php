<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\CountdownComponent;

/**
 * @internal
 */
#[CoversClass(CountdownComponent::class)]
final class CountdownComponentTest extends TestCase
{
    private CountdownComponent $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new CountdownComponent();
    }

    public function testDefaultProperties(): void
    {
        self::assertSame('', $this->component->endTime);
        self::assertSame('DD天 HH时 MM分 SS秒', $this->component->format);
        self::assertTrue($this->component->showDays);
        self::assertTrue($this->component->showHours);
        self::assertTrue($this->component->showMinutes);
        self::assertTrue($this->component->showSeconds);
        self::assertSame('活动已结束', $this->component->expiredText);
        self::assertSame('', $this->component->className);
    }

    public function testSetProperties(): void
    {
        $endTime = '2025-12-31 23:59:59';
        $this->component->endTime = $endTime;
        $this->component->format = 'DD:HH:MM:SS';
        $this->component->showDays = false;
        $this->component->showHours = false;
        $this->component->showMinutes = true;
        $this->component->showSeconds = true;
        $this->component->expiredText = 'Countdown expired';
        $this->component->className = 'custom-countdown';

        self::assertSame($endTime, $this->component->endTime);
        self::assertSame('DD:HH:MM:SS', $this->component->format);
        self::assertFalse($this->component->showDays);
        self::assertFalse($this->component->showHours);
        self::assertTrue($this->component->showMinutes);
        self::assertTrue($this->component->showSeconds);
        self::assertSame('Countdown expired', $this->component->expiredText);
        self::assertSame('custom-countdown', $this->component->className);
    }
}
