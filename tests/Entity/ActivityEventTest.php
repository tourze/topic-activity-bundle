<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;

/**
 * @internal
 */
#[CoversClass(ActivityEvent::class)]
final class ActivityEventTest extends AbstractEntityTestCase
{
    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'activityId' => ['activityId', 123],
            'sessionId' => ['sessionId', 'test-session-id'],
            'userId' => ['userId', 456],
            'eventType' => ['eventType', 'view'],
            'eventData' => ['eventData', ['key' => 'value']],
            'clientIp' => ['clientIp', '192.168.1.1'],
            'userAgent' => ['userAgent', 'Mozilla/5.0 Test Browser'],
            'referer' => ['referer', 'https://example.com'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Setup logic if needed
    }

    #[Test]
    public function testEntityInitialization(): void
    {
        $event = ActivityEvent::create(1);

        $this->assertInstanceOf(ActivityEvent::class, $event);
        $this->assertNull($event->getId());
        $this->assertEquals(1, $event->getActivityId());
        $this->assertEquals('', $event->getSessionId());
        $this->assertNull($event->getUserId());
        $this->assertEquals('', $event->getEventType());
        $this->assertNull($event->getEventData());
        $this->assertNull($event->getClientIp());
        $this->assertNull($event->getUserAgent());
        $this->assertNull($event->getReferer());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getCreateTime());
    }

    #[Test]
    public function testSettersAndGetters(): void
    {
        $event = ActivityEvent::create(1);

        $event->setActivityId(123);
        $this->assertEquals(123, $event->getActivityId());

        $event->setSessionId('test-session');
        $this->assertEquals('test-session', $event->getSessionId());

        $event->setUserId(456);
        $this->assertEquals(456, $event->getUserId());

        $event->setEventType(ActivityEvent::EVENT_VIEW);
        $this->assertEquals(ActivityEvent::EVENT_VIEW, $event->getEventType());

        $eventData = ['key' => 'value'];
        $event->setEventData($eventData);
        $this->assertEquals($eventData, $event->getEventData());

        $event->setClientIp('192.168.1.1');
        $this->assertEquals('192.168.1.1', $event->getClientIp());

        $event->setUserAgent('Mozilla/5.0');
        $this->assertEquals('Mozilla/5.0', $event->getUserAgent());

        $event->setReferer('https://example.com');
        $this->assertEquals('https://example.com', $event->getReferer());
    }

    #[Test]
    public function testEventDataValueMethods(): void
    {
        $event = ActivityEvent::create(1);

        // Test getting default value
        $this->assertEquals('default', $event->getEventDataValue('key', 'default'));

        // Test setting and getting value
        $event->setEventDataValue('key', 'value');
        $this->assertEquals('value', $event->getEventDataValue('key'));

        // Test with existing event data
        $event->setEventData(['existing' => 'data']);
        $event->setEventDataValue('new', 'value');
        $this->assertEquals('data', $event->getEventDataValue('existing'));
        $this->assertEquals('value', $event->getEventDataValue('new'));
    }

    #[Test]
    public function testEventTypeCheckers(): void
    {
        $event = ActivityEvent::create(1);

        $event->setEventType(ActivityEvent::EVENT_VIEW);
        $this->assertTrue($event->isViewEvent());
        $this->assertFalse($event->isClickEvent());
        $this->assertFalse($event->isShareEvent());
        $this->assertFalse($event->isFormSubmitEvent());
        $this->assertFalse($event->isConversionEvent());

        $event->setEventType(ActivityEvent::EVENT_CLICK);
        $this->assertFalse($event->isViewEvent());
        $this->assertTrue($event->isClickEvent());
        $this->assertFalse($event->isShareEvent());
        $this->assertFalse($event->isFormSubmitEvent());
        $this->assertFalse($event->isConversionEvent());

        $event->setEventType(ActivityEvent::EVENT_SHARE);
        $this->assertFalse($event->isViewEvent());
        $this->assertFalse($event->isClickEvent());
        $this->assertTrue($event->isShareEvent());
        $this->assertFalse($event->isFormSubmitEvent());
        $this->assertFalse($event->isConversionEvent());

        $event->setEventType(ActivityEvent::EVENT_FORM_SUBMIT);
        $this->assertFalse($event->isViewEvent());
        $this->assertFalse($event->isClickEvent());
        $this->assertFalse($event->isShareEvent());
        $this->assertTrue($event->isFormSubmitEvent());
        $this->assertFalse($event->isConversionEvent());

        $event->setEventType(ActivityEvent::EVENT_CONVERSION);
        $this->assertFalse($event->isViewEvent());
        $this->assertFalse($event->isClickEvent());
        $this->assertFalse($event->isShareEvent());
        $this->assertFalse($event->isFormSubmitEvent());
        $this->assertTrue($event->isConversionEvent());
    }

    #[Test]
    public function testGetDeviceType(): void
    {
        $event = ActivityEvent::create(1);

        // Test unknown device
        $this->assertEquals('unknown', $event->getDeviceType());

        // Test mobile devices
        $event->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');
        $this->assertEquals('mobile', $event->getDeviceType());

        $event->setUserAgent('Mozilla/5.0 (Android 11; Mobile; rv:68.0)');
        $this->assertEquals('mobile', $event->getDeviceType());

        // Test tablet devices
        $event->setUserAgent('Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)');
        $this->assertEquals('tablet', $event->getDeviceType());

        // Test desktop
        $event->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $this->assertEquals('desktop', $event->getDeviceType());
    }

    #[Test]
    public function testGetSource(): void
    {
        $event = ActivityEvent::create(1);

        // Test direct access
        $this->assertEquals('direct', $event->getSource());

        // Test search engines
        $event->setReferer('https://www.google.com/search?q=test');
        $this->assertEquals('search', $event->getSource());

        $event->setReferer('https://www.baidu.com/s?wd=test');
        $this->assertEquals('search', $event->getSource());

        // Test social media
        $event->setReferer('https://www.facebook.com/share');
        $this->assertEquals('social', $event->getSource());

        $event->setReferer('https://twitter.com/intent/tweet');
        $this->assertEquals('social', $event->getSource());

        // Test referral
        $event->setReferer('https://example.com/page');
        $this->assertEquals('referral', $event->getSource());

        // Test invalid referer
        $event->setReferer('invalid-url');
        $this->assertEquals('unknown', $event->getSource());
    }

    #[Test]
    public function testToString(): void
    {
        $event = ActivityEvent::create(1);
        $event->setEventType(ActivityEvent::EVENT_VIEW);

        $this->assertStringContainsString(ActivityEvent::EVENT_VIEW, $event->__toString());
        $this->assertStringContainsString('#', $event->__toString());
    }

    /**
     * {@inheritDoc}
     */
    protected function createEntity(): object
    {
        return ActivityEvent::create(1);
    }
}
