<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component\Basic;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TopicActivityBundle\Component\AbstractComponent;

#[Autoconfigure(tags: ['topic_activity.component'])]
class ImageComponent extends AbstractComponent
{
    protected string $type = 'image';

    protected string $name = '图片';

    protected string $category = 'basic';

    protected string $icon = 'fa fa-image';

    protected string $description = '图片展示组件，支持多种布局方式';

    protected int $order = 20;

    public function getDefaultConfig(): array
    {
        return [
            'src' => '',
            'alt' => '',
            'title' => '',
            'width' => 'auto',
            'height' => 'auto',
            'objectFit' => 'cover',
            'link' => '',
            'linkTarget' => '_self',
            'lazyLoad' => true,
            'borderRadius' => '0',
            'className' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'src' => [
                'type' => 'string',
                'required' => true,
                'label' => '图片地址',
                'editor' => 'image',
            ],
            'alt' => [
                'type' => 'string',
                'required' => false,
                'label' => '替代文本',
                'maxLength' => 255,
            ],
            'title' => [
                'type' => 'string',
                'required' => false,
                'label' => '标题',
                'maxLength' => 255,
            ],
            'width' => [
                'type' => 'string',
                'required' => false,
                'label' => '宽度',
                'default' => 'auto',
            ],
            'height' => [
                'type' => 'string',
                'required' => false,
                'label' => '高度',
                'default' => 'auto',
            ],
            'objectFit' => [
                'type' => 'string',
                'required' => false,
                'label' => '适配方式',
                'options' => ['cover', 'contain', 'fill', 'none', 'scale-down'],
                'default' => 'cover',
            ],
            'link' => [
                'type' => 'url',
                'required' => false,
                'label' => '链接地址',
            ],
            'linkTarget' => [
                'type' => 'string',
                'required' => false,
                'label' => '链接打开方式',
                'options' => ['_self', '_blank'],
                'default' => '_self',
            ],
            'lazyLoad' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '懒加载',
                'default' => true,
            ],
            'borderRadius' => [
                'type' => 'string',
                'required' => false,
                'label' => '圆角',
                'default' => '0',
            ],
            'className' => [
                'type' => 'string',
                'required' => false,
                'label' => '自定义样式类',
            ],
        ];
    }
}
