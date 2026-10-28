<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import\Source;

use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\DrawSourceInterface;

final readonly class JsonDrawSource implements DrawSourceInterface
{
    public function __construct(private string $json)
    {
    }

    public function records(GameRules $game): iterable
    {
        try {
            $rows = json_decode($this->json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ImportException('Invalid JSON: '.$exception->getMessage(), 0, $exception);
        }

        if (!is_array($rows) || !array_is_list($rows) || !str_starts_with(ltrim($this->json), '[')) {
            throw new ImportException('JSON must contain a top-level list of draw records.');
        }

        foreach ($rows as $row) {
            if (!is_array($row) || count($row) !== 3 || !is_string($row['id'] ?? null)
                || !is_string($row['date'] ?? null) || !is_array($row['numbers'] ?? null)) {
                throw new ImportException('Expected exactly id (string), date (string), numbers (pool map).');
            }

            yield new DrawRecord($row['id'], $row['date'], $row['numbers']);
        }
    }
}
