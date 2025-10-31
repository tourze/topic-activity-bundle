<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Component\ComponentRegistry;

final class ComponentTypesController extends AbstractController
{
    public function __construct(
        private readonly ComponentRegistry $componentRegistry,
    ) {
    }

    #[Route(path: '/admin/activity/editor/component-types', name: 'topic_activity_editor_component_types', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->componentRegistry->getComponentsConfig());
    }
}
