<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component\Basic;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TopicActivityBundle\Component\AbstractComponent;

#[Autoconfigure(tags: ['topic_activity.component'])]
class TextComponent extends AbstractComponent
{
    protected string $type = 'text';

    protected string $name = '文本';

    protected string $category = 'basic';

    protected string $icon = 'fa fa-font';

    protected string $description = '富文本编辑器，支持格式化文本内容';

    protected int $order = 10;

    public function getDefaultConfig(): array
    {
        return [
            'content' => '',
            'alignment' => 'left',
            'fontSize' => '14px',
            'color' => '#333333',
            'backgroundColor' => 'transparent',
            'padding' => '10px',
            'className' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'content' => [
                'type' => 'string',
                'required' => false,
                'label' => '文本内容',
                'editor' => 'richtext',
            ],
            'alignment' => [
                'type' => 'string',
                'required' => false,
                'label' => '对齐方式',
                'options' => ['left', 'center', 'right', 'justify'],
                'default' => 'left',
            ],
            'fontSize' => [
                'type' => 'string',
                'required' => false,
                'label' => '字体大小',
                'default' => '14px',
            ],
            'color' => [
                'type' => 'string',
                'required' => false,
                'label' => '文字颜色',
                'editor' => 'color',
                'default' => '#333333',
            ],
            'backgroundColor' => [
                'type' => 'string',
                'required' => false,
                'label' => '背景颜色',
                'editor' => 'color',
                'default' => 'transparent',
            ],
            'padding' => [
                'type' => 'string',
                'required' => false,
                'label' => '内边距',
                'default' => '10px',
            ],
            'className' => [
                'type' => 'string',
                'required' => false,
                'label' => '自定义样式类',
            ],
        ];
    }
}
