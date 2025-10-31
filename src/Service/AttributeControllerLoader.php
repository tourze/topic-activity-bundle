<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentPreviewController;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentsGetController;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentsSaveController;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentTypesController;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\IndexController as EditorIndexController;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\LayoutSaveController;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\PublishController;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\DeviceController;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\IndexController as StatsIndexController;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\SourceController;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\SummaryController;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\TrackEventController;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\TrendController;
use Tourze\TopicActivityBundle\Controller\Frontend\PreviewController;
use Tourze\TopicActivityBundle\Controller\Frontend\ShowController;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();

        $controllers = [
            // Editor controllers
            EditorIndexController::class,
            ComponentsGetController::class,
            ComponentsSaveController::class,
            ComponentPreviewController::class,
            ComponentTypesController::class,
            LayoutSaveController::class,
            PublishController::class,

            // Stats controllers
            StatsIndexController::class,
            SummaryController::class,
            TrendController::class,
            DeviceController::class,
            SourceController::class,
            TrackEventController::class,

            // Frontend controllers
            ShowController::class,
            PreviewController::class,
        ];

        foreach ($controllers as $controller) {
            $collection->addCollection($this->controllerLoader->load($controller));
        }

        return $collection;
    }
}
