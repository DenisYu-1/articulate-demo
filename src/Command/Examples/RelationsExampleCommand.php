<?php

namespace App\Command\Examples;

use App\Entity\Cart;
use App\Entity\Phone;
use App\Entity\User;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:relations', description: 'Relations example')]
final class RelationsExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = 'relations-' . uniqid() . '@example.com';

        $user = new User();
        $user->name = 'Relations Demo';
        $user->email = $email;
        $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
        $user->status = 'active';

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $phone1 = new Phone();
        $phone1->number = '+1234567890';
        $phone1->label = 'mobile';
        $phone1->user = $user;

        $phone2 = new Phone();
        $phone2->number = '+0987654321';
        $phone2->label = 'work';
        $phone2->user = $user;

        $cart = new Cart();
        $cart->total = 0.0;
        $cart->user = $user;

        $this->entityManager->persist($phone1);
        $this->entityManager->persist($phone2);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $io->success("Created user with phones and cart (OneToMany, ManyToOne, OneToOne)");

        $loaded = $this->entityManager->find(User::class, $user->id);
        $this->entityManager->loadRelation($loaded, 'phones');
        $this->entityManager->loadRelation($loaded, 'cart');

        $phonesCount = $loaded->phones instanceof \Countable ? count($loaded->phones) : (is_array($loaded->phones) ? count($loaded->phones) : 0);
        $io->text("Phones: {$phonesCount}");
        $cartTotal = $loaded->cart !== null ? $loaded->cart->total : 'N/A';
        $io->text("Cart total: {$cartTotal}");

        return Command::SUCCESS;
    }
}
