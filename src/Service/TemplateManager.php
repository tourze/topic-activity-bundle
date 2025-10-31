<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;
use Tourze\TopicActivityBundle\Repository\ActivityTemplateRepository;

#[WithMonologChannel(channel: 'topic_activity')]
class TemplateManager
{
    public function __construct(
        private readonly ActivityTemplateRepository $templateRepository,
        private readonly ActivityManager $activityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 创建预设模板
     */
    public function createSystemTemplates(): void
    {
        $templates = $this->getSystemTemplateDefinitions();

        foreach ($templates as $definition) {
            $existing = $this->templateRepository->findByCode($definition['code']);
            if (null !== $existing) {
                continue;
            }

            $template = new ActivityTemplate();
            $template->setName($definition['name']);
            $template->setCode($definition['code']);
            $template->setDescription($definition['description']);
            $template->setCategory($definition['category']);
            $template->setLayoutConfig($definition['layoutConfig']);
            $template->setDefaultData($definition['defaultData']);
            $template->setTags($this->extractValidTags($definition['tags']));
            $template->setIsSystem(true);
            $template->setIsActive(true);

            $this->templateRepository->save($template, true);

            $this->logger->info('Created system template', [
                'code' => $definition['code'],
                'name' => $definition['name'],
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractValidTags(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        $validTags = [];
        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $validTags[] = $tag;
            }
        }

        return $validTags;
    }

    /**
     * @return array<array{code: string, name: string, description: string, category: string, layoutConfig: array<string, mixed>, defaultData: array<string, mixed>, tags: array<string>}>
     */
    private function getSystemTemplateDefinitions(): array
    {
        return [
            [
                'code' => 'general_promotion',
                'name' => '通用促销',
                'description' => '适用于促销活动，包含倒计时、活动规则等组件',
                'category' => 'promotion',
                'layoutConfig' => [
                    'version' => '1.0',
                    'settings' => [
                        'backgroundColor' => '#f5f5f5',
                        'maxWidth' => '1200px',
                    ],
                    'components' => [
                        [
                            'type' => 'banner',
                            'props' => [
                                'images' => [],
                                'height' => '400px',
                                'autoplay' => true,
                            ],
                        ],
                        [
                            'type' => 'countdown',
                            'props' => [
                                'endTime' => '',
                                'prefix' => '距离活动结束还有',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'props' => [
                                'content' => '活动规则说明',
                                'alignment' => 'left',
                            ],
                        ],
                    ],
                ],
                'defaultData' => [
                    'title' => '限时促销活动',
                    'description' => '精选商品限时优惠',
                ],
                'tags' => ['促销', '优惠', '限时'],
            ],
            [
                'code' => 'new_product_launch',
                'name' => '新品发布',
                'description' => '新产品上市宣传，包含视频介绍、产品特性展示',
                'category' => 'new_product',
                'layoutConfig' => [
                    'version' => '1.0',
                    'settings' => [
                        'backgroundColor' => '#ffffff',
                        'maxWidth' => '1200px',
                    ],
                    'components' => [
                        [
                            'type' => 'video',
                            'props' => [
                                'src' => '',
                                'poster' => '',
                                'controls' => true,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'props' => [
                                'content' => '<h2>产品特性</h2>',
                                'alignment' => 'center',
                            ],
                        ],
                        [
                            'type' => 'image',
                            'props' => [
                                'src' => '',
                                'alt' => '产品图片',
                            ],
                        ],
                        [
                            'type' => 'button',
                            'props' => [
                                'text' => '立即预约',
                                'link' => '#',
                                'style' => 'primary',
                            ],
                        ],
                    ],
                ],
                'defaultData' => [
                    'title' => '新品发布会',
                    'description' => '全新产品震撼上市',
                ],
                'tags' => ['新品', '发布', '预约'],
            ],
            [
                'code' => 'holiday_special',
                'name' => '节日专题',
                'description' => '节日营销活动，包含节日主题元素',
                'category' => 'holiday',
                'layoutConfig' => [
                    'version' => '1.0',
                    'settings' => [
                        'backgroundColor' => '#fff0f0',
                        'maxWidth' => '1200px',
                    ],
                    'components' => [
                        [
                            'type' => 'banner',
                            'props' => [
                                'images' => [],
                                'height' => '500px',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'props' => [
                                'content' => '<h1>节日祝福语</h1>',
                                'alignment' => 'center',
                                'fontSize' => '24px',
                            ],
                        ],
                        [
                            'type' => 'image',
                            'props' => [
                                'src' => '',
                                'alt' => '节日活动图片',
                            ],
                        ],
                    ],
                ],
                'defaultData' => [
                    'title' => '节日特惠',
                    'description' => '节日快乐，好礼相送',
                ],
                'tags' => ['节日', '活动', '促销'],
            ],
            [
                'code' => 'brand_story',
                'name' => '品牌故事',
                'description' => '品牌宣传页面，展示品牌历史和文化',
                'category' => 'brand',
                'layoutConfig' => [
                    'version' => '1.0',
                    'settings' => [
                        'backgroundColor' => '#fafafa',
                        'maxWidth' => '1200px',
                    ],
                    'components' => [
                        [
                            'type' => 'image',
                            'props' => [
                                'src' => '',
                                'alt' => '品牌Logo',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'props' => [
                                'content' => '<h1>我们的故事</h1>',
                                'alignment' => 'center',
                            ],
                        ],
                        [
                            'type' => 'video',
                            'props' => [
                                'src' => '',
                                'poster' => '',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'props' => [
                                'content' => '<p>品牌介绍内容</p>',
                                'alignment' => 'left',
                            ],
                        ],
                    ],
                ],
                'defaultData' => [
                    'title' => '品牌故事',
                    'description' => '了解我们的品牌历程',
                ],
                'tags' => ['品牌', '文化', '历史'],
            ],
            [
                'code' => 'event_registration',
                'name' => '活动报名',
                'description' => '线下活动报名页面，包含活动介绍和报名表单',
                'category' => 'event',
                'layoutConfig' => [
                    'version' => '1.0',
                    'settings' => [
                        'backgroundColor' => '#ffffff',
                        'maxWidth' => '1200px',
                    ],
                    'components' => [
                        [
                            'type' => 'banner',
                            'props' => [
                                'images' => [],
                                'height' => '300px',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'props' => [
                                'content' => '<h2>活动介绍</h2>',
                            ],
                        ],
                        [
                            'type' => 'countdown',
                            'props' => [
                                'endTime' => '',
                                'prefix' => '报名截止时间',
                            ],
                        ],
                        [
                            'type' => 'file_upload',
                            'props' => [
                                'label' => '上传报名资料',
                                'multiple' => false,
                                'accept' => '.pdf,.doc,.docx',
                                'maxSize' => 10485760,
                            ],
                        ],
                    ],
                ],
                'defaultData' => [
                    'title' => '活动报名',
                    'description' => '精彩活动等你参加',
                ],
                'tags' => ['活动', '报名', '线下'],
            ],
        ];
    }

    /**
     * 从模板创建活动
     *
     * @param array<string, mixed> $customData
     */
    public function createActivityFromTemplate(ActivityTemplate $template, array $customData = []): Activity
    {
        $template->incrementUsageCount();
        $this->templateRepository->save($template, true);

        $activityData = array_merge([
            'title' => $customData['title'] ?? $template->getName() . ' - ' . date('Y-m-d'),
            'description' => $customData['description'] ?? $template->getDescription(),
            'layoutConfig' => $template->getLayoutConfig(),
        ], $customData);

        $activity = $this->activityManager->createActivity($activityData);

        // 创建组件
        $this->createComponentsFromTemplate($activity, $template);

        $this->logger->info('Created activity from template', [
            'template_id' => $template->getId(),
            'activity_id' => $activity->getId(),
        ]);

        return $activity;
    }

    private function createComponentsFromTemplate(Activity $activity, ActivityTemplate $template): void
    {
        $layoutConfig = $template->getLayoutConfig();
        $components = $layoutConfig['components'] ?? [];

        if (!is_array($components)) {
            return;
        }

        foreach ($components as $index => $componentData) {
            if (!is_array($componentData)) {
                continue;
            }

            /** @var array<string, mixed> $validComponentData */
            $validComponentData = [];
            foreach ($componentData as $key => $value) {
                if (is_string($key)) {
                    $validComponentData[$key] = $value;
                }
            }

            $component = $this->buildComponentFromTemplateData($activity, $validComponentData, $index);
            $activity->addComponent($component);
        }
    }

    /**
     * @param array<string, mixed> $componentData
     */
    private function buildComponentFromTemplateData(Activity $activity, array $componentData, int|string $index): ActivityComponent
    {
        $component = new ActivityComponent();
        $component->setActivity($activity);

        $type = $componentData['type'] ?? '';
        $component->setComponentType(is_string($type) ? $type : '');

        $validProps = $this->extractValidProps($componentData['props'] ?? []);
        $component->setComponentConfig($validProps);

        $component->setPosition(is_int($index) ? $index : 0);
        $component->setIsVisible(true);

        return $component;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractValidProps(mixed $props): array
    {
        if (!is_array($props)) {
            return [];
        }

        $validProps = [];
        foreach ($props as $key => $value) {
            if (is_string($key)) {
                $validProps[$key] = $value;
            }
        }

        return $validProps;
    }

    /**
     * 从活动创建模板
     */
    public function createTemplateFromActivity(Activity $activity, string $name, string $code): ActivityTemplate
    {
        $template = new ActivityTemplate();
        $template->setName($name);
        $template->setCode($code);
        $template->setDescription($activity->getDescription());
        $template->setCategory('custom');
        $template->setLayoutConfig($activity->getLayoutConfig());
        $template->setIsSystem(false);
        $template->setIsActive(true);

        // 提取默认数据
        $defaultData = [
            'title' => $activity->getTitle(),
            'description' => $activity->getDescription(),
            'coverImage' => $activity->getCoverImage(),
        ];
        $template->setDefaultData($defaultData);

        $this->templateRepository->save($template, true);

        $this->logger->info('Created template from activity', [
            'activity_id' => $activity->getId(),
            'template_code' => $code,
        ]);

        return $template;
    }

    /**
     * 获取可用模板列表
     *
     * @return ActivityTemplate[]
     */
    public function getAvailableTemplates(?string $category = null): array
    {
        if (null !== $category) {
            return $this->templateRepository->findByCategory($category);
        }

        return $this->templateRepository->findActiveTemplates();
    }

    /**
     * 获取模板分类
     *
     * @return array<string, string>
     */
    public function getTemplateCategories(): array
    {
        return [
            'promotion' => '通用促销',
            'new_product' => '新品发布',
            'holiday' => '节日专题',
            'brand' => '品牌故事',
            'event' => '活动报名',
            'custom' => '自定义',
        ];
    }
}
