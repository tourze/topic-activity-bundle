<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Stats;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\StatsCollector;

final class DeviceController extends AbstractController
{
    public function __construct(
        private readonly StatsCollector $statsCollector,
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    #[Route(path: '/admin/activity/stats/{id}/device', name: 'topic_activity_stats_device', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id): JsonResponse
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        $distribution = $this->statsCollector->getDeviceDistribution($activity);

        return $this->json($distribution);
    }
}
