<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;

/**
 * @internal
 */
#[CoversClass(ActivityComponent::class)]
final class ActivityComponentTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ActivityComponent();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'componentType' => ['componentType', 'test_value'],
            'componentConfig' => ['componentConfig', ['key' => 'value']],
            'position' => ['position', 123],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // No special setup needed for this test
    }

    public function testEntityInitialization(): void
    {
        $component = new ActivityComponent();

        $this->assertNull($component->getId());
        $this->assertNull($component->getActivity());
        $this->assertSame('', $component->getComponentType());
        $this->assertSame([], $component->getComponentConfig());
        $this->assertSame(0, $component->getPosition());
        $this->assertTrue($component->isVisible());
        $this->assertNull($component->getCreateTime());
        $this->assertNull($component->getUpdateTime());
    }

    public function testSettersAndGetters(): void
    {
        $component = new ActivityComponent();
        $activity = new Activity();

        $component->setActivity($activity);
        $this->assertSame($activity, $component->getActivity());

        $component->setComponentType('text');
        $this->assertSame('text', $component->getComponentType());

        $config = ['content' => 'Hello World'];
        $component->setComponentConfig($config);
        $this->assertSame($config, $component->getComponentConfig());

        $component->setPosition(5);
        $this->assertSame(5, $component->getPosition());

        $component->setIsVisible(false);
        $this->assertFalse($component->isVisible());
    }

    public function testConfigValueManagement(): void
    {
        $component = new ActivityComponent();

        // Test getting default value when key doesn't exist
        $this->assertNull($component->getConfigValue('nonexistent'));
        $this->assertSame('default', $component->getConfigValue('nonexistent', 'default'));

        // Test setting and getting config values
        $component->setConfigValue('key1', 'value1');
        $this->assertSame('value1', $component->getConfigValue('key1'));

        $component->setConfigValue('key2', ['nested' => 'value']);
        $this->assertSame(['nested' => 'value'], $component->getConfigValue('key2'));

        // Test that the component config contains both values
        $expectedConfig = [
            'key1' => 'value1',
            'key2' => ['nested' => 'value'],
        ];
        $this->assertSame($expectedConfig, $component->getComponentConfig());
    }

    public function testPositionMovement(): void
    {
        $component = new ActivityComponent();
        $component->setPosition(5);

        $component->moveUp();
        $this->assertSame(4, $component->getPosition());

        $component->moveUp();
        $this->assertSame(3, $component->getPosition());

        $component->moveDown();
        $this->assertSame(4, $component->getPosition());

        $component->moveDown();
        $this->assertSame(5, $component->getPosition());

        // Test that position doesn't go below 0
        $component->setPosition(0);
        $component->moveUp();
        $this->assertSame(0, $component->getPosition());
    }

    public function testDuplicate(): void
    {
        $original = new ActivityComponent();
        $original->setComponentType('button');
        $original->setComponentConfig(['text' => 'Click me']);
        $original->setPosition(10);
        $original->setIsVisible(false);

        $duplicate = $original->duplicate();

        // Check that duplicate has the same values
        $this->assertSame('button', $duplicate->getComponentType());
        $this->assertSame(['text' => 'Click me'], $duplicate->getComponentConfig());
        $this->assertSame(10, $duplicate->getPosition());
        $this->assertFalse($duplicate->isVisible());

        // Check that duplicate is a new instance
        $this->assertNotSame($original, $duplicate);
        $this->assertNull($duplicate->getId());
        $this->assertNull($duplicate->getActivity());
        $this->assertNull($duplicate->getCreateTime());
    }

    public function testRelationshipWithActivity(): void
    {
        $component = new ActivityComponent();
        $activity = new Activity();

        $this->assertNull($component->getActivity());

        $component->setActivity($activity);
        $this->assertSame($activity, $component->getActivity());

        $component->setActivity(null);
        $this->assertNull($component->getActivity());
    }
}
