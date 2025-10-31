<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(name: 'topic_activity:image', template: '@TopicActivity/components/image.html.twig')]
final class ImageComponent
{
    #[ExposeInTemplate]
    public string $src = '';

    #[ExposeInTemplate]
    public string $alt = '';

    #[ExposeInTemplate]
    public string $title = '';

    #[ExposeInTemplate]
    public string $width = 'auto';

    #[ExposeInTemplate]
    public string $height = 'auto';

    #[ExposeInTemplate]
    public string $objectFit = 'cover';

    #[ExposeInTemplate]
    public string $link = '';

    #[ExposeInTemplate]
    public string $linkTarget = '_self';

    #[ExposeInTemplate]
    public bool $lazyLoad = true;

    #[ExposeInTemplate]
    public string $borderRadius = '0';

    #[ExposeInTemplate]
    public string $className = '';

    public function getImageStyle(): string
    {
        $styles = [];

        if ('' !== $this->width && 'auto' !== $this->width) {
            $styles[] = sprintf('width: %s', $this->width);
        }

        if ('' !== $this->height && 'auto' !== $this->height) {
            $styles[] = sprintf('height: %s', $this->height);
        }

        if ('' !== $this->objectFit) {
            $styles[] = sprintf('object-fit: %s', $this->objectFit);
        }

        if ('' !== $this->borderRadius && '0' !== $this->borderRadius) {
            $styles[] = sprintf('border-radius: %s', $this->borderRadius);
        }

        return implode('; ', $styles);
    }

    public function hasLink(): bool
    {
        return '' !== $this->link;
    }
}
