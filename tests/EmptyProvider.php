<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use Rupcom\LotteryToolkit\{DrawCollection, DrawProviderInterface, GameRules};

final class EmptyProvider implements DrawProviderInterface
{
    public function getDraws(GameRules $game): DrawCollection
    {
        return new DrawCollection($game);
    }
}
