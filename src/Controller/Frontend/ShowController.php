<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Service\ComponentRenderer;

final class ShowController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityEventRepository $activityEventRepository,
        private readonly ComponentRenderer $componentRenderer,
    ) {
    }

    #[Route(path: '/activity/{slug}', name: 'topic_activity_show', methods: ['GET'])]
    public function __invoke(string $slug): Response
    {
        $activity = $this->activityRepository->findOneBy(['slug' => $slug]);
        if (null === $activity || !$this->canViewActivity($activity)) {
            throw $this->createNotFoundException('Activity not found');
        }

        $this->recordPageView($activity);

        $components = $activity->getComponents()->toArray();
        usort($components, fn ($a, $b) => $a->getPosition() <=> $b->getPosition());

        return $this->render('@TopicActivity/frontend/show.html.twig', [
            'activity' => $activity,
            'components' => $components,
            'renderer' => $this->componentRenderer,
        ]);
    }

    private function canViewActivity(Activity $activity): bool
    {
        $status = $activity->getStatus();

        if (ActivityStatus::PUBLISHED !== $status) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $startTime = $activity->getStartTime();
        $endTime = $activity->getEndTime();

        if (null !== $startTime && $startTime > $now) {
            return false;
        }

        if (null !== $endTime && $endTime < $now) {
            return false;
        }

        return true;
    }

    private function recordPageView(Activity $activity): void
    {
        try {
            $request = $this->getRequest();
            $event = $this->activityEventRepository->createEvent(
                $activity,
                'page_view',
                [
                    'ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'referer' => $request->headers->get('Referer'),
                ]
            );

            $this->activityEventRepository->save($event, true);
        } catch (\Exception $e) {
            // Log error but don't fail the page load
        }
    }

    private function getRequest(): Request
    {
        $requestStack = $this->container->get('request_stack');
        if (!$requestStack instanceof RequestStack) {
            throw new BadRequestHttpException('Request stack service not found');
        }

        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('No request available');
        }

        return $request;
    }
}
