<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component\Basic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\TopicActivityBundle\Component\Basic\SpacerComponent;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(SpacerComponent::class)]
class SpacerComponentTest extends TestCase
{
    private SpacerComponent $component;

    private Environment $twig;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->createMock(Environment::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->component = new SpacerComponent($this->twig, $this->validator);
    }

    public function testGetType(): void
    {
        $this->assertSame('spacer', $this->component->getType());
    }

    public function testGetName(): void
    {
        $this->assertSame('间距', $this->component->getName());
    }

    public function testGetCategory(): void
    {
        $this->assertSame('basic', $this->component->getCategory());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('fa fa-arrows-v', $this->component->getIcon());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('用于添加元素间的空白间距', $this->component->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertSame(50, $this->component->getOrder());
    }

    public function testGetDefaultConfig(): void
    {
        $defaultConfig = $this->component->getDefaultConfig();

        $this->assertIsArray($defaultConfig);
        $this->assertArrayHasKey('height', $defaultConfig);
        $this->assertArrayHasKey('backgroundColor', $defaultConfig);
        $this->assertArrayHasKey('className', $defaultConfig);

        $this->assertSame('20px', $defaultConfig['height']);
        $this->assertSame('transparent', $defaultConfig['backgroundColor']);
        $this->assertSame('', $defaultConfig['className']);
    }

    public function testGetConfigSchema(): void
    {
        $schema = $this->component->getConfigSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('height', $schema);
        $this->assertArrayHasKey('backgroundColor', $schema);
        $this->assertArrayHasKey('className', $schema);

        $this->assertSame('string', $schema['height']['type']);
        $this->assertFalse($schema['height']['required']);
        $this->assertSame('高度', $schema['height']['label']);
        $this->assertSame('20px', $schema['height']['default']);

        $this->assertSame('string', $schema['backgroundColor']['type']);
        $this->assertFalse($schema['backgroundColor']['required']);
        $this->assertSame('背景颜色', $schema['backgroundColor']['label']);
        $this->assertSame('color', $schema['backgroundColor']['editor']);
        $this->assertSame('transparent', $schema['backgroundColor']['default']);

        $this->assertSame('string', $schema['className']['type']);
        $this->assertFalse($schema['className']['required']);
        $this->assertSame('自定义样式类', $schema['className']['label']);
    }

    public function testRender(): void
    {
        $config = ['height' => '30px', 'backgroundColor' => '#f0f0f0'];
        $expectedHtml = '<div class="spacer" style="height: 30px; background-color: #f0f0f0;"></div>';

        /** @phpstan-ignore method.nonObject, method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@TopicActivity/components/spacer.html.twig',
                /** @phpstan-ignore staticMethod.dynamicCall */
                $this->callback(static function (mixed $params): bool {
                    if (!is_array($params)) {
                        return false;
                    }
                    if (!isset($params['config']) || !is_array($params['config'])) {
                        return false;
                    }
                    if (!isset($params['config']['height']) || '30px' !== $params['config']['height']) {
                        return false;
                    }
                    if (!isset($params['config']['backgroundColor']) || '#f0f0f0' !== $params['config']['backgroundColor']) {
                        return false;
                    }

                    return isset($params['component']);
                })
            )
            ->willReturn($expectedHtml)
        ;

        $result = $this->component->render($config);
        $this->assertSame($expectedHtml, $result);
    }

    public function testRenderWithException(): void
    {
        /** @phpstan-ignore method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->willThrowException(new \Exception('Template error'))
        ;

        $result = $this->component->render();
        $this->assertStringContainsString('Component render error: Template error', $result);
    }

    public function testValidate(): void
    {
        $config = [];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);

        $config = ['height' => '25px', 'backgroundColor' => '#ffffff'];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);

        $config = ['height' => 123];
        $errors = $this->component->validate($config);
        $this->assertArrayHasKey('height', $errors);
        $this->assertStringContainsString('must be of type string', $errors['height']);
    }
}
