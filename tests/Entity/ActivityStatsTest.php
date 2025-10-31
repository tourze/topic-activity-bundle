<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityStats;

/**
 * @internal
 */
#[CoversClass(ActivityStats::class)]
final class ActivityStatsTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ActivityStats();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'pv' => ['pv', 123],
            'uv' => ['uv', 123],
            'shareCount' => ['shareCount', 123],
            'formSubmitCount' => ['formSubmitCount', 123],
            'conversionCount' => ['conversionCount', 123],
            'stayDuration' => ['stayDuration', 123.45],
            'bounceRate' => ['bounceRate', 123.45],
        ];
    }

    public function testEntityCreation(): void
    {
        $activity = new Activity();
        $stats = new ActivityStats();
        $stats->setActivity($activity);

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $this->assertSame($activity, $stats->getActivity());
        $this->assertEquals(0, $stats->getPv());
        $this->assertEquals(0, $stats->getUv());
        $this->assertEquals(0, $stats->getShareCount());
        $this->assertEquals(0, $stats->getFormSubmitCount());
        $this->assertEquals(0, $stats->getConversionCount());
        $this->assertEquals(0.0, $stats->getStayDuration());
        $this->assertEquals(0.0, $stats->getBounceRate());
    }

    public function testIncrementMethods(): void
    {
        $stats = new ActivityStats();

        $stats->incrementPv(5);
        $this->assertEquals(5, $stats->getPv());

        $stats->incrementUv(3);
        $this->assertEquals(3, $stats->getUv());

        $stats->incrementShareCount(2);
        $this->assertEquals(2, $stats->getShareCount());

        $stats->incrementFormSubmitCount(1);
        $this->assertEquals(1, $stats->getFormSubmitCount());

        $stats->incrementConversionCount(1);
        $this->assertEquals(1, $stats->getConversionCount());

        $stats->addStayDuration(30.5);
        $this->assertEquals(30.5, $stats->getStayDuration());
    }

    public function testConversionRate(): void
    {
        $stats = new ActivityStats();

        // Test with zero UV
        $this->assertEquals(0.0, $stats->getConversionRate());

        // Test with some data
        $stats->setUv(100);
        $stats->setConversionCount(5);
        $this->assertEquals(5.0, $stats->getConversionRate());
    }

    public function testAverageStayDuration(): void
    {
        $stats = new ActivityStats();

        // Test with zero PV
        $this->assertEquals(0.0, $stats->getAverageStayDuration());

        // Test with some data
        $stats->setPv(10);
        $stats->setStayDuration(50.0);
        $this->assertEquals(5.0, $stats->getAverageStayDuration());
    }

    public function testMerge(): void
    {
        $stats1 = new ActivityStats();
        $stats1->setPv(100);
        $stats1->setUv(80);
        $stats1->setShareCount(10);
        $stats1->setFormSubmitCount(5);
        $stats1->setConversionCount(2);
        $stats1->setStayDuration(200.0);
        $stats1->setBounceRate(30.0);

        $stats2 = new ActivityStats();
        $stats2->setPv(50);
        $stats2->setUv(40);
        $stats2->setShareCount(5);
        $stats2->setFormSubmitCount(3);
        $stats2->setConversionCount(1);
        $stats2->setStayDuration(100.0);
        $stats2->setBounceRate(20.0);

        $stats1->merge($stats2);

        $this->assertEquals(150, $stats1->getPv());
        $this->assertEquals(120, $stats1->getUv());
        $this->assertEquals(15, $stats1->getShareCount());
        $this->assertEquals(8, $stats1->getFormSubmitCount());
        $this->assertEquals(3, $stats1->getConversionCount());
        $this->assertEquals(300.0, $stats1->getStayDuration());
        // Bounce rate should be weighted average
        $this->assertEquals(26.67, round($stats1->getBounceRate(), 2));
    }

    public function testToString(): void
    {
        $stats = new ActivityStats();
        $this->assertStringContainsString('Activity Stats #', $stats->__toString());
        $this->assertStringContainsString(date('Y-m-d'), $stats->__toString());
    }

    public function testStatsData(): void
    {
        $stats = new ActivityStats();

        $deviceStats = ['mobile' => 60, 'desktop' => 40];
        $sourceStats = ['direct' => 50, 'search' => 30, 'social' => 20];
        $regionStats = ['beijing' => 40, 'shanghai' => 35, 'guangzhou' => 25];

        $stats->setDeviceStats($deviceStats);
        $stats->setSourceStats($sourceStats);
        $stats->setRegionStats($regionStats);

        $this->assertEquals($deviceStats, $stats->getDeviceStats());
        $this->assertEquals($sourceStats, $stats->getSourceStats());
        $this->assertEquals($regionStats, $stats->getRegionStats());
    }
}
