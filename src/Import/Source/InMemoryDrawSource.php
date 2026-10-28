<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import\Source;

use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\DrawSourceInterface;

final readonly class InMemoryDrawSource implements DrawSourceInterface
{
    /** @param iterable<DrawRecord> $records Generators are consumed once. */
    public function __construct(private iterable $records)
    {
    }

    public function records(GameRules $game): iterable
    {
        yield from $this->records;
    }
}
