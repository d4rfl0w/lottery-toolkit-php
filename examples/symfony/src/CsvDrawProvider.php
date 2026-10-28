<?php

declare(strict_types=1);

namespace LotteryToolkitExample;

use Rupcom\LotteryToolkit\Draw\DrawCollection;
use Rupcom\LotteryToolkit\Draw\DrawProviderInterface;
use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\Source\CsvDrawSource;

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

        return $this->importer->import($game, new CsvDrawSource($csv));
    }
}
