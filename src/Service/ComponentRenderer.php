<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TopicActivityBundle\Component\ComponentRegistry;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;

#[WithMonologChannel(channel: 'topic_activity')]
class ComponentRenderer
{
    public function __construct(
        private readonly ComponentRegistry $componentRegistry,
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
            $html .= $this->render($component);
        }

        return $html;
    }

    public function render(ActivityComponent $activityComponent): string
    {
        $type = $activityComponent->getComponentType();
        $config = $activityComponent->getComponentConfig();

        $component = $this->componentRegistry->get($type);
        if (null === $component) {
            $this->logger->warning('Component type not found', [
                'type' => $type,
                'activity_component_id' => $activityComponent->getId(),
            ]);

            return $this->renderErrorComponent($type, 'Component type not found');
        }

        if (!$activityComponent->isVisible()) {
            return '';
        }

        try {
            $errors = $component->validate($config);
            if ([] !== $errors) {
                $this->logger->warning('Component config validation failed', [
                    'type' => $type,
                    'errors' => $errors,
                    'activity_component_id' => $activityComponent->getId(),
                ]);

                return $this->renderErrorComponent($type, 'Invalid configuration', $errors);
            }

            return $component->render($config);
        } catch (\Exception $e) {
            $this->logger->error('Component render failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'activity_component_id' => $activityComponent->getId(),
            ]);

            return $this->renderErrorComponent($type, $e->getMessage());
        }
    }

    /**
     * 渲染方法的别名，用于匹配模板使用方式
     */
    public function renderComponent(ActivityComponent $activityComponent): string
    {
        return $this->render($activityComponent);
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderErrorComponent(string $type, string $message, array $errors = []): string
    {
        $errorDetails = '';
        if ([] !== $errors) {
            $errorList = [];
            foreach ($errors as $field => $error) {
                $errorList[] = sprintf('%s: %s', $field, $error);
            }
            $errorDetails = implode(', ', $errorList);
        }

        return sprintf(
            '<!-- Component Error [%s]: %s %s -->',
            $type,
            $message,
            '' !== $errorDetails ? '(' . $errorDetails . ')' : ''
        );
    }

    /**
     * @param list<ActivityComponent> $components
     * @return array<int|string, string>
     */
    public function renderToArray(array $components): array
    {
        $rendered = [];
        foreach ($components as $component) {
            $id = $component->getId();
            if (null !== $id) {
                $rendered[(string) $id] = $this->render($component);
            } else {
                // 为没有ID的组件使用对象哈希作为字符串键
                $objectHash = spl_object_hash($component);
                $rendered[$objectHash] = $this->render($component);
            }
        }

        return $rendered;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function renderByType(string $type, array $config = []): string
    {
        $component = $this->componentRegistry->get($type);
        if (null === $component) {
            return $this->renderErrorComponent($type, 'Component type not found');
        }

        try {
            $errors = $component->validate($config);
            if ([] !== $errors) {
                return $this->renderErrorComponent($type, 'Invalid configuration', $errors);
            }

            return $component->render($config);
        } catch (\Exception $e) {
            $this->logger->error('Component render failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return $this->renderErrorComponent($type, $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function previewComponent(string $type, array $config = []): array
    {
        $component = $this->componentRegistry->get($type);
        if (null === $component) {
            return [
                'success' => false,
                'error' => 'Component type not found',
                'html' => $this->renderErrorComponent($type, 'Component type not found'),
            ];
        }

        $errors = $component->validate($config);
        if ([] !== $errors) {
            return [
                'success' => false,
                'errors' => $errors,
                'html' => $this->renderErrorComponent($type, 'Invalid configuration', $errors),
            ];
        }

        try {
            $html = $component->render($config);

            return [
                'success' => true,
                'html' => $html,
                'type' => $type,
                'config' => $config,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'html' => $this->renderErrorComponent($type, $e->getMessage()),
            ];
        }
    }
}
