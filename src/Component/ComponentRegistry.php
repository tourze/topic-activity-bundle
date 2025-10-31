<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Tourze\TopicActivityBundle\Exception\ComponentAlreadyRegisteredException;

class ComponentRegistry
{
    /**
     * @var array<string, ComponentInterface>
     */
    private array $components = [];

    /**
     * @var array<string, array<ComponentInterface>>
     */
    private array $componentsByCategory = [];

    /**
     * @param iterable<ComponentInterface> $components
     */
    public function __construct(
        #[AutowireIterator(tag: 'topic_activity.component')]
        iterable $components = [],
    ) {
        foreach ($components as $component) {
            $this->register($component);
        }
    }

    public function register(ComponentInterface $component): void
    {
        $type = $component->getType();
        $category = $component->getCategory();

        if (isset($this->components[$type])) {
            throw new ComponentAlreadyRegisteredException($type);
        }

        $this->components[$type] = $component;

        if (!isset($this->componentsByCategory[$category])) {
            $this->componentsByCategory[$category] = [];
        }

        $this->componentsByCategory[$category][] = $component;
    }

    public function get(string $type): ?ComponentInterface
    {
        return $this->components[$type] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->components[$type]);
    }

    /**
     * @return array<string, ComponentInterface>
     */
    public function all(): array
    {
        return $this->components;
    }

    /**
     * @return array<ComponentInterface>
     */
    public function getByCategory(string $category): array
    {
        return $this->componentsByCategory[$category] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getCategories(): array
    {
        return array_keys($this->componentsByCategory);
    }

    /**
     * @return array<ComponentInterface>
     */
    public function getVisibleComponents(): array
    {
        return array_filter($this->components, fn (ComponentInterface $component) => $component->isVisible());
    }

    /**
     * @return array<ComponentInterface>
     */
    public function getSortedComponents(): array
    {
        $components = array_values($this->components);
        usort($components, fn (ComponentInterface $a, ComponentInterface $b) => $a->getOrder() <=> $b->getOrder());

        return $components;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getComponentsConfig(): array
    {
        $config = [];

        foreach ($this->components as $type => $component) {
            $config[$type] = [
                'name' => $component->getName(),
                'type' => $component->getType(),
                'category' => $component->getCategory(),
                'icon' => $component->getIcon(),
                'description' => $component->getDescription(),
                'order' => $component->getOrder(),
                'visible' => $component->isVisible(),
                'defaultConfig' => $component->getDefaultConfig(),
                'configSchema' => $component->getConfigSchema(),
            ];
        }

        return $config;
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function getComponentsConfigByCategory(): array
    {
        $config = [];

        foreach ($this->componentsByCategory as $category => $components) {
            $config[$category] = [];
            foreach ($components as $component) {
                $type = $component->getType();
                $config[$category][$type] = [
                    'name' => $component->getName(),
                    'type' => $type,
                    'icon' => $component->getIcon(),
                    'description' => $component->getDescription(),
                    'order' => $component->getOrder(),
                ];
            }
        }

        return $config;
    }

    /**
     * @return array<string>
     */
    public function getRegisteredComponentTypes(): array
    {
        return array_keys($this->components);
    }
}
