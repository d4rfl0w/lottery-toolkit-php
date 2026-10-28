<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Statistics;

use Rupcom\LotteryToolkit\Draw\DrawCollection;

final class StatisticsAnalyzer
{
    public function analyze(DrawCollection $draws, string $pool = 'main'): StatisticsReport
    {
        $rules = $draws->game->pool($pool);
        $frequency = array_fill_keys(range($rules->min, $rules->max), 0);
        $recency = array_fill_keys(range($rules->min, $rules->max), null);
        $oddEven = $sums = $pairs = [];
        foreach ($draws as $index => $draw) {
            $numbers = $draw->numbers[$pool];
            $odd = 0;
            foreach ($numbers as $number) {
                ++$frequency[$number];
                $recency[$number] = count($draws) - $index - 1;
                $odd += $number % 2;
            }
            $distribution = $odd.':'.(count($numbers) - $odd);
            $oddEven[$distribution] = ($oddEven[$distribution] ?? 0) + 1;
            $sum = array_sum($numbers);
            $sums[$sum] = ($sums[$sum] ?? 0) + 1;
            $seenPairs = [];
            for ($i = 0, $count = count($numbers); $i < $count; ++$i) {
                for ($j = $i + 1; $j < $count; ++$j) {
                    $seenPairs[$numbers[$i].':'.$numbers[$j]] = true;
                }
            }
            foreach ($seenPairs as $pair => $_) {
                $pairs[$pair] = ($pairs[$pair] ?? 0) + 1;
            }
        }
        ksort($oddEven);
        ksort($sums, SORT_NUMERIC);
        ksort($pairs);

        return new StatisticsReport($draws->game->id, $pool, count($draws), $frequency, $recency, $oddEven, $sums, $pairs);
    }
}
