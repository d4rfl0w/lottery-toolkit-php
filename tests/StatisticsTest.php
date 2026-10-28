<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use PHPUnit\Framework\TestCase;
use Rupcom\LotteryToolkit\Draw\DrawCollection;
use Rupcom\LotteryToolkit\Exception\ValidationException;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Game\NumberPool;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\Source\InMemoryDrawSource;
use Rupcom\LotteryToolkit\Statistics\FrequencyOrder;
use Rupcom\LotteryToolkit\Statistics\StatisticsAnalyzer;

final class StatisticsTest extends TestCase
{
    public function testExactStatisticsAndRankingsOnUnorderedHistory(): void
    {
        $game = new GameRules('small', [new NumberPool('main', 3, 1, 8)]);
        $draws = (new DrawImporter())->import($game, new InMemoryDrawSource([
            new DrawRecord('c', '2026-01-03', ['main' => [2, 4, 6]]),
            new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3]]),
            new DrawRecord('b', '2026-01-02', ['main' => [1, 3, 5]]),
        ]));
        $report = (new StatisticsAnalyzer())->analyze($draws);
        self::assertSame(3, $report->drawCount);
        self::assertSame([1 => 2, 2 => 2, 3 => 2, 4 => 1, 5 => 1, 6 => 1, 7 => 0, 8 => 0], $report->frequency);
        self::assertSame([1 => 1, 2 => 0, 3 => 1, 4 => 0, 5 => 1, 6 => 0, 7 => null, 8 => null], $report->drawsSinceLastOccurrence);
        self::assertSame(['0:3' => 1, '2:1' => 1, '3:0' => 1], $report->oddEvenDistribution);
        self::assertSame([6 => 1, 9 => 1, 12 => 1], $report->sumDistribution);
        self::assertSame([1 => 2, 2 => 2], $report->mostFrequent(2));
        self::assertSame([7 => 0, 8 => 0, 4 => 1], $report->leastFrequent(3));
        self::assertSame($report->leastFrequent(3), $report->ranked(FrequencyOrder::LeastFrequent, 3));
        self::assertSame(['1:3' => 2, '1:2' => 1], $report->mostFrequentPairs(2));
        $recent = (new StatisticsAnalyzer())->analyze($draws->last(1));
        self::assertNull($recent->drawsSinceLastOccurrence[1]);
        self::assertSame(0, $recent->drawsSinceLastOccurrence[2]);
        $json = json_decode(json_encode($report, JSON_THROW_ON_ERROR));
        self::assertInstanceOf(\stdClass::class, $json->frequency);
        self::assertSame(2, $json->frequency->{'1'});
    }

    public function testEmptyHistoryAndUnknownPool(): void
    {
        $report = (new StatisticsAnalyzer())->analyze(new DrawCollection(GameRules::lotto()));
        self::assertSame(0, $report->drawCount);
        self::assertSame(0, array_sum($report->frequency));
        self::assertCount(49, $report->drawsSinceLastOccurrence);
        self::assertNull($report->drawsSinceLastOccurrence[49]);
        self::assertSame([], $report->mostFrequentPairs());
        self::assertSame([1 => 0, 2 => 0], $report->leastFrequent(2));
        self::assertInstanceOf(\stdClass::class, json_decode(json_encode($report))->pairs);
        $this->expectException(ValidationException::class);
        (new StatisticsAnalyzer())->analyze(new DrawCollection(GameRules::lotto()), 'euro');
    }

    public function testIndependentEuroPool(): void
    {
        $draws = (new DrawImporter())->import(GameRules::eurojackpot(), new InMemoryDrawSource([
            new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3, 4, 5], 'euro' => [10, 12]]),
        ]));
        $report = (new StatisticsAnalyzer())->analyze($draws, 'euro');
        self::assertSame(0, $report->frequency[1]);
        self::assertSame(1, $report->frequency[12]);
        self::assertSame(['10:12' => 1], $report->pairs);
        self::assertSame([22 => 1], $report->sumDistribution);
    }

    public function testDuplicatesCountOccurrencesButPairsOnlyOncePerDraw(): void
    {
        $game = new GameRules('repeat', [new NumberPool('main', 4, 1, 3, true)]);
        $draws = (new DrawImporter())->import($game, new InMemoryDrawSource([
            new DrawRecord('a', '2026-01-01', ['main' => [1, 1, 1, 2]]),
        ]));
        $report = (new StatisticsAnalyzer())->analyze($draws);
        self::assertSame([1 => 3, 2 => 1, 3 => 0], $report->frequency);
        self::assertSame(['1:1' => 1, '1:2' => 1], $report->pairs);
        self::assertSame(['3:1' => 1], $report->oddEvenDistribution);
    }
}
