<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\ComponentRenderer;

final class PreviewController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ComponentRenderer $componentRenderer,
    ) {
    }

    #[Route(path: '/activity/preview/{slug}', name: 'topic_activity_preview', methods: ['GET'])]
    public function __invoke(string $slug): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $activity = $this->activityRepository->findOneBy(['slug' => $slug]);
        if (null === $activity) {
            if (is_numeric($slug)) {
                $activity = $this->activityRepository->find((int) $slug);
            }

            if (null === $activity) {
                throw $this->createNotFoundException('Activity not found');
            }
        }

        $components = $activity->getComponents()->toArray();
        usort($components, fn ($a, $b) => $a->getPosition() <=> $b->getPosition());

        return $this->render('@TopicActivity/frontend/preview.html.twig', [
            'activity' => $activity,
            'components' => $components,
            'renderer' => $this->componentRenderer,
            'is_preview' => true,
        ]);
    }
}
