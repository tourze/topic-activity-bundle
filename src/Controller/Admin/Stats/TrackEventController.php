<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Stats;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\StatsCollector;

final class TrackEventController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly StatsCollector $statsCollector,
    ) {
    }

    #[Route(path: '/admin/activity/stats/{id}/track', name: 'topic_activity_track_event', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        $eventType = $request->request->get('event_type');
        if (!is_string($eventType)) {
            return $this->json(['error' => 'Event type must be a string'], 400);
        }

        $eventData = $this->normalizeEventData($request->request->all('event_data'));

        try {
            $this->handleEvent($eventType, $activity, $eventData, $request);

            return $this->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @param array<array-key, mixed> $eventData
     * @return array<string, mixed>
     */
    private function normalizeEventData(array $eventData): array
    {
        $validEventData = [];
        foreach ($eventData as $key => $value) {
            if (is_string($key)) {
                $validEventData[$key] = $value;
            }
        }

        return $validEventData;
    }

    /**
     * @param array<string, mixed> $eventData
     * @throws \Exception
     */
    private function handleEvent(string $eventType, Activity $activity, array $eventData, Request $request): void
    {
        switch ($eventType) {
            case 'page_view':
                $this->statsCollector->recordPageView($activity, $request);
                break;
            case 'form_submit':
                $this->statsCollector->recordFormSubmit($activity, $eventData);
                break;
            case 'share':
                $this->handleShareEvent($activity, $eventData);
                break;
            case 'conversion':
                $this->statsCollector->recordConversion($activity, $eventData);
                break;
            case 'stay_duration':
                $this->handleStayDurationEvent($activity, $eventData);
                break;
            default:
                throw new \InvalidArgumentException('Unknown event type');
        }
    }

    /**
     * @param array<string, mixed> $eventData
     */
    private function handleShareEvent(Activity $activity, array $eventData): void
    {
        $platform = $eventData['platform'] ?? 'unknown';
        $platformStr = is_string($platform) ? $platform : 'unknown';
        $this->statsCollector->recordShare($activity, $platformStr);
    }

    /**
     * @param array<string, mixed> $eventData
     */
    private function handleStayDurationEvent(Activity $activity, array $eventData): void
    {
        $duration = $eventData['duration'] ?? 0;
        $durationFloat = is_numeric($duration) ? (float) $duration : 0.0;
        $this->statsCollector->recordStayDuration($activity, $durationFloat);
    }
}
