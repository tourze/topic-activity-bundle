<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Stats;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\StatsCollector;

final class SummaryController extends AbstractController
{
    public function __construct(
        private readonly StatsCollector $statsCollector,
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    #[Route(path: '/admin/activity/stats/{id}/summary', name: 'topic_activity_stats_summary', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        $startDate = is_string($startDate) && '' !== $startDate ? new \DateTimeImmutable($startDate) : null;
        $endDate = is_string($endDate) && '' !== $endDate ? new \DateTimeImmutable($endDate) : null;

        $summary = $this->statsCollector->getActivitySummary($activity, $startDate, $endDate);

        return $this->json($summary);
    }
}
