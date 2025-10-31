<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Component\ComponentInterface;
use Tourze\TopicActivityBundle\Component\ComponentRegistry;

/**
 * @internal
 */
#[CoversClass(ComponentRegistry::class)]
class ComponentRegistryTest extends TestCase
{
    private ComponentRegistry $registry;

    private ComponentInterface $mockComponent1;

    private ComponentInterface $mockComponent2;

    private ComponentInterface $mockComponent3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockComponent1 = $this->createMockComponent('button', '按钮', 'basic', 10, true);
        $this->mockComponent2 = $this->createMockComponent('image', '图片', 'basic', 20, true);
        $this->mockComponent3 = $this->createMockComponent('form', '表单', 'form', 30, false);

        $this->registry = new ComponentRegistry([
            $this->mockComponent1,
            $this->mockComponent2,
            $this->mockComponent3,
        ]);
    }

    private function createMockComponent(string $type, string $name, string $category, int $order, bool $visible): ComponentInterface
    {
        $component = $this->createMock(ComponentInterface::class);
        $component->method('getType')->willReturn($type);
        $component->method('getName')->willReturn($name);
        $component->method('getCategory')->willReturn($category);
        $component->method('getIcon')->willReturn('fa fa-' . $type);
        $component->method('getDescription')->willReturn($name . ' component');
        $component->method('getOrder')->willReturn($order);
        $component->method('isVisible')->willReturn($visible);
        $component->method('getDefaultConfig')->willReturn([]);
        $component->method('getConfigSchema')->willReturn([]);

        return $component;
    }

    public function testConstructorRegistersComponents(): void
    {
        $this->assertTrue($this->registry->has('button'));
        $this->assertTrue($this->registry->has('image'));
        $this->assertTrue($this->registry->has('form'));
    }

    public function testRegisterComponent(): void
    {
        $newComponent = $this->createMockComponent('video', '视频', 'media', 40, true);

        $this->registry->register($newComponent);

        $this->assertTrue($this->registry->has('video'));
        $this->assertSame($newComponent, $this->registry->get('video'));
    }

    public function testRegisterDuplicateComponentThrowsException(): void
    {
        $duplicateComponent = $this->createMockComponent('button', '按钮2', 'basic', 50, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Component with type "button" is already registered');

        $this->registry->register($duplicateComponent);
    }

    public function testGet(): void
    {
        $this->assertSame($this->mockComponent1, $this->registry->get('button'));
        $this->assertSame($this->mockComponent2, $this->registry->get('image'));
        $this->assertNull($this->registry->get('nonexistent'));
    }

    public function testHas(): void
    {
        $this->assertTrue($this->registry->has('button'));
        $this->assertTrue($this->registry->has('image'));
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    public function testAll(): void
    {
        $allComponents = $this->registry->all();

        $this->assertIsArray($allComponents);
        $this->assertCount(3, $allComponents);
        $this->assertArrayHasKey('button', $allComponents);
        $this->assertArrayHasKey('image', $allComponents);
        $this->assertArrayHasKey('form', $allComponents);
        $this->assertSame($this->mockComponent1, $allComponents['button']);
    }

    public function testGetByCategory(): void
    {
        $basicComponents = $this->registry->getByCategory('basic');
        $formComponents = $this->registry->getByCategory('form');
        $emptyComponents = $this->registry->getByCategory('nonexistent');

        $this->assertCount(2, $basicComponents);
        $this->assertContains($this->mockComponent1, $basicComponents);
        $this->assertContains($this->mockComponent2, $basicComponents);

        $this->assertCount(1, $formComponents);
        $this->assertContains($this->mockComponent3, $formComponents);

        $this->assertEmpty($emptyComponents);
    }

    public function testGetCategories(): void
    {
        $categories = $this->registry->getCategories();

        $this->assertIsArray($categories);
        $this->assertCount(2, $categories);
        $this->assertContains('basic', $categories);
        $this->assertContains('form', $categories);
    }

    public function testGetVisibleComponents(): void
    {
        $visibleComponents = $this->registry->getVisibleComponents();

        $this->assertCount(2, $visibleComponents);
        $this->assertContains($this->mockComponent1, $visibleComponents);
        $this->assertContains($this->mockComponent2, $visibleComponents);
        $this->assertNotContains($this->mockComponent3, $visibleComponents);
    }

    public function testGetSortedComponents(): void
    {
        $sortedComponents = $this->registry->getSortedComponents();

        $this->assertCount(3, $sortedComponents);
        $this->assertSame($this->mockComponent1, $sortedComponents[0]); // order 10
        $this->assertSame($this->mockComponent2, $sortedComponents[1]); // order 20
        $this->assertSame($this->mockComponent3, $sortedComponents[2]); // order 30
    }

    public function testGetComponentsConfig(): void
    {
        $config = $this->registry->getComponentsConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('button', $config);
        $this->assertArrayHasKey('image', $config);
        $this->assertArrayHasKey('form', $config);

        $buttonConfig = $config['button'];
        $this->assertArrayHasKey('name', $buttonConfig);
        $this->assertArrayHasKey('type', $buttonConfig);
        $this->assertArrayHasKey('category', $buttonConfig);
        $this->assertArrayHasKey('icon', $buttonConfig);
        $this->assertArrayHasKey('description', $buttonConfig);
        $this->assertArrayHasKey('order', $buttonConfig);
        $this->assertArrayHasKey('visible', $buttonConfig);
        $this->assertArrayHasKey('defaultConfig', $buttonConfig);
        $this->assertArrayHasKey('configSchema', $buttonConfig);

        $this->assertSame('按钮', $buttonConfig['name']);
        $this->assertSame('button', $buttonConfig['type']);
        $this->assertSame('basic', $buttonConfig['category']);
        $this->assertSame('fa fa-button', $buttonConfig['icon']);
        $this->assertSame('按钮 component', $buttonConfig['description']);
        $this->assertSame(10, $buttonConfig['order']);
        $this->assertTrue($buttonConfig['visible']);
        $this->assertIsArray($buttonConfig['defaultConfig']);
        $this->assertIsArray($buttonConfig['configSchema']);
    }

    public function testGetComponentsConfigByCategory(): void
    {
        $config = $this->registry->getComponentsConfigByCategory();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('basic', $config);
        $this->assertArrayHasKey('form', $config);

        $basicConfig = $config['basic'];
        $this->assertArrayHasKey('button', $basicConfig);
        $this->assertArrayHasKey('image', $basicConfig);

        $buttonConfig = $basicConfig['button'];
        $this->assertArrayHasKey('name', $buttonConfig);
        $this->assertArrayHasKey('type', $buttonConfig);
        $this->assertArrayHasKey('icon', $buttonConfig);
        $this->assertArrayHasKey('description', $buttonConfig);
        $this->assertArrayHasKey('order', $buttonConfig);

        $this->assertSame('按钮', $buttonConfig['name']);
        $this->assertSame('button', $buttonConfig['type']);
        $this->assertSame('fa fa-button', $buttonConfig['icon']);

        $formConfig = $config['form'];
        $this->assertArrayHasKey('form', $formConfig);
        $this->assertSame('表单', $formConfig['form']['name']);
    }

    public function testEmptyRegistry(): void
    {
        $emptyRegistry = new ComponentRegistry([]);

        $this->assertEmpty($emptyRegistry->all());
        $this->assertEmpty($emptyRegistry->getCategories());
        $this->assertEmpty($emptyRegistry->getVisibleComponents());
        $this->assertEmpty($emptyRegistry->getSortedComponents());
        $this->assertFalse($emptyRegistry->has('anything'));
        $this->assertNull($emptyRegistry->get('anything'));
    }
}
