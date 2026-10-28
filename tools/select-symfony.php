<?php

declare(strict_types=1);

// Run only in a disposable checkout: this intentionally changes composer.json.
$version = $argv[1] ?? '';
if (!in_array($version, ['6.4.*', '7.*'], true)) {
    fwrite(STDERR, "Usage: php tools/select-symfony.php '6.4.*'|'7.*'\n");
    exit(1);
}
$path = dirname(__DIR__).'/composer.json';
$config = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
foreach (['cache', 'clock', 'config', 'console', 'dependency-injection', 'error-handler',
    'event-dispatcher', 'filesystem', 'finder', 'framework-bundle', 'http-foundation',
    'http-kernel', 'options-resolver', 'process', 'routing', 'stopwatch', 'string', 'var-dumper', 'var-exporter', 'yaml'] as $component) {
    $config['require-dev']['symfony/'.$component] = $version;
}
ksort($config['require-dev']);
file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
