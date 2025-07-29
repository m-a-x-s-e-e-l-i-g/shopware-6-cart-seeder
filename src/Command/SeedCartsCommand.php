<?php

declare(strict_types=1);

namespace MaxSeelig\CartSeeder\Command;

use MaxSeelig\CartSeeder\Service\CartSeederService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Max Seelig
 */
#[AsCommand(
    name: 'cart-seeder:seed',
    description: 'Generate fake customers and shopping carts for development purposes'
)]
final class SeedCartsCommand extends Command
{
    public function __construct(
        private readonly CartSeederService $cartSeederService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('customers', 'c', InputOption::VALUE_OPTIONAL, 'Number of customers to create', 50)
            ->addOption('carts', null, InputOption::VALUE_OPTIONAL, 'Number of carts to create', 100)
            ->addOption('min-items', null, InputOption::VALUE_OPTIONAL, 'Minimum items per cart', 1)
            ->addOption('max-items', null, InputOption::VALUE_OPTIONAL, 'Maximum items per cart', 5)
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Clean existing seeded data first');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $customerCount = (int) $input->getOption('customers');
        $cartCount = (int) $input->getOption('carts');
        $minItems = (int) $input->getOption('min-items');
        $maxItems = (int) $input->getOption('max-items');
        $clean = $input->getOption('clean');

        $io->title('Cart Seeder - Development Tool');

        if ($clean) {
            $io->section('Cleaning existing seeded data...');
            $cleaned = $this->cartSeederService->cleanSeededData();
            $io->success("Cleaned {$cleaned['customers']} customers and {$cleaned['carts']} carts");
        }

        $io->section('Generating fake data...');
        
        // Create customers
        $io->progressStart($customerCount);
        $createdCustomers = 0;
        for ($i = 0; $i < $customerCount; $i++) {
            try {
                $this->cartSeederService->createFakeCustomer();
                $createdCustomers++;
            } catch (\Exception $e) {
                $io->error("Failed to create customer: " . $e->getMessage());
            }
            $io->progressAdvance();
        }
        $io->progressFinish();
        $io->writeln("Created {$createdCustomers} fake customers");

        // Create carts
        if ($cartCount > 0 && $createdCustomers > 0) {
            $io->progressStart($cartCount);
            $createdCarts = 0;
            for ($i = 0; $i < $cartCount; $i++) {
                try {
                    $this->cartSeederService->createFakeCart($minItems, $maxItems);
                    $createdCarts++;
                } catch (\Exception $e) {
                    $io->error("Failed to create cart: " . $e->getMessage());
                }
                $io->progressAdvance();
            }
            $io->progressFinish();
            $io->writeln("Created {$createdCarts} fake carts");
        }

        $io->success("Successfully created {$createdCustomers} customers and {$createdCarts} carts!");

        return Command::SUCCESS;
    }
}
