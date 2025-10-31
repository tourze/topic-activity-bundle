<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;

/**
 * @internal
 */
#[CoversClass(ActivityTemplate::class)]
final class ActivityTemplateTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ActivityTemplate();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'name' => ['name', 'test_value'],
            'code' => ['code', 'test_value'],
            'category' => ['category', 'test_value'],
            'layoutConfig' => ['layoutConfig', ['key' => 'value']],
            'defaultData' => ['defaultData', ['key' => 'value']],
            'tags' => ['tags', ['key' => 'value']],
            'active' => ['active', true],
            'system' => ['system', true],
            'usageCount' => ['usageCount', 123],
        ];
    }

    public function testEntityCreation(): void
    {
        $template = new ActivityTemplate();

        $this->assertInstanceOf(ActivityTemplate::class, $template);
        $this->assertEquals('', $template->getName());
        $this->assertEquals('', $template->getDescription());
        $this->assertEquals('general', $template->getCategory());
        $this->assertEquals('', $template->getThumbnail());
        $this->assertEquals([], $template->getLayoutConfig());
        $this->assertEquals([], $template->getDefaultData());
        $this->assertEquals([], $template->getTags());
        $this->assertTrue($template->isActive());
        $this->assertFalse($template->isSystem());
        $this->assertEquals(0, $template->getUsageCount());
    }

    public function testIncrementUsageCount(): void
    {
        $template = new ActivityTemplate();

        $this->assertEquals(0, $template->getUsageCount());

        $template->incrementUsageCount();
        $this->assertEquals(1, $template->getUsageCount());

        $template->incrementUsageCount();
        $template->incrementUsageCount();
        $template->incrementUsageCount();
        $template->incrementUsageCount();
        $template->incrementUsageCount();
        $this->assertEquals(6, $template->getUsageCount());
    }

    public function testToString(): void
    {
        $template = new ActivityTemplate();
        $template->setName('Test Template');

        $this->assertStringContainsString('Test Template', $template->__toString());
    }

    public function testTagManagement(): void
    {
        $template = new ActivityTemplate();

        $tags = ['marketing', 'promotion', 'lead-generation'];
        $template->setTags($tags);

        $this->assertEquals($tags, $template->getTags());
        $this->assertContains('marketing', $template->getTags());
        $this->assertNotContains('sales', $template->getTags());
    }

    public function testLayoutConfig(): void
    {
        $template = new ActivityTemplate();

        $config = [
            'sections' => [
                [
                    'id' => 'header',
                    'components' => ['title', 'subtitle'],
                ],
                [
                    'id' => 'content',
                    'components' => ['main-content', 'image'],
                ],
            ],
        ];

        $template->setLayoutConfig($config);
        $this->assertEquals($config, $template->getLayoutConfig());
        $this->assertIsArray($template->getLayoutConfig());
        $this->assertArrayHasKey('sections', $template->getLayoutConfig());
    }

    public function testDefaultData(): void
    {
        $template = new ActivityTemplate();

        $data = [
            'title' => 'Default Campaign Title',
            'description' => 'Default campaign description',
            'settings' => [
                'autoPublish' => false,
                'trackingEnabled' => true,
            ],
        ];

        $template->setDefaultData($data);
        $this->assertEquals($data, $template->getDefaultData());
        $defaultData = $template->getDefaultData();
        $this->assertIsArray($defaultData);
        $this->assertIsString($defaultData['title']);
        $this->assertIsArray($defaultData['settings']);
        $this->assertIsBool($defaultData['settings']['trackingEnabled']);
        $this->assertEquals('Default Campaign Title', $defaultData['title']);
        $this->assertTrue($defaultData['settings']['trackingEnabled']);
    }

    public function testSystemTemplate(): void
    {
        $template = new ActivityTemplate();

        // Test default values
        $this->assertFalse($template->isSystem());
        $this->assertTrue($template->isActive());

        // Test setting system template
        $template->setSystem(true);
        $this->assertTrue($template->isSystem());

        // Test deactivating template
        $template->setActive(false);
        $this->assertFalse($template->isActive());
    }
}
