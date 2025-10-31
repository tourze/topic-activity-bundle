<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component\Basic;

use Tourze\TopicActivityBundle\Component\AbstractComponent;

class SpacerComponent extends AbstractComponent
{
    protected string $type = 'spacer';

    protected string $name = '间距';

    protected string $category = 'basic';

    protected string $icon = 'fa fa-arrows-v';

    protected string $description = '用于添加元素间的空白间距';

    protected int $order = 50;

    public function getDefaultConfig(): array
    {
        return [
            'height' => '20px',
            'backgroundColor' => 'transparent',
            'className' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'height' => [
                'type' => 'string',
                'required' => false,
                'label' => '高度',
                'default' => '20px',
            ],
            'backgroundColor' => [
                'type' => 'string',
                'required' => false,
                'label' => '背景颜色',
                'editor' => 'color',
                'default' => 'transparent',
            ],
            'className' => [
                'type' => 'string',
                'required' => false,
                'label' => '自定义样式类',
            ],
        ];
    }
}
