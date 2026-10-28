<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import;

/** Input data; date and number rules are validated during import. */
final readonly class DrawRecord
{
    /** @param array<array-key, mixed> $numbers */
    public function __construct(
        public string $id,
        public string $date,
        public array $numbers,
    ) {
    }
}
