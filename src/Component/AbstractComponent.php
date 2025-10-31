<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

abstract class AbstractComponent implements ComponentInterface
{
    protected string $name = '';

    protected string $type = '';

    protected string $category = 'basic';

    protected string $icon = 'fa fa-cube';

    protected string $description = '';

    protected int $order = 100;

    protected bool $visible = true;

    public function __construct(
        protected Environment $twig,
        protected ValidatorInterface $validator,
    ) {
    }

    public function getName(): string
    {
        return '' !== $this->name ? $this->name : $this->type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function supports(string $version): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function render(array $config = []): string
    {
        $config = array_merge($this->getDefaultConfig(), $config);
        $template = sprintf('@TopicActivity/components/%s.html.twig', $this->type);

        try {
            return $this->twig->render($template, [
                'config' => $config,
                'component' => $this,
            ]);
        } catch (\Exception $e) {
            return sprintf('<!-- Component render error: %s -->', $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    public function validate(array $config): array
    {
        /** @var array<string, string> $errors */
        $errors = [];
        $schema = $this->getConfigSchema();

        foreach ($schema as $field => $rules) {
            $errors = $this->validateRequiredField($field, $rules, $config, $errors);
            $errors = $this->validateFieldType($field, $rules, $config, $errors);
            $errors = $this->validateFieldLength($field, $rules, $config, $errors);
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $config
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    private function validateRequiredField(string $field, array $rules, array $config, array $errors): array
    {
        if (isset($rules['required']) && true === $rules['required'] && $this->isFieldEmpty($config, $field)) {
            $errors[$field] = sprintf('Field "%s" is required', $field);
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $config
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    private function validateFieldType(string $field, array $rules, array $config, array $errors): array
    {
        if (!isset($config[$field], $rules['type'])) {
            return $errors;
        }

        $value = $config[$field];
        $type = $rules['type'];

        if (\is_string($type) && !$this->validateType($value, $type)) {
            $errors[$field] = sprintf('Field "%s" must be of type %s', $field, $type);
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $config
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    private function validateFieldLength(string $field, array $rules, array $config, array $errors): array
    {
        if (!isset($config[$field], $rules['maxLength'])) {
            return $errors;
        }

        $value = $config[$field];
        $maxLength = $rules['maxLength'];
        if (\is_string($value) && \is_int($maxLength) && \strlen($value) > $maxLength) {
            $errors[$field] = sprintf('Field "%s" must not exceed %d characters', $field, $maxLength);
        }

        return $errors;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getConfigSchema(): array
    {
        return [];
    }

    protected function validateType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => \is_string($value),
            'int', 'integer' => \is_int($value),
            'float', 'double' => \is_float($value),
            'bool', 'boolean' => \is_bool($value),
            'array' => \is_array($value),
            'url' => \is_string($value) && false !== filter_var($value, FILTER_VALIDATE_URL),
            'email' => \is_string($value) && false !== filter_var($value, FILTER_VALIDATE_EMAIL),
            default => true,
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    private function isFieldEmpty(array $config, string $field): bool
    {
        if (!isset($config[$field])) {
            return true;
        }

        $value = $config[$field];

        // Check for empty string
        if (\is_string($value) && '' === $value) {
            return true;
        }

        // Check for empty array
        if (\is_array($value) && [] === $value) {
            return true;
        }

        // If we get here, the value is not empty according to our criteria
        return false;
    }
}
