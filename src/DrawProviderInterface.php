<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

interface DrawProviderInterface
{
    /**
     * Return a validated history for exactly these rules, including an empty history.
     * IDs must be unique. Implementations own I/O, credentials, pagination and retries.
     * @throws \Rupcom\LotteryToolkit\Exception\LotteryException On a domain/import failure.
     */
    public function getDraws(GameRules $game): DrawCollection;
}
