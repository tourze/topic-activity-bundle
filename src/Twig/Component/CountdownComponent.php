<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(name: 'topic_activity:countdown', template: '@TopicActivity/components/countdown.html.twig')]
final class CountdownComponent
{
    #[ExposeInTemplate]
    public string $endTime = '';

    #[ExposeInTemplate]
    public string $format = 'DD天 HH时 MM分 SS秒';

    #[ExposeInTemplate]
    public bool $showDays = true;

    #[ExposeInTemplate]
    public bool $showHours = true;

    #[ExposeInTemplate]
    public bool $showMinutes = true;

    #[ExposeInTemplate]
    public bool $showSeconds = true;

    #[ExposeInTemplate]
    public string $prefix = '距离活动结束还有';

    #[ExposeInTemplate]
    public string $suffix = '';

    #[ExposeInTemplate]
    public string $expiredText = '活动已结束';

    #[ExposeInTemplate]
    public string $fontSize = '24px';

    #[ExposeInTemplate]
    public string $color = '#ff0000';

    #[ExposeInTemplate]
    public string $backgroundColor = '#fff';

    #[ExposeInTemplate]
    public string $padding = '20px';

    #[ExposeInTemplate]
    public string $borderRadius = '8px';

    #[ExposeInTemplate]
    public string $className = '';

    public function getContainerStyle(): string
    {
        $styles = [];

        if ('' !== $this->backgroundColor) {
            $styles[] = sprintf('background-color: %s', $this->backgroundColor);
        }

        if ('' !== $this->padding) {
            $styles[] = sprintf('padding: %s', $this->padding);
        }

        if ('' !== $this->borderRadius) {
            $styles[] = sprintf('border-radius: %s', $this->borderRadius);
        }

        return implode('; ', $styles);
    }

    public function getTextStyle(): string
    {
        $styles = [];

        if ('' !== $this->fontSize) {
            $styles[] = sprintf('font-size: %s', $this->fontSize);
        }

        if ('' !== $this->color) {
            $styles[] = sprintf('color: %s', $this->color);
        }

        return implode('; ', $styles);
    }

    public function getDataAttributes(): string
    {
        $attrs = [
            'data-end-time' => $this->endTime,
            'data-format' => $this->format,
            'data-show-days' => $this->showDays ? 'true' : 'false',
            'data-show-hours' => $this->showHours ? 'true' : 'false',
            'data-show-minutes' => $this->showMinutes ? 'true' : 'false',
            'data-show-seconds' => $this->showSeconds ? 'true' : 'false',
            'data-expired-text' => $this->expiredText,
        ];

        $result = [];
        foreach ($attrs as $key => $value) {
            $result[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
        }

        return implode(' ', $result);
    }
}
