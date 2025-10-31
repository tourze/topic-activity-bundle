<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityComponentRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(ActivityComponentRepository::class)]
#[RunTestsInSeparateProcesses]
final class ActivityComponentRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Create a test component to satisfy the countWithDataFixture test
        // This is needed because DataFixtures are not being loaded automatically
        $activity = $this->createActivity('Test Activity for DataFixture', 'test-activity-datafixture');

        $component = new ActivityComponent();
        $component->setActivity($activity);
        $component->setComponentType('test_fixture_component');
        $component->setPosition(1);
        $component->setComponentConfig(['test' => 'fixture_data']);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($component, true);
    }

    protected function createNewEntity(): object
    {
        $activity = $this->createActivity('Test Activity', 'test-activity-' . uniqid());

        $component = new ActivityComponent();
        $component->setActivity($activity);
        $component->setComponentType('test-component');
        $component->setPosition(1);
        $component->setComponentConfig(['test' => 'data']);

        return $component;
    }

    /**
     * @return ServiceEntityRepository<ActivityComponent>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(ActivityComponentRepository::class);
    }

    protected function createActivity(string $title, string $slug): Activity
    {
        $activity = new Activity();
        $activity->setTitle($title);
        $activity->setSlug($slug . '-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $repository = self::getService(ActivityRepository::class);
        $repository->save($activity, true);

        return $activity;
    }

    public function testSaveAndFindComponentShouldWorkCorrectly(): void
    {
        $activity = $this->createActivity('Test Activity', 'test-activity');

        $component = new ActivityComponent();
        $component->setActivity($activity);
        $component->setComponentType('test_component');
        $component->setPosition(1);
        $component->setComponentConfig(['content' => 'Test content']);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($component, true);

        $this->assertNotNull($component->getId());

        $foundComponent = $repository->find($component->getId());

        $this->assertNotNull($foundComponent);
        $this->assertEquals($component->getId(), $foundComponent->getId());
        $this->assertEquals('test_component', $foundComponent->getComponentType());
        $this->assertEquals(1, $foundComponent->getPosition());
        $this->assertEquals(['content' => 'Test content'], $foundComponent->getComponentConfig());
    }

    public function testFindByActivityShouldReturnComponentsOrderedByPosition(): void
    {
        $activity = $this->createActivity('Test Activity', 'test-activity');

        $component1 = new ActivityComponent();
        $component1->setActivity($activity);
        $component1->setComponentType('first_component');
        $component1->setPosition(2);
        $component1->setComponentConfig(['order' => 'second']);

        $component2 = new ActivityComponent();
        $component2->setActivity($activity);
        $component2->setComponentType('second_component');
        $component2->setPosition(1);
        $component2->setComponentConfig(['order' => 'first']);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($component1);
        $repository->save($component2, true);

        $components = $repository->findByActivity($activity);

        $this->assertCount(2, $components);
        $this->assertEquals(1, $components[0]->getPosition());
        $this->assertEquals(2, $components[1]->getPosition());
    }

    public function testRemoveComponentShouldDeleteFromDatabase(): void
    {
        $activity = $this->createActivity('Test Activity', 'test-activity');

        $component = new ActivityComponent();
        $component->setActivity($activity);
        $component->setComponentType('removable_component');
        $component->setPosition(1);
        $component->setComponentConfig(['removable' => true]);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($component, true);

        $componentId = $component->getId();
        $this->assertNotNull($componentId);

        $repository->remove($component, true);

        $foundComponent = $repository->find($componentId);
        $this->assertNull($foundComponent);
    }

    public function testComponentConfigShouldBeJson(): void
    {
        $activity = $this->createActivity('Test Activity', 'test-activity');

        $config = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'styles' => [
                'color' => '#ff0000',
                'fontSize' => '16px',
            ],
            'metadata' => [
                'version' => '1.0',
                'author' => 'Test Author',
            ],
        ];

        $component = new ActivityComponent();
        $component->setActivity($activity);
        $component->setComponentType('json_component');
        $component->setPosition(1);
        $component->setComponentConfig($config);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($component, true);

        $foundComponent = $repository->find($component->getId());

        $this->assertNotNull($foundComponent);
        $this->assertEquals($config, $foundComponent->getComponentConfig());
    }

    public function testFindByActivityAndTypeShouldFilterCorrectly(): void
    {
        $activity = $this->createActivity('Test Activity', 'test-activity');

        $textComponent = new ActivityComponent();
        $textComponent->setActivity($activity);
        $textComponent->setComponentType('text');
        $textComponent->setPosition(1);
        $textComponent->setComponentConfig(['content' => 'Text content']);

        $imageComponent = new ActivityComponent();
        $imageComponent->setActivity($activity);
        $imageComponent->setComponentType('image');
        $imageComponent->setPosition(2);
        $imageComponent->setComponentConfig(['src' => 'image.jpg']);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($textComponent);
        $repository->save($imageComponent, true);

        $textComponents = $repository->findByActivityAndType($activity, 'text');
        $imageComponents = $repository->findByActivityAndType($activity, 'image');

        $this->assertCount(1, $textComponents);
        $this->assertCount(1, $imageComponents);
        $this->assertEquals('text', $textComponents[0]->getComponentType());
        $this->assertEquals('image', $imageComponents[0]->getComponentType());
    }

    public function testFindVisibleByActivityShouldFilterCorrectly(): void
    {
        $activity = $this->createActivity('Visibility Test Activity', 'visibility-test-activity');

        $visibleComponent = new ActivityComponent();
        $visibleComponent->setActivity($activity);
        $visibleComponent->setComponentType('visible');
        $visibleComponent->setPosition(1);
        $visibleComponent->setComponentConfig(['visible' => true]);
        $visibleComponent->setIsVisible(true);

        $hiddenComponent = new ActivityComponent();
        $hiddenComponent->setActivity($activity);
        $hiddenComponent->setComponentType('hidden');
        $hiddenComponent->setPosition(2);
        $hiddenComponent->setComponentConfig(['visible' => false]);
        $hiddenComponent->setIsVisible(false);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($visibleComponent);
        $repository->save($hiddenComponent, true);

        $visibleComponents = $repository->findVisibleByActivity($activity);

        $this->assertCount(1, $visibleComponents);
        $this->assertTrue($visibleComponents[0]->isVisible());
    }

    public function testFindByTypeShouldFilterCorrectly(): void
    {
        $repository = self::getService(ActivityComponentRepository::class);

        // Clear existing components of type 'text' and 'image' to ensure test isolation
        $existingComponents = $repository->findBy(['componentType' => ['text', 'image']]);
        foreach ($existingComponents as $component) {
            $repository->remove($component, false);
        }
        $repository->flush();

        $activity1 = $this->createActivity('Activity 1', 'activity-1');
        $activity2 = $this->createActivity('Activity 2', 'activity-2');

        $textComponent1 = new ActivityComponent();
        $textComponent1->setActivity($activity1);
        $textComponent1->setComponentType('text');
        $textComponent1->setPosition(1);
        $textComponent1->setComponentConfig(['content' => 'Text 1']);

        $textComponent2 = new ActivityComponent();
        $textComponent2->setActivity($activity2);
        $textComponent2->setComponentType('text');
        $textComponent2->setPosition(1);
        $textComponent2->setComponentConfig(['content' => 'Text 2']);

        $imageComponent = new ActivityComponent();
        $imageComponent->setActivity($activity1);
        $imageComponent->setComponentType('image');
        $imageComponent->setPosition(2);
        $imageComponent->setComponentConfig(['src' => 'image.jpg']);

        $repository->save($textComponent1);
        $repository->save($textComponent2);
        $repository->save($imageComponent, true);

        $textComponents = $repository->findByType('text');
        $imageComponents = $repository->findByType('image');

        $this->assertCount(2, $textComponents);
        $this->assertCount(1, $imageComponents);
    }

    public function testCountByTypeShouldReturnCorrectCounts(): void
    {
        $repository = self::getService(ActivityComponentRepository::class);

        // Clear existing components to ensure test isolation
        $existingComponents = $repository->findAll();
        foreach ($existingComponents as $component) {
            $repository->remove($component, false);
        }
        $repository->flush();

        $activity1 = $this->createActivity('Count Activity 1', 'count-activity-1');
        $activity2 = $this->createActivity('Count Activity 2', 'count-activity-2');

        for ($i = 0; $i < 3; ++$i) {
            $textComponent = new ActivityComponent();
            $textComponent->setActivity($activity1);
            $textComponent->setComponentType('text');
            $textComponent->setPosition($i + 1);
            $textComponent->setComponentConfig(['content' => "Text {$i}"]);
            $repository->save($textComponent);
        }

        for ($i = 0; $i < 2; ++$i) {
            $imageComponent = new ActivityComponent();
            $imageComponent->setActivity($activity2);
            $imageComponent->setComponentType('image');
            $imageComponent->setPosition($i + 1);
            $imageComponent->setComponentConfig(['src' => "image{$i}.jpg"]);
            $repository->save($imageComponent);
        }

        $repository->flush();

        $counts = $repository->countByType();

        $this->assertIsArray($counts);
        $this->assertArrayHasKey('text', $counts);
        $this->assertArrayHasKey('image', $counts);
        $this->assertEquals(3, $counts['text']);
        $this->assertEquals(2, $counts['image']);
    }

    public function testReorderComponentsShouldUpdatePositions(): void
    {
        $activity = $this->createActivity('Reorder Activity', 'reorder-activity');

        $component1 = new ActivityComponent();
        $component1->setActivity($activity);
        $component1->setComponentType('first');
        $component1->setPosition(1);
        $component1->setComponentConfig(['order' => 1]);

        $component2 = new ActivityComponent();
        $component2->setActivity($activity);
        $component2->setComponentType('second');
        $component2->setPosition(2);
        $component2->setComponentConfig(['order' => 2]);

        $component3 = new ActivityComponent();
        $component3->setActivity($activity);
        $component3->setComponentType('third');
        $component3->setPosition(3);
        $component3->setComponentConfig(['order' => 3]);

        $repository = self::getService(ActivityComponentRepository::class);
        $repository->save($component1);
        $repository->save($component2);
        $repository->save($component3, true);

        // Reorder: move component3 to first position
        $id1 = $component1->getId();
        $id2 = $component2->getId();
        $id3 = $component3->getId();
        $this->assertNotNull($id1);
        $this->assertNotNull($id2);
        $this->assertNotNull($id3);
        $newOrder = [$id3, $id1, $id2];
        $repository->reorderComponents($activity, $newOrder);

        $reorderedComponents = $repository->findByActivity($activity);

        $this->assertEquals($component3->getId(), $reorderedComponents[0]->getId());
        $this->assertEquals($component1->getId(), $reorderedComponents[1]->getId());
        $this->assertEquals($component2->getId(), $reorderedComponents[2]->getId());
    }

    public function testFlushShouldPersistPendingChanges(): void
    {
        $activity = $this->createActivity('Flush Test Activity', 'flush-test-activity');

        $component = new ActivityComponent();
        $component->setActivity($activity);
        $component->setComponentType('flush_test');
        $component->setPosition(1);
        $component->setComponentConfig(['test' => 'flush']);

        $repository = self::getService(ActivityComponentRepository::class);

        // Save without flushing
        $repository->save($component, false);

        // Component should not be immediately available in fresh query
        $this->assertNull($component->getId());

        // Now flush
        $repository->flush();

        // Component should now have an ID and be persisted
        $this->assertNotNull($component->getId());

        // Verify we can find it
        $foundComponent = $repository->find($component->getId());
        $this->assertNotNull($foundComponent);
        $this->assertEquals('flush_test', $foundComponent->getComponentType());
    }
}
