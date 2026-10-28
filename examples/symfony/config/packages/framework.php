<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => 'public-synthetic-example-not-a-production-secret',
        'test' => '%kernel.debug%',
        'router' => ['utf8' => true],
        'http_method_override' => false,
        'handle_all_throwables' => true,
    ]);
};
