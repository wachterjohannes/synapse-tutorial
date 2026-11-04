<?php

namespace App\Command;

use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Target;

#[AsCommand('app:ask', 'Asks the read agent a question about your notes')]
final class AskCommand
{
    public function __construct(
        #[Target('reader')]
        private readonly AgentInterface $agent,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('Your question')] string $question): int
    {
        $io->writeln($this->agent->call($question)->getContent());

        return 0;
    }
}
