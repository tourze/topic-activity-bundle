<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(name: 'topic_activity:button', template: '@TopicActivity/components/button.html.twig')]
final class ButtonComponent
{
    #[ExposeInTemplate]
    public string $text = '点击按钮';

    #[ExposeInTemplate]
    public string $link = '';

    #[ExposeInTemplate]
    public string $linkTarget = '_self';

    #[ExposeInTemplate]
    public string $style = 'primary';

    #[ExposeInTemplate]
    public string $size = 'medium';

    #[ExposeInTemplate]
    public string $width = 'auto';

    #[ExposeInTemplate]
    public string $icon = '';

    #[ExposeInTemplate]
    public string $iconPosition = 'left';

    #[ExposeInTemplate]
    public bool $disabled = false;

    #[ExposeInTemplate]
    public string $borderRadius = '4px';

    #[ExposeInTemplate]
    public string $className = '';

    public function getButtonClass(): string
    {
        $classes = ['btn', 'activity-btn'];

        // Style mapping
        $styleMap = [
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            'success' => 'btn-success',
            'danger' => 'btn-danger',
            'warning' => 'btn-warning',
            'info' => 'btn-info',
            'outline' => 'btn-outline-primary',
        ];

        if (isset($styleMap[$this->style])) {
            $classes[] = $styleMap[$this->style];
        }

        // Size mapping
        $sizeMap = [
            'small' => 'btn-sm',
            'large' => 'btn-lg',
        ];

        if (isset($sizeMap[$this->size])) {
            $classes[] = $sizeMap[$this->size];
        }

        if ($this->disabled) {
            $classes[] = 'disabled';
        }

        if ('' !== $this->className) {
            $classes[] = $this->className;
        }

        return implode(' ', $classes);
    }

    public function getButtonStyle(): string
    {
        $styles = [];

        if ('' !== $this->width && 'auto' !== $this->width) {
            $styles[] = sprintf('width: %s', $this->width);
        }

        if ('' !== $this->borderRadius) {
            $styles[] = sprintf('border-radius: %s', $this->borderRadius);
        }

        return implode('; ', $styles);
    }

    public function hasIcon(): bool
    {
        return '' !== $this->icon;
    }

    public function hasLink(): bool
    {
        return '' !== $this->link;
    }
}
