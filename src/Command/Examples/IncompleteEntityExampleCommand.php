<?php

namespace App\Command\Examples;

use App\Entity\IncompleteSettings;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:example:incomplete-entity',
    description: 'Demonstrates runtime and schema-level failures for unmapped required columns',
)]
final class IncompleteEntityExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        // Create a table that has a required column the entity does not map.
        // Realistic scenario: a DBA adds a column; the dev forgets to add it to the entity.
        $connection->exec(
            'CREATE TABLE IF NOT EXISTS settings_demo (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key  VARCHAR(255) NOT NULL,
                group_name   VARCHAR(100) NOT NULL
            )'
        );

        $io->section('1. Runtime failure — INSERT without the unmapped required column');
        $io->text('IncompleteSettings maps settings_demo but has no $groupName property.');
        $io->text('MySQL cannot supply a value for group_name (NOT NULL, no default) → PDOException.');

        try {
            $this->entityManager->transactional(function (EntityManager $em) {
                $setting = new IncompleteSettings();
                $setting->settingKey = 'theme';
                $em->persist($setting);
                $em->flush();
            });

            $io->error('Expected PDOException — none thrown. Check entity or table definition.');
        } catch (\PDOException $e) {
            $io->success('INSERT correctly rejected: ' . $e->getMessage());
        }

        $io->section('2. Schema-level detection — articulate:validate');
        $io->text('Run the following to detect this at development time, before any INSERT:');
        $io->block('docker compose exec php bin/console articulate:validate', null, 'fg=yellow');
        $io->text('Expected output: warning that group_name is NOT NULL without a default and is not mapped in any entity.');

        // TODO: add a ReadOnlySettings entity (#[Entity(readOnly: true)]) that maps the same
        // table with all columns for read queries. articulate:validate should pass for read-only
        // entities even when they omit required columns, since they never INSERT.
        // Waiting for readOnly support to land in the library.

        $connection->exec('DROP TABLE IF EXISTS settings_demo');

        return Command::SUCCESS;
    }
}
