<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

final class ComponentsGetController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    #[Route(path: '/admin/activity/{id}/editor/components', name: 'topic_activity_editor_components', methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            return new JsonResponse(['error' => 'Activity not found'], 404);
        }

        $components = $activity->getComponents()->toArray();
        $data = [];

        foreach ($components as $component) {
            $data[] = [
                'id' => $component->getId(),
                'type' => $component->getComponentType(),
                'config' => $component->getComponentConfig(),
                'position' => $component->getPosition(),
                'visible' => $component->isVisible(),
            ];
        }

        return new JsonResponse($data);
    }
}
