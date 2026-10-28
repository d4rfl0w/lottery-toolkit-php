<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Draw;

use Rupcom\LotteryToolkit\Exception\ValidationException;
use Rupcom\LotteryToolkit\Game\GameRules;

final readonly class Draw
{
    /** @var array<string, list<int>> */
    public array $numbers;
    public \DateTimeImmutable $date;

    /** @param array<array-key, mixed> $numbers */
    public function __construct(
        public GameRules $game,
        public string $id,
        \DateTimeImmutable $date,
        array $numbers,
    ) {
        if (trim($id) === '') {
            throw new ValidationException('Draw ID must not be blank.');
        }
        $this->date = $date->setTimezone(new \DateTimeZone('UTC'));
        $this->numbers = $game->validate($numbers);
    }
}
