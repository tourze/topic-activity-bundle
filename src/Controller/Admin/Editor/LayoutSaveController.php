<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

final class LayoutSaveController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    #[Route(path: '/admin/activity/{id}/editor/layout', name: 'topic_activity_editor_save_layout', methods: ['POST'])]
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

        // 验证数据结构为 array<string, mixed>
        $validData = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                return new JsonResponse(['error' => 'Layout config keys must be strings'], 400);
            }
            $validData[$key] = $value;
        }

        try {
            $activity->setLayoutConfig($validData);
            $this->activityRepository->save($activity, true);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
