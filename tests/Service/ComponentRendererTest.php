<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\ComponentRenderer;

/**
 * @internal
 */
#[CoversClass(ComponentRenderer::class)]
#[RunTestsInSeparateProcesses]
class ComponentRendererTest extends AbstractIntegrationTestCase
{
    private ComponentRenderer $componentRenderer;

    private ActivityRepository $activityRepository;

    protected function onSetUp(): void
    {
        $this->componentRenderer = self::getService(ComponentRenderer::class);
        $this->activityRepository = self::getService(ActivityRepository::class);
    }

    public function testRenderTextComponent(): void
    {
        $activityComponent = new ActivityComponent();
        $activityComponent->setComponentType('text');
        $activityComponent->setComponentConfig(['content' => 'Integration Test Content']);
        $activityComponent->setIsVisible(true);

        $result = $this->componentRenderer->render($activityComponent);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Integration Test Content', $result);
    }

    public function testRenderUnknownComponentType(): void
    {
        $activityComponent = new ActivityComponent();
        $activityComponent->setComponentType('unknown-type');
        $activityComponent->setComponentConfig([]);
        $activityComponent->setIsVisible(true);

        $result = $this->componentRenderer->render($activityComponent);

        $this->assertIsString($result);
        $this->assertStringContainsString('Component Error', $result);
        $this->assertStringContainsString('unknown-type', $result);
    }

    public function testRenderInvisibleComponent(): void
    {
        $activityComponent = new ActivityComponent();
        $activityComponent->setComponentType('text');
        $activityComponent->setComponentConfig(['content' => 'Hidden Content']);
        $activityComponent->setIsVisible(false);

        $result = $this->componentRenderer->render($activityComponent);

        $this->assertSame('', $result);
    }

    public function testRenderTextComponentWithEmptyContent(): void
    {
        $activityComponent = new ActivityComponent();
        $activityComponent->setComponentType('text');
        $activityComponent->setComponentConfig(['content' => '']);
        $activityComponent->setIsVisible(true);

        $result = $this->componentRenderer->render($activityComponent);

        // Empty content may result in error or empty render depending on component implementation
        $this->assertIsString($result);
    }

    public function testRenderImageComponent(): void
    {
        $activityComponent = new ActivityComponent();
        $activityComponent->setComponentType('image');
        $activityComponent->setComponentConfig([
            'src' => 'https://example.com/test-image.jpg',
            'alt' => 'Test Image',
        ]);
        $activityComponent->setIsVisible(true);

        $result = $this->componentRenderer->render($activityComponent);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testRenderMultipleComponents(): void
    {
        $component1 = new ActivityComponent();
        $component1->setComponentType('text');
        $component1->setComponentConfig(['content' => 'First Component']);
        $component1->setIsVisible(true);

        $component2 = new ActivityComponent();
        $component2->setComponentType('text');
        $component2->setComponentConfig(['content' => 'Second Component']);
        $component2->setIsVisible(true);

        $result = $this->componentRenderer->renderMultiple([$component1, $component2]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('First Component', $result);
        $this->assertStringContainsString('Second Component', $result);
    }

    public function testRenderToArray(): void
    {
        $activity = new Activity();
        $activity->setTitle('Test Activity for Array Render');

        $component1 = new ActivityComponent();
        $component1->setComponentType('text');
        $component1->setComponentConfig(['content' => 'Array Test First']);
        $component1->setIsVisible(true);
        $activity->addComponent($component1);

        $component2 = new ActivityComponent();
        $component2->setComponentType('text');
        $component2->setComponentConfig(['content' => 'Array Test Second']);
        $component2->setIsVisible(true);
        $activity->addComponent($component2);

        // 保存到数据库获取ID
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($activity);
        $em->persist($component1);
        $em->persist($component2);
        $em->flush();

        $result = $this->componentRenderer->renderToArray([$component1, $component2]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey((string) $component1->getId(), $result);
        $this->assertArrayHasKey((string) $component2->getId(), $result);
        $this->assertStringContainsString('Array Test First', $result[(string) $component1->getId()]);
        $this->assertStringContainsString('Array Test Second', $result[(string) $component2->getId()]);
    }

    public function testRenderByType(): void
    {
        $config = ['content' => 'Direct Type Render Test'];

        $result = $this->componentRenderer->renderByType('text', $config);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Direct Type Render Test', $result);
    }

    public function testRenderByTypeNotFound(): void
    {
        $result = $this->componentRenderer->renderByType('non-existent-type');

        $this->assertIsString($result);
        $this->assertStringContainsString('Component Error', $result);
        $this->assertStringContainsString('non-existent-type', $result);
    }

    public function testRenderButtonComponent(): void
    {
        $config = [
            'text' => 'Click Me',
            'url' => 'https://example.com',
            'style' => 'primary',
        ];

        $result = $this->componentRenderer->renderByType('button', $config);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testRenderVideoComponent(): void
    {
        $config = [
            'url' => 'https://example.com/video.mp4',
            'poster' => 'https://example.com/poster.jpg',
            'autoplay' => false,
        ];

        $result = $this->componentRenderer->renderByType('video', $config);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testPreviewComponent(): void
    {
        $config = ['content' => 'Preview Test Content'];

        $result = $this->componentRenderer->previewComponent('text', $config);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('config', $result);
        $this->assertSame('text', $result['type']);
        $this->assertSame($config, $result['config']);

        if (isset($result['success']) && true === $result['success']) {
            $this->assertIsString($result['html']);
            $this->assertStringContainsString('Preview Test Content', $result['html']);
        }
    }

    public function testPreviewComponentNotFound(): void
    {
        $result = $this->componentRenderer->previewComponent('nonexistent');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('html', $result);
    }

    public function testPreviewWithComplexConfig(): void
    {
        $config = [
            'content' => 'Complex preview test',
            'style' => 'bold',
            'color' => '#FF0000',
        ];

        $result = $this->componentRenderer->previewComponent('text', $config);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertSame('text', $result['type']);
        $this->assertSame($config, $result['config']);
    }

    public function testRenderWithRealActivity(): void
    {
        // 创建真实的活动和组件
        $activity = new Activity();
        $activity->setTitle('Integration Test Activity');

        $textComponent = new ActivityComponent();
        $textComponent->setComponentType('text');
        $textComponent->setComponentConfig(['content' => 'Real activity component']);
        $textComponent->setIsVisible(true);
        $textComponent->setPosition(0);
        $activity->addComponent($textComponent);

        // 保存到数据库
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($activity);
        $em->persist($textComponent);
        $em->flush();

        // 渲染组件
        $result = $this->componentRenderer->render($textComponent);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Real activity component', $result);
    }

    public function testRenderComponent(): void
    {
        // 创建活动
        $activity = new Activity();
        $activity->setTitle('Component Test Activity');
        $activity->setSlug('component-test-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $this->activityRepository->save($activity, true);

        // 创建活动组件
        $textComponent = new ActivityComponent();
        $textComponent->setActivity($activity);
        $textComponent->setComponentType('text');
        $textComponent->setComponentConfig(['content' => 'Test renderComponent alias']);
        $textComponent->setPosition(1);
        $textComponent->setIsVisible(true);

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($textComponent);
        $em->flush();

        // 测试 renderComponent 方法（这是 render 方法的别名）
        $result = $this->componentRenderer->renderComponent($textComponent);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
