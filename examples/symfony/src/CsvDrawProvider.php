<?php

declare(strict_types=1);

namespace LotteryToolkitExample;

use Rupcom\LotteryToolkit\{DrawCollection, DrawImporter, DrawProviderInterface, GameRules};
use Rupcom\LotteryToolkit\Exception\ImportException;

final readonly class CsvDrawProvider implements DrawProviderInterface
{
    public function __construct(private DrawImporter $importer, private string $path)
    {
    }

    public function getDraws(GameRules $game): DrawCollection
    {
        $csv = @file_get_contents($this->path);
        if ($csv === false) {
            throw new ImportException('Cannot read the configured CSV file.');
        }

        return $this->importer->fromCsv($game, $csv);
    }
}
