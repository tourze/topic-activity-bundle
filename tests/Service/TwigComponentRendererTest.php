<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Service\TwigComponentRenderer;

/**
 * @internal
 */
#[CoversClass(TwigComponentRenderer::class)]
#[RunTestsInSeparateProcesses]
class TwigComponentRendererTest extends AbstractIntegrationTestCase
{
    private TwigComponentRenderer $twigComponentRenderer;

    protected function onSetUp(): void
    {
        $this->twigComponentRenderer = self::getService(TwigComponentRenderer::class);
    }

    public function testRenderComponent(): void
    {
        $component = new ActivityComponent();
        $component->setComponentType('text');
        $component->setComponentConfig(['content' => 'Test content']);
        $component->setIsVisible(true);

        $result = $this->twigComponentRenderer->renderComponent($component);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testRenderComponentNotVisible(): void
    {
        $component = new ActivityComponent();
        $component->setComponentType('text');
        $component->setComponentConfig(['content' => 'Test content']);
        $component->setIsVisible(false);

        $result = $this->twigComponentRenderer->renderComponent($component);

        $this->assertSame('', $result);
    }

    public function testRenderComponentUnknownType(): void
    {
        $component = new ActivityComponent();
        $component->setComponentType('unknown_type');
        $component->setComponentConfig(['content' => 'Test content']);
        $component->setIsVisible(true);

        $result = $this->twigComponentRenderer->renderComponent($component);

        $this->assertStringContainsString('Component Error [unknown_type]: Unknown component type', $result);
        $this->assertStringContainsString('alert alert-danger', $result);
    }

    public function testRenderMultiple(): void
    {
        $component1 = new ActivityComponent();
        $component1->setComponentType('text');
        $component1->setComponentConfig(['content' => 'Content 1']);
        $component1->setIsVisible(true);

        $component2 = new ActivityComponent();
        $component2->setComponentType('image');
        $component2->setComponentConfig(['src' => 'image.jpg']);
        $component2->setIsVisible(true);

        $result = $this->twigComponentRenderer->renderMultiple([$component1, $component2]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testRenderMultipleEmptyArray(): void
    {
        $result = $this->twigComponentRenderer->renderMultiple([]);

        $this->assertSame('', $result);
    }

    public function testRenderByType(): void
    {
        $config = ['content' => 'Test content'];

        $result = $this->twigComponentRenderer->renderByType('button', $config);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testRenderByTypeUnknown(): void
    {
        $result = $this->twigComponentRenderer->renderByType('unknown_type', []);

        $this->assertStringContainsString('Component Error [unknown_type]: Unknown component type', $result);
        $this->assertStringContainsString('alert alert-danger', $result);
    }

    public function testRenderByTypeWithEmptyConfig(): void
    {
        $result = $this->twigComponentRenderer->renderByType('text');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testServiceInstanceIsCorrect(): void
    {
        $this->assertInstanceOf(TwigComponentRenderer::class, $this->twigComponentRenderer);
    }
}
