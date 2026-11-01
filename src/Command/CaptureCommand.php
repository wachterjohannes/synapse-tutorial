<?php

namespace App\Command;

use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Target;

#[AsCommand('app:capture', 'Hands raw material to the capture agent, which files it in the brain')]
final class CaptureCommand
{
    public function __construct(
        #[Target('capturer')]
        private readonly AgentInterface $agent,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('The raw text to capture')] string $text): int
    {
        $io->writeln($this->agent->call($text)->getContent());

        return 0;
    }
}
