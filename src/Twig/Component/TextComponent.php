<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(name: 'topic_activity:text', template: '@TopicActivity/components/text.html.twig')]
final class TextComponent
{
    #[ExposeInTemplate]
    public string $content = '';

    #[ExposeInTemplate]
    public string $alignment = 'left';

    #[ExposeInTemplate]
    public string $fontSize = '14px';

    #[ExposeInTemplate]
    public string $color = '#333333';

    #[ExposeInTemplate]
    public string $backgroundColor = 'transparent';

    #[ExposeInTemplate]
    public string $padding = '10px';

    #[ExposeInTemplate]
    public string $className = '';

    public function getStyle(): string
    {
        $styles = [];

        if ('' !== $this->fontSize) {
            $styles[] = sprintf('font-size: %s', $this->fontSize);
        }

        if ('' !== $this->color) {
            $styles[] = sprintf('color: %s', $this->color);
        }

        if ('' !== $this->backgroundColor && 'transparent' !== $this->backgroundColor) {
            $styles[] = sprintf('background-color: %s', $this->backgroundColor);
        }

        if ('' !== $this->padding) {
            $styles[] = sprintf('padding: %s', $this->padding);
        }

        if ('' !== $this->alignment) {
            $styles[] = sprintf('text-align: %s', $this->alignment);
        }

        return implode('; ', $styles);
    }
}
