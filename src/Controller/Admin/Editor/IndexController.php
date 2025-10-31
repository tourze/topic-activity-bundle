<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Component\ComponentRegistry;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

final class IndexController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ComponentRegistry $componentRegistry,
    ) {
    }

    #[Route(path: '/admin/activity/{id}/editor', name: 'topic_activity_editor', methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $activity = $this->activityRepository->find($id);
        if (null === $activity) {
            throw $this->createNotFoundException('Activity not found');
        }

        return $this->render('@TopicActivity/admin/editor.html.twig', [
            'activity' => $activity,
            'components_config' => $this->componentRegistry->getComponentsConfigByCategory(),
        ]);
    }
}
