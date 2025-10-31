<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component\Marketing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\TopicActivityBundle\Component\Marketing\CountdownComponent;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(CountdownComponent::class)]
class CountdownComponentTest extends TestCase
{
    private CountdownComponent $component;

    private Environment $twig;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->createMock(Environment::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->component = new CountdownComponent($this->twig, $this->validator);
    }

    public function testGetName(): void
    {
        $this->assertSame('倒计时', $this->component->getName());
    }

    public function testGetType(): void
    {
        $this->assertSame('countdown', $this->component->getType());
    }

    public function testGetCategory(): void
    {
        $this->assertSame('marketing', $this->component->getCategory());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('fa fa-clock', $this->component->getIcon());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('活动倒计时显示', $this->component->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertSame(110, $this->component->getOrder());
    }

    public function testIsVisible(): void
    {
        $this->assertTrue($this->component->isVisible());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->component->supports('1.0'));
        $this->assertTrue($this->component->supports('2.0'));
    }

    public function testGetDefaultConfig(): void
    {
        $expected = [
            'endTime' => '',
            'format' => 'DD天 HH时 MM分 SS秒',
            'showDays' => true,
            'showHours' => true,
            'showMinutes' => true,
            'showSeconds' => true,
            'prefix' => '距离活动结束还有',
            'suffix' => '',
            'expiredText' => '活动已结束',
            'fontSize' => '24px',
            'color' => '#ff0000',
            'backgroundColor' => '#fff',
            'padding' => '20px',
            'borderRadius' => '8px',
            'className' => '',
        ];

        $this->assertSame($expected, $this->component->getDefaultConfig());
    }

    public function testGetConfigSchema(): void
    {
        $schema = $this->component->getConfigSchema();

        // 测试必填字段
        $this->assertArrayHasKey('endTime', $schema);
        $this->assertSame('string', $schema['endTime']['type']);
        $this->assertTrue($schema['endTime']['required']);
        $this->assertSame('结束时间', $schema['endTime']['label']);
        $this->assertSame('datetime', $schema['endTime']['editor']);

        // 测试布尔字段
        $this->assertArrayHasKey('showDays', $schema);
        $this->assertSame('boolean', $schema['showDays']['type']);
        $this->assertTrue($schema['showDays']['default']);

        // 测试字符串字段的长度限制
        $this->assertArrayHasKey('prefix', $schema);
        $this->assertSame('string', $schema['prefix']['type']);
        $this->assertSame(100, $schema['prefix']['maxLength']);

        // 测试颜色编辑器
        $this->assertArrayHasKey('color', $schema);
        $this->assertSame('color', $schema['color']['editor']);
        $this->assertSame('#ff0000', $schema['color']['default']);
    }

    public function testRenderWithDefaultConfig(): void
    {
        /** @phpstan-ignore method.nonObject, method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@TopicActivity/components/countdown.html.twig',
                [
                    'config' => $this->component->getDefaultConfig(),
                    'component' => $this->component,
                ]
            )
            ->willReturn('<div class="countdown-component">Countdown Content</div>')
        ;

        $result = $this->component->render();
        $this->assertSame('<div class="countdown-component">Countdown Content</div>', $result);
    }

    public function testRenderWithCustomConfig(): void
    {
        $customConfig = [
            'endTime' => '2024-12-31 23:59:59',
            'showSeconds' => false,
            'prefix' => '距离新年还有',
            'color' => '#00ff00',
        ];

        $expectedConfig = array_merge($this->component->getDefaultConfig(), $customConfig);

        /** @phpstan-ignore method.nonObject, method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@TopicActivity/components/countdown.html.twig',
                [
                    'config' => $expectedConfig,
                    'component' => $this->component,
                ]
            )
            ->willReturn('<div class="countdown-component">Custom Countdown</div>')
        ;

        $result = $this->component->render($customConfig);
        $this->assertSame('<div class="countdown-component">Custom Countdown</div>', $result);
    }

    public function testRenderHandlesException(): void
    {
        /** @phpstan-ignore method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->willThrowException(new \Exception('Template error'))
        ;

        $result = $this->component->render();
        $this->assertSame('<!-- Component render error: Template error -->', $result);
    }

    public function testValidateRequiredFields(): void
    {
        $config = [];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('endTime', $errors);
        $this->assertSame('Field "endTime" is required', $errors['endTime']);
    }

    public function testValidateWithValidConfig(): void
    {
        $config = [
            'endTime' => '2024-12-31 23:59:59',
            'showDays' => true,
            'format' => 'DD天 HH时 MM分',
        ];

        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);
    }

    public function testValidateWithInvalidTypes(): void
    {
        $config = [
            'endTime' => 123, // should be string
            'showDays' => 'yes', // should be boolean
            'format' => true, // should be string
        ];

        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('endTime', $errors);
        $this->assertArrayHasKey('showDays', $errors);
        $this->assertArrayHasKey('format', $errors);

        $this->assertStringContainsString('must be of type string', $errors['endTime']);
        $this->assertStringContainsString('must be of type boolean', $errors['showDays']);
        $this->assertStringContainsString('must be of type string', $errors['format']);
    }

    public function testValidateMaxLengthConstraints(): void
    {
        $longText = str_repeat('a', 101);

        $config = [
            'endTime' => '2024-12-31 23:59:59',
            'prefix' => $longText,
            'suffix' => $longText,
        ];

        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('prefix', $errors);
        $this->assertArrayHasKey('suffix', $errors);

        $this->assertStringContainsString('must not exceed 100 characters', $errors['prefix']);
        $this->assertStringContainsString('must not exceed 100 characters', $errors['suffix']);
    }
}
