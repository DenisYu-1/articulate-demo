<?php

namespace App\Command\Examples;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:migrations-workflow', description: 'Migrations workflow example')]
final class MigrationsWorkflowExampleCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Migrations workflow');
        $io->listing([
            'articulate:init - creates migration tracking table',
            'articulate:diff - generates migrations from entity vs DB diff',
            'articulate:migrate - runs pending migrations',
        ]);
        $io->text('Run these commands manually. See documentation/migrations for details.');

        return Command::SUCCESS;
    }
}
