<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component\Marketing;

use Tourze\TopicActivityBundle\Component\AbstractComponent;

class BannerComponent extends AbstractComponent
{
    protected string $type = 'banner';

    protected string $name = '轮播图';

    protected string $category = 'marketing';

    protected string $icon = 'fa fa-images';

    protected string $description = '图片轮播展示';

    protected int $order = 130;

    public function getDefaultConfig(): array
    {
        return [
            'images' => [],
            'autoplay' => true,
            'interval' => 3000,
            'showIndicators' => true,
            'showArrows' => true,
            'height' => '400px',
            'objectFit' => 'cover',
            'borderRadius' => '0',
            'className' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'images' => [
                'type' => 'array',
                'required' => true,
                'label' => '图片列表',
                'editor' => 'images',
                'itemSchema' => [
                    'src' => ['type' => 'string', 'required' => true],
                    'alt' => ['type' => 'string', 'required' => false],
                    'link' => ['type' => 'url', 'required' => false],
                ],
            ],
            'autoplay' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '自动播放',
                'default' => true,
            ],
            'interval' => [
                'type' => 'integer',
                'required' => false,
                'label' => '轮播间隔(毫秒)',
                'default' => 3000,
            ],
            'showIndicators' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '显示指示器',
                'default' => true,
            ],
            'showArrows' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '显示切换箭头',
                'default' => true,
            ],
            'height' => [
                'type' => 'string',
                'required' => false,
                'label' => '高度',
                'default' => '400px',
            ],
            'objectFit' => [
                'type' => 'string',
                'required' => false,
                'label' => '图片适配',
                'options' => ['cover', 'contain', 'fill', 'none'],
                'default' => 'cover',
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
