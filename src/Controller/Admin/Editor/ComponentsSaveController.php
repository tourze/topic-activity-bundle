<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\ActivityManager;

final class ComponentsSaveController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityManager $activityManager,
    ) {
    }

    #[Route(path: '/admin/activity/{id}/editor/components', name: 'topic_activity_editor_save_components', methods: ['POST'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            return new JsonResponse(['error' => 'Activity not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // 验证组件数据结构为 array<int, array<string, mixed>>
        $validData = [];
        foreach ($data as $index => $componentData) {
            if (!is_int($index) || !is_array($componentData)) {
                return new JsonResponse(['error' => 'Invalid component data structure'], 400);
            }
            // 验证componentData的键是字符串
            $validComponentData = [];
            foreach ($componentData as $key => $value) {
                if (is_string($key)) {
                    $validComponentData[$key] = $value;
                }
            }
            $validData[$index] = $validComponentData;
        }

        try {
            $this->activityManager->updateActivityComponents($activity, $validData);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
