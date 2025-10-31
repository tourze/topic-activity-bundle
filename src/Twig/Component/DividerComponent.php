<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'activity:divider', template: '@TopicActivity/components/divider.html.twig')]
class DividerComponent
{
    public string $style = 'solid';

    public string $color = '#e5e5e5';

    public string $thickness = '1px';

    public string $margin = '20px 0';

    public string $width = '100%';

    public string $align = 'center';

    public string $text = '';

    public bool $showIcon = false;

    public string $icon = 'fa-star';

    /**
     * @param array<string, mixed> $props
     */
    public function mount(array $props = []): void
    {
        // 类型安全赋值
        $this->style = is_string($props['style'] ?? null) ? $props['style'] : 'solid';
        $this->color = is_string($props['color'] ?? null) ? $props['color'] : '#e5e5e5';
        $this->thickness = is_string($props['thickness'] ?? null) ? $props['thickness'] : '1px';
        $this->margin = is_string($props['margin'] ?? null) ? $props['margin'] : '20px 0';
        $this->width = is_string($props['width'] ?? null) ? $props['width'] : '100%';
        $this->align = is_string($props['align'] ?? null) ? $props['align'] : 'center';
        $this->text = is_string($props['text'] ?? null) ? $props['text'] : '';
        $this->showIcon = is_bool($props['showIcon'] ?? null) ? $props['showIcon'] : false;
        $this->icon = is_string($props['icon'] ?? null) ? $props['icon'] : 'fa-star';
    }

    public function getAlignStyle(): string
    {
        $alignments = [
            'left' => 'margin-left: 0; margin-right: auto;',
            'center' => 'margin-left: auto; margin-right: auto;',
            'right' => 'margin-left: auto; margin-right: 0;',
        ];

        return $alignments[$this->align] ?? $alignments['center'];
    }
}
