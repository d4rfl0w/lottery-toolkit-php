<?php

declare(strict_types=1);

use LotteryToolkitExample\CsvDrawProvider;
use LotteryToolkitExample\LotteryCommand;
use LotteryToolkitExample\LotteryController;
use LotteryToolkitExample\LotteryService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autowire()->autoconfigure();
    $services->set(CsvDrawProvider::class)->arg('$path', '%kernel.project_dir%/data/draws.csv');
    $services->set(LotteryService::class);
    $services->set(LotteryController::class)->tag('controller.service_arguments');
    $services->set(LotteryCommand::class);
    if ($container->env() === 'test') {
        $services->alias('test.game_registry', \Rupcom\LotteryToolkit\Game\GameRegistry::class)->public();
        $services->set('logger', \Psr\Log\NullLogger::class);
    }
};
