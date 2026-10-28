<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import;

use Rupcom\LotteryToolkit\Game\GameRules;

interface DrawSourceInterface
{
    /**
     * Yield records in source order, without silently dropping invalid data.
     *
     * @return iterable<DrawRecord>
     * @throws \Rupcom\LotteryToolkit\Exception\ImportException For malformed source data.
     */
    public function records(GameRules $game): iterable;
}
