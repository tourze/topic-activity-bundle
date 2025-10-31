<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component\Basic;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TopicActivityBundle\Component\AbstractComponent;

#[Autoconfigure(tags: ['topic_activity.component'])]
class ButtonComponent extends AbstractComponent
{
    protected string $type = 'button';

    protected string $name = '按钮';

    protected string $category = 'basic';

    protected string $icon = 'fa fa-square';

    protected string $description = '可点击按钮，支持多种样式和交互';

    protected int $order = 30;

    public function getDefaultConfig(): array
    {
        return [
            'text' => '点击按钮',
            'link' => '',
            'linkTarget' => '_self',
            'style' => 'primary',
            'size' => 'medium',
            'width' => 'auto',
            'icon' => '',
            'iconPosition' => 'left',
            'disabled' => false,
            'borderRadius' => '4px',
            'className' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'text' => [
                'type' => 'string',
                'required' => true,
                'label' => '按钮文字',
                'maxLength' => 100,
            ],
            'link' => [
                'type' => 'url',
                'required' => false,
                'label' => '链接地址',
            ],
            'linkTarget' => [
                'type' => 'string',
                'required' => false,
                'label' => '打开方式',
                'options' => ['_self', '_blank'],
                'default' => '_self',
            ],
            'style' => [
                'type' => 'string',
                'required' => false,
                'label' => '按钮风格',
                'options' => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'outline'],
                'default' => 'primary',
            ],
            'size' => [
                'type' => 'string',
                'required' => false,
                'label' => '按钮大小',
                'options' => ['small', 'medium', 'large'],
                'default' => 'medium',
            ],
            'width' => [
                'type' => 'string',
                'required' => false,
                'label' => '按钮宽度',
                'default' => 'auto',
            ],
            'icon' => [
                'type' => 'string',
                'required' => false,
                'label' => '图标',
            ],
            'iconPosition' => [
                'type' => 'string',
                'required' => false,
                'label' => '图标位置',
                'options' => ['left', 'right'],
                'default' => 'left',
            ],
            'disabled' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '禁用状态',
                'default' => false,
            ],
            'borderRadius' => [
                'type' => 'string',
                'required' => false,
                'label' => '圆角',
                'default' => '4px',
            ],
            'className' => [
                'type' => 'string',
                'required' => false,
                'label' => '自定义样式类',
            ],
        ];
    }
}
