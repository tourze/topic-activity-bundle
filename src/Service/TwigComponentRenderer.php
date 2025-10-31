<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Twig\Environment;

#[WithMonologChannel(channel: 'topic_activity')]
#[Autoconfigure(public: true)]
class TwigComponentRenderer
{
    /**
     * @var array<string, string>
     */
    private array $componentMapping = [
        'text' => 'topic_activity:text',
        'image' => 'topic_activity:image',
        'button' => 'topic_activity:button',
        'video' => 'topic_activity:video',
        'spacer' => 'topic_activity:spacer',
        'countdown' => 'topic_activity:countdown',
        'banner' => 'topic_activity:banner',
        'richtext' => 'activity:richtext',
        'divider' => 'activity:divider',
        'custom_html' => 'activity:custom_html',
        'file_upload' => 'activity:file_upload',
    ];

    public function __construct(
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<ActivityComponent> $components
     */
    public function renderMultiple(array $components): string
    {
        $html = '';
        foreach ($components as $component) {
            $html .= $this->renderComponent($component);
        }

        return $html;
    }

    public function renderComponent(ActivityComponent $activityComponent): string
    {
        $type = $activityComponent->getComponentType();
        $config = $activityComponent->getComponentConfig();

        if (!$activityComponent->isVisible()) {
            return '';
        }

        $componentName = $this->componentMapping[$type] ?? null;
        if (null === $componentName) {
            $this->logger->warning('Unknown component type', ['type' => $type]);

            return $this->renderErrorComponent($type, 'Unknown component type');
        }

        try {
            return $this->twig->render('@TopicActivity/component_wrapper.html.twig', [
                'component_name' => $componentName,
                'config' => $config,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to render component', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return $this->renderErrorComponent($type, $e->getMessage());
        }
    }

    private function renderErrorComponent(string $type, string $message): string
    {
        return sprintf(
            '<div class="alert alert-danger">Component Error [%s]: %s</div>',
            htmlspecialchars($type),
            htmlspecialchars($message)
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public function renderByType(string $type, array $config = []): string
    {
        $componentName = $this->componentMapping[$type] ?? null;
        if (null === $componentName) {
            return $this->renderErrorComponent($type, 'Unknown component type');
        }

        try {
            return $this->twig->render('@TopicActivity/component_wrapper.html.twig', [
                'component_name' => $componentName,
                'config' => $config,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to render component', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return $this->renderErrorComponent($type, $e->getMessage());
        }
    }
}
