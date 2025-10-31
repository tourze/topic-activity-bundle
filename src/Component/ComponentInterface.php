<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component;

interface ComponentInterface
{
    public function getName(): string;

    public function getType(): string;

    public function getCategory(): string;

    public function getIcon(): string;

    public function getDescription(): string;

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getConfigSchema(): array;

    public function isVisible(): bool;

    public function getOrder(): int;

    public function supports(string $version): bool;

    /**
     * @param array<string, mixed> $config
     */
    public function render(array $config = []): string;

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    public function validate(array $config): array;
}
