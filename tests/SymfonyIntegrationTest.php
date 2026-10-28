<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use LotteryToolkitExample\CsvDrawProvider;
use LotteryToolkitExample\Kernel;
use LotteryToolkitExample\LotteryService;
use PHPUnit\Framework\TestCase;
use Rupcom\LotteryToolkit\Bridge\Symfony\DomainExceptionSubscriber;
use Rupcom\LotteryToolkit\Bridge\Symfony\LotteryToolkitBundle;
use Rupcom\LotteryToolkit\Draw\DrawCollection;
use Rupcom\LotteryToolkit\Draw\DrawProviderInterface;
use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Exception\ValidationException;
use Rupcom\LotteryToolkit\Game\GameRegistry;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Statistics\StatisticsAnalyzer;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class SymfonyIntegrationTest extends TestCase
{
    private ?Kernel $kernel = null;

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
        $this->kernel = null;
    }

    private function boot(): Kernel
    {
        $this->kernel = new Kernel('test', true);
        $this->kernel->boot();

        return $this->kernel;
    }

    public function testRealCompiledContainerAutowiresCoreAndProvider(): void
    {
        $kernel = $this->boot();
        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(StatisticsAnalyzer::class, $container->get(StatisticsAnalyzer::class));
        self::assertInstanceOf(CsvDrawProvider::class, $container->get(DrawProviderInterface::class));
        self::assertSame('lotto', $container->get(GameRules::class)->id);
        self::assertSame(3, $container->get(GameRegistry::class)->get('demo')->pool('main')->count);
        self::assertSame(3, $container->get(LotteryService::class)->statistics()->drawCount);
    }

    public function testRestStatisticsAndTicketMatchingShareTheService(): void
    {
        $kernel = $this->boot();
        $statistics = $kernel->handle(Request::create('/statistics'));
        self::assertSame(200, $statistics->getStatusCode());
        self::assertSame('application/json', $statistics->headers->get('content-type'));
        self::assertSame(3, json_decode($statistics->getContent(), true)['drawCount']);
        $request = Request::create('/tickets/check', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"bets":[{"main":[1,3,5,7,9,11]},{"main":[1,2,3,4,5,6]}]}');
        $response = $kernel->handle($request);
        self::assertSame(200, $response->getStatusCode());
        $matches = json_decode($response->getContent(), true)['matches'];
        self::assertSame(6, $matches[0]['counts']['main']);
        self::assertSame(3, $matches[1]['counts']['main']);
    }

    public function testInvalidTicketBecomes422AndMalformedJsonBecomes400(): void
    {
        $kernel = $this->boot();
        foreach (['{"bets":[{"main":[1,1,2,3,4,5]}]}', '{"bets":null}', 'null'] as $content) {
            $response = $kernel->handle(Request::create('/tickets/check', 'POST', content: $content));
            self::assertSame(422, $response->getStatusCode());
            self::assertSame(['error' => 'invalid_lottery_data'], json_decode($response->getContent(), true));
        }
        $response = $kernel->handle(Request::create('/tickets/check', 'POST', content: '{'));
        self::assertSame(400, $response->getStatusCode());
        self::assertSame(405, $kernel->handle(Request::create('/tickets/check', 'GET'))->getStatusCode());
    }

    public function testConsoleCallsSameStatisticsAndCheckingFunctions(): void
    {
        $kernel = $this->boot();
        $command = (new Application($kernel))->find('lottery:analyse');
        $tester = new CommandTester($command);
        self::assertSame(0, $tester->execute(['--bets' => '[{"main":[1,3,5,7,9,11]}]']));
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(3, $result['statistics']['drawCount']);
        self::assertSame(6, $result['matches'][0]['total']);
        self::assertSame(1, $tester->execute(['--bets' => '[{"main":[1]}]']));
        self::assertStringContainsString('exactly 6', $tester->getDisplay());
        self::assertSame(1, $tester->execute(['--bets' => '{']));
        self::assertSame(1, $tester->execute(['--bets' => 'null']));
    }

    public function testCustomProviderCanReplaceCsvWithoutChangingConsumers(): void
    {
        $container = $this->boot()->getContainer()->get('test.service_container');
        $container->set(CsvDrawProvider::class, new class () implements DrawProviderInterface {
            public function getDraws(GameRules $game): DrawCollection
            {
                return new DrawCollection($game);
            }
        });
        self::assertSame(0, $container->get(LotteryService::class)->statistics()->drawCount);
    }

    public function testDefaultConfigurationDoesNotExposeHttpExceptionsOrProvideFakeData(): void
    {
        $builder = $this->compileBundle([]);
        self::assertFalse($builder->has(DomainExceptionSubscriber::class));
        self::assertFalse($builder->has(DrawProviderInterface::class));
        self::assertSame('lotto', $builder->get(GameRules::class)->id);
    }

    public function testConfiguredGameAndProviderAreAvailableInCompiledContainer(): void
    {
        $builder = $this->compileBundle([
            'provider' => 'custom.provider',
            'default_game' => 'tiny',
            'games' => ['tiny' => ['pools' => ['main' => ['count' => 2, 'min' => 10, 'max' => 11]]]],
        ]);
        self::assertSame('tiny', $builder->get(GameRules::class)->id);
        self::assertSame(10, $builder->get(GameRegistry::class)->get('tiny')->pool('main')->min);
        self::assertInstanceOf(EmptyProvider::class, $builder->get(DrawProviderInterface::class));
    }

    public function testInvalidGameConfigurationFailsAtCompileTime(): void
    {
        $this->expectException(ValidationException::class);
        $this->compileBundle(['games' => ['tiny' => ['pools' => ['main' => ['count' => 6, 'max' => 5]]]]]);
    }

    public function testUnknownConfigurationKeyFailsAtCompileTime(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->compileBundle(['typo' => true]);
    }

    public function testUnknownDefaultGameFailsAtCompileTime(): void
    {
        $this->expectException(ValidationException::class);
        $this->compileBundle(['default_game' => 'missing']);
    }

    public function testSubscriberDoesNotHideImportFailures(): void
    {
        $kernel = $this->createStub(KernelInterface::class);
        $event = new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, new ImportException('private path'));
        (new DomainExceptionSubscriber())->onException($event);
        self::assertFalse($event->hasResponse());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidScalarConfiguration')]
    public function testConfigurationRejectsNonStringServiceAndGameIds(array $config): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->compileBundle($config);
    }

    public static function invalidScalarConfiguration(): iterable
    {
        yield [['provider' => true]];
        yield [['provider' => 42]];
        yield [['provider' => ' ']];
        yield [['default_game' => 42]];
    }

    public function testMissingConfiguredProviderFailsCompilation(): void
    {
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException::class);
        $this->compileBundle(['provider' => 'missing.provider']);
    }

    private function compileBundle(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/lottery-toolkit-tests');
        $bundle = new LotteryToolkitBundle();
        $extension = $bundle->getContainerExtension();
        $container->registerExtension($extension);
        $container->register('custom.provider', EmptyProvider::class);
        $container->loadFromExtension('lottery_toolkit', $config);
        $container->addCompilerPass(new class () implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach ([GameRules::class, GameRegistry::class, DrawProviderInterface::class] as $id) {
                    if ($container->hasAlias($id)) {
                        $container->getAlias($id)->setPublic(true);
                    } elseif ($container->hasDefinition($id)) {
                        $container->getDefinition($id)->setPublic(true);
                    }
                }
            }
        });
        $container->compile();

        return $container;
    }
}
