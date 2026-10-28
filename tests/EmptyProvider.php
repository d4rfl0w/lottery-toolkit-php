<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use Rupcom\LotteryToolkit\Draw\DrawCollection;
use Rupcom\LotteryToolkit\Draw\DrawProviderInterface;
use Rupcom\LotteryToolkit\Game\GameRules;

final class EmptyProvider implements DrawProviderInterface
{
    public function getDraws(GameRules $game): DrawCollection
    {
        return new DrawCollection($game);
    }
}
