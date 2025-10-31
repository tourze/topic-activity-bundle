<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Service\ComponentRenderer;

final class ComponentPreviewController extends AbstractController
{
    public function __construct(
        private readonly ComponentRenderer $componentRenderer,
    ) {
    }

    #[Route(path: '/admin/activity/editor/preview-component', name: 'topic_activity_editor_preview_component', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['type'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $type = $data['type'];
        $config = $data['config'] ?? [];

        // 类型安全检查
        if (!is_string($type)) {
            return new JsonResponse(['error' => 'Type must be a string'], 400);
        }

        if (!is_array($config)) {
            return new JsonResponse(['error' => 'Config must be an array'], 400);
        }

        // 验证config是 array<string, mixed> 类型
        $validConfig = [];
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $validConfig[$key] = $value;
            }
        }

        $result = $this->componentRenderer->previewComponent($type, $validConfig);

        return new JsonResponse($result);
    }
}
