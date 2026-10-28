<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Bridge\Symfony;

use Rupcom\LotteryToolkit\{DrawImporter, DrawProviderInterface, GameRegistry, GameRules, NumberGenerator, NumberPool, StatisticsAnalyzer, TicketChecker};
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/** Optional integration; no Symfony class is loaded when using the core on its own. */
final class LotteryToolkitBundle extends AbstractBundle
{
    protected string $extensionAlias = 'lottery_toolkit';

    public function configure(DefinitionConfigurator $definition): void
    {
        $this->configureRoot($definition->rootNode());
    }

    // Symfony 6.4 exposes a broader root type than 7.x; validate it at this boundary.
    private function configureRoot(object $root): void
    {
        if (!$root instanceof ArrayNodeDefinition) {
            throw new \LogicException('Lottery Toolkit configuration requires an array root.');
        }
        $options = $root->children();
        $options->scalarNode('provider')->defaultNull()
            ->validate()->ifTrue(static fn (mixed $value): bool => $value !== null && (!is_string($value) || trim($value) === ''))
            ->thenInvalid('Provider must be a non-blank service ID or null.')->end();
        $options->scalarNode('default_game')->defaultValue('lotto')->cannotBeEmpty()
            ->validate()->ifTrue(static fn (mixed $value): bool => !is_string($value) || !preg_match('/^[a-z][a-z0-9_]*$/D', $value))
            ->thenInvalid('Default game must be a lowercase game identifier.')->end();
        $options->booleanNode('map_exceptions')->defaultFalse();
        $games = $options->arrayNode('games')->useAttributeAsKey('id')->arrayPrototype()->children();
        $pools = $games->arrayNode('pools')->isRequired()->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')->arrayPrototype()->children();
        $pools->integerNode('count')->isRequired()->min(1)->max(100);
        $pools->integerNode('min')->defaultValue(1)->min(1);
        $pools->integerNode('max')->isRequired()->min(1);
        $pools->booleanNode('allow_duplicates')->defaultFalse();
    }

    /**
     * @param array{provider: ?string, default_game: string, map_exceptions: bool, games: array<string, array{pools: array<string, array{count: positive-int, min: int, max: int, allow_duplicates: bool}>}>} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        foreach ([DrawImporter::class, StatisticsAnalyzer::class, TicketChecker::class, NumberGenerator::class] as $class) {
            $builder->register($class, $class);
        }
        $games = (new GameRegistry())->all();
        foreach ($config['games'] as $id => $definition) {
            $pools = [];
            foreach ($definition['pools'] as $name => $pool) {
                $pools[] = new NumberPool($name, $pool['count'], $pool['min'], $pool['max'], $pool['allow_duplicates']);
            }
            $games[$id] = new GameRules($id, $pools);
        }
        (new GameRegistry($games))->get($config['default_game']);
        $references = [];
        foreach ($games as $id => $game) {
            $pools = [];
            foreach ($game->pools as $pool) {
                $pools[] = new Definition(NumberPool::class, [$pool->name, $pool->count, $pool->min, $pool->max, $pool->allowDuplicates]);
            }
            $serviceId = 'lottery_toolkit.game.'.$id;
            $builder->register($serviceId, GameRules::class)->setArguments([$id, $pools]);
            $references[] = new Reference($serviceId);
        }
        $builder->register(GameRegistry::class, GameRegistry::class)->setArgument('$games', $references);
        $builder->setAlias(GameRules::class, 'lottery_toolkit.game.'.$config['default_game']);
        if ($config['provider'] !== null) {
            $builder->setAlias(DrawProviderInterface::class, $config['provider']);
        }
        if ($config['map_exceptions']) {
            $builder->register(DomainExceptionSubscriber::class, DomainExceptionSubscriber::class)->addTag('kernel.event_subscriber');
        }
    }
}
