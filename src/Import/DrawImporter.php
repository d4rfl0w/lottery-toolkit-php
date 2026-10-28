<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import;

use Rupcom\LotteryToolkit\Draw\Draw;
use Rupcom\LotteryToolkit\Draw\DrawCollection;
use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Exception\LotteryException;
use Rupcom\LotteryToolkit\Exception\ValidationException;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\Internal\DrawDateParser;

/** Builds a validated history atomically, independently of the input format. */
final class DrawImporter
{
    public function import(GameRules $game, DrawSourceInterface $source): DrawCollection
    {
        $draws = [];
        $ids = [];
        $position = 1;
        $dateParser = new DrawDateParser();

        try {
            foreach ($source->records($game) as $record) {
                if (isset($ids[$record->id])) {
                    throw new ValidationException("Duplicate draw ID: {$record->id}.");
                }

                $draws[] = new Draw($game, $record->id, $dateParser->parse($record->date), $record->numbers);
                $ids[$record->id] = true;
                ++$position;
            }
        } catch (LotteryException $exception) {
            throw new ImportException("Import failed at record $position: ".$exception->getMessage(), 0, $exception);
        }

        return new DrawCollection($game, $draws);
    }
}
