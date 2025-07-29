<?php

declare(strict_types=1);

namespace MaxSeelig\CartSeeder\Service;

use Doctrine\DBAL\Connection;
use Faker\Factory;
use Faker\Generator;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\CachedSalesChannelContextFactory;

/**
 * @author Max Seelig
 */
final class CartSeederService
{
    private Generator $faker;
    private Context $context;
    private array $salesChannelIds = [];
    private array $productIds = [];
    private array $customerGroupIds = [];
    private array $paymentMethodIds = [];
    private array $shippingMethodIds = [];
    private array $countryIds = [];
    private array $seededCustomerIds = [];

    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $customerGroupRepository,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EntityRepository $shippingMethodRepository,
        private readonly EntityRepository $countryRepository,
        private readonly CartService $cartService,
        private readonly CachedSalesChannelContextFactory $salesChannelContextFactory,
        private readonly Connection $connection
    ) {
        $this->faker = Factory::create();
        $this->context = new Context(new SystemSource());
        $this->loadRequiredData();
    }

    public function createFakeCustomer(): CustomerEntity
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        
        $customerData = [
            'id' => $customerId,
            'customerNumber' => 'SEED-' . $this->faker->unique()->numerify('######'),
            'salutationId' => $this->getRandomSalutationId(),
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'email' => $this->faker->unique()->email,
            'password' => 'password123', // Simple password for dev
            'groupId' => $this->faker->randomElement($this->customerGroupIds),
            'salesChannelId' => $this->faker->randomElement($this->salesChannelIds),
            'defaultPaymentMethodId' => $this->faker->randomElement($this->paymentMethodIds),
            'defaultShippingAddressId' => $addressId,
            'defaultBillingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'salutationId' => $this->getRandomSalutationId(),
                    'firstName' => $this->faker->firstName,
                    'lastName' => $this->faker->lastName,
                    'street' => $this->faker->streetAddress,
                    'zipcode' => $this->faker->postcode,
                    'city' => $this->faker->city,
                    'countryId' => $this->faker->randomElement($this->countryIds),
                ]
            ]
        ];

        $this->customerRepository->create([$customerData], $this->context);
        $this->seededCustomerIds[] = $customerId;

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search(
            new Criteria([$customerId]),
            $this->context
        )->first();

        return $customer;
    }

    public function createFakeCart(int $minItems = 1, int $maxItems = 5): void
    {
        if (empty($this->seededCustomerIds)) {
            throw new \RuntimeException('No customers available. Create customers first.');
        }

        $customerId = $this->faker->randomElement($this->seededCustomerIds);
        $salesChannelId = $this->faker->randomElement($this->salesChannelIds);
        
        // Create sales channel context
        $salesChannelContext = $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $salesChannelId,
            [
                'customerId' => $customerId
            ]
        );

        // Create new cart
        $cart = $this->cartService->createNew($salesChannelContext->getToken());
        
        // Add random products
        $itemCount = $this->faker->numberBetween($minItems, $maxItems);
        for ($i = 0; $i < $itemCount; $i++) {
            $productId = $this->faker->randomElement($this->productIds);
            $quantity = $this->faker->numberBetween(1, 3);
            
            $lineItem = new LineItem(
                Uuid::randomHex(),
                LineItem::PRODUCT_LINE_ITEM_TYPE,
                $productId,
                $quantity
            );
            
            $cart = $this->cartService->add($cart, $lineItem, $salesChannelContext);
        }

        // Make cart look older (random time in the past 7 days)
        $this->makeCartOlder($cart->getToken(), $this->faker->numberBetween(1, 7 * 24 * 60 * 60));
    }

    public function cleanSeededData(): array
    {
        $customerCount = 0;
        $cartCount = 0;

        // Clean customers with SEED- prefix
        $stmt = $this->connection->prepare('
            DELETE FROM customer 
            WHERE customer_number LIKE "SEED-%"
        ');
        $customerCount = $stmt->executeStatement();

        // Clean old carts (optional - you might want to keep some)
        $stmt = $this->connection->prepare('
            DELETE FROM cart 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ');
        $cartCount = $stmt->executeStatement();

        $this->seededCustomerIds = [];

        return [
            'customers' => $customerCount,
            'carts' => $cartCount
        ];
    }

    private function loadRequiredData(): void
    {
        // Load sales channels
        $salesChannels = $this->salesChannelRepository->search(new Criteria(), $this->context);
        $this->salesChannelIds = $salesChannels->getIds();

        // Load products (limit to avoid memory issues)
        $criteria = new Criteria();
        $criteria->setLimit(1000);
        $products = $this->productRepository->search($criteria, $this->context);
        $this->productIds = $products->getIds();

        // Load customer groups
        $customerGroups = $this->customerGroupRepository->search(new Criteria(), $this->context);
        $this->customerGroupIds = $customerGroups->getIds();

        // Load payment methods
        $paymentMethods = $this->paymentMethodRepository->search(new Criteria(), $this->context);
        $this->paymentMethodIds = $paymentMethods->getIds();

        // Load shipping methods
        $shippingMethods = $this->shippingMethodRepository->search(new Criteria(), $this->context);
        $this->shippingMethodIds = $shippingMethods->getIds();

        // Load countries
        $countries = $this->countryRepository->search(new Criteria(), $this->context);
        $this->countryIds = $countries->getIds();
    }

    private function getRandomSalutationId(): string
    {
        // Try to get a random salutation, fallback to hardcoded ID
        try {
            $stmt = $this->connection->prepare('SELECT LOWER(HEX(id)) FROM salutation LIMIT 1');
            $result = $stmt->executeQuery();
            $salutationId = $result->fetchOne();
            
            if ($salutationId) {
                return $salutationId;
            }
        } catch (\Exception $e) {
            // Fallback to hardcoded salutation ID
        }
        
        // Default salutation ID (this should exist in most Shopware installations)
        return 'ed643807ce9143658a05f3c9c7c5b8c1';
    }

    private function makeCartOlder(string $token, int $secondsAgo): void
    {
        $olderDate = new \DateTime();
        $olderDate->sub(new \DateInterval('PT' . $secondsAgo . 'S'));

        $this->connection->executeStatement(
            'UPDATE cart SET created_at = :created_at WHERE token = :token',
            [
                'created_at' => $olderDate->format('Y-m-d H:i:s.v'),
                'token' => $token
            ]
        );
    }
}
