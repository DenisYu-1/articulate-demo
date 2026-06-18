<?php

namespace App\Feature\CustomerAccounts\Command;

use App\Feature\CustomerAccounts\Entity\Customer;
use App\Feature\CustomerAccounts\Entity\CustomerSummary;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:customers:browse', description: 'Customer repository and pagination demo')]
final class CustomersBrowseCommand extends Command
{
    use CustomerCommandSupport;

    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $suffix = bin2hex(random_bytes(4));
        $domain = "browse-{$suffix}.test";

        $this->enableSoftDeleteFilter();

        $io->section('Customer browsing');

        $ids = $this->seedCustomers($domain);
        $repo = $this->customerRepository();

        $summaries = $repo->findSummariesByEmailDomain($domain, 2, 1);
        $io->text('CustomerSummary offset page: ' . implode(', ', array_map(
            fn (CustomerSummary $summary): string => $summary->email,
            $summaries,
        )));

        $cursorPage = $repo->findSummaryCursorByEmailDomain($domain, null, 2);
        $cursorItems = $cursorPage->getItems();
        $io->text(sprintf(
            'CustomerSummary cursor page: %d items, next cursor=%s',
            count($cursorItems),
            $cursorPage->getNextCursor() === null ? 'no' : 'yes',
        ));

        $domainCustomers = $repo->findByEmailDomain($domain);
        $io->text('findByEmailDomain(): ' . count($domainCustomers));

        $registered = $repo->findRegisteredBetween(
            new \DateTime('-2 days'),
            new \DateTime('+1 day'),
        );
        $io->text('findRegisteredBetween(): ' . count($registered) . ' visible customers');

        $active = $repo->findActiveByEmailDomain($domain);
        $io->text('findActiveByEmailDomain(): ' . count($active));

        $withAddress = $repo->findWithAddress($ids[0]);
        $io->text(sprintf(
            'findWithAddress(): %s',
            $withAddress instanceof Customer && $withAddress->address !== null
                ? $withAddress->address->city
                : 'not loaded',
        ));

        $summaryRepository = $this->entityManager->getRepository(CustomerSummary::class);
        $io->text('CustomerSummary repository class: ' . basename(str_replace('\\', '/', $summaryRepository::class)));

        $io->success('Customer browse demo completed');

        return Command::SUCCESS;
    }

    /**
     * @return int[]
     */
    private function seedCustomers(string $domain): array
    {
        $ids = [];
        $base = new \DateTimeImmutable('-6 hours');
        $names = ['Alice Browse', 'Bob Browse', 'Carol Browse', 'Dina Browse', 'Evan Browse'];

        foreach ($names as $index => $name) {
            $customer = $this->createCustomerWithAddress(
                $name,
                sprintf('browse-%d@%s', $index, $domain),
                "Browse {$index}",
            );

            $this->setRegisteredAt(
                $customer,
                $base->modify("+{$index} hours")->format('Y-m-d H:i:s'),
            );

            $ids[] = $customer->id;
            $this->entityManager->clear();
        }

        return $ids;
    }
}
