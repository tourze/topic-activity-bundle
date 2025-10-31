<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Stats;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

final class IndexController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    #[Route(path: '/admin/activity/stats/{id}', name: 'topic_activity_stats', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id): Response
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            throw $this->createNotFoundException('Activity not found');
        }

        return $this->render('@TopicActivity/admin/stats/index.html.twig', [
            'activity' => $activity,
        ]);
    }
}
