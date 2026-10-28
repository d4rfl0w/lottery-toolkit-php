<?php

declare(strict_types=1);

use LotteryToolkitExample\CsvDrawProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('lottery_toolkit', [
        'provider' => CsvDrawProvider::class,
        'default_game' => 'lotto',
        'map_exceptions' => true,
        'games' => ['demo' => ['pools' => ['main' => ['count' => 3, 'max' => 10]]]],
    ]);
};
