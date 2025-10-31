<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Stats;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\StatsCollector;

final class TrendController extends AbstractController
{
    public function __construct(
        private readonly StatsCollector $statsCollector,
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    #[Route(path: '/admin/activity/stats/{id}/trend', name: 'topic_activity_stats_trend', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        // 安全地获取days参数，处理无效输入
        $daysParam = $request->query->get('days', '7');
        $days = is_numeric($daysParam) ? (int) $daysParam : 7;
        if ($days < 1 || $days > 365) {
            $days = 7;
        }

        $trend = $this->statsCollector->getTrendData($activity, $days);

        return $this->json($trend);
    }
}
