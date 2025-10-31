<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\ActivityManager;

final class PublishController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityManager $activityManager,
    ) {
    }

    #[Route(path: '/admin/activity/{id}/editor/publish', name: 'topic_activity_editor_publish', methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            return new JsonResponse(['error' => 'Activity not found'], 404);
        }

        try {
            $this->activityManager->publishActivity($activity);

            return new JsonResponse([
                'success' => true,
                'status' => $activity->getStatus()->value,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
