<?php

declare(strict_types=1);

namespace LotteryToolkitExample;

use Rupcom\LotteryToolkit\Exception\LotteryException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'lottery:analyse', description: 'Analyse synthetic CSV draws; optionally check bets from JSON.')]
final class LotteryCommand extends Command
{
    public function __construct(private readonly LotteryService $lottery)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('bets', null, InputOption::VALUE_REQUIRED, 'A JSON list of bet pool maps.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = ['statistics' => $this->lottery->statistics()];
            $raw = $input->getOption('bets');
            if ($raw !== null) {
                if (!is_string($raw)) {
                    throw new \InvalidArgumentException('The bets option must be a JSON string.');
                }
                $bets = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($bets)) {
                    throw new \InvalidArgumentException('Bets must be a JSON array.');
                }
                $result['matches'] = $this->lottery->check($bets);
            }
            $output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        } catch (LotteryException | \JsonException | \InvalidArgumentException $exception) {
            $output->writeln('<error>'.OutputFormatter::escape($exception->getMessage()).'</error>');

            return Command::FAILURE;
        }
    }
}
