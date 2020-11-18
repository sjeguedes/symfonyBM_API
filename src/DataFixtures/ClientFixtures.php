<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Services\Faker\DataProvider;
use App\Entity\Client;
use App\Entity\Partner;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Class ClientFixtures
 *
 * Load client initial set of data.
 */
class ClientFixtures extends BaseFixture implements DependentFixtureInterface
{
    /**
     * Get class dependencies with other entities.
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            PartnerFixtures::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        // Get all partner company sets indexes which are used for Partner Proxy objects
        $partnerProxyIndexes = $this->getAllIndexesRecursively(DataProvider::PARTNER_COMPANY_SETS['references']);
        // Create client (a client associated to a partner which is the API consumer)
        $this->createFixtures(Client::class, 40, function ($i) use ($partnerProxyIndexes) {
            // Get a client type randomly
            $clientType = array_rand(array_flip(Client::CLIENT_TYPES));
            // Get individual or professional name
            $clientName = Client::INDIVIDUAL_TYPE === $clientType
                ? $this->faker->lastName
                : $this->faker->lastName . ' ' . array_rand(array_flip(DataProvider::COMPANY_STATUS));
            // Get a partner entity reference randomly
            /** @var object|Partner $partnerProxy */
            $partnerProxy = $this->getReference(Partner::class . '_' . array_rand(array_flip($partnerProxyIndexes)));
            // Get corresponding partner email created with random company department
            $unique = '-' . substr(md5(uniqid()), 0, 4);
            $clientEmail = $this->getCustomFakerProvider()->customSanitizedString($clientName) . $unique . '@' . $this->faker->freeEmailDomain;
            $client = new Client();
            return $client
                ->setCreationDate(new \DateTimeImmutable(sprintf("+%d days", -$i)))
                ->setUpdateDate(new \DateTimeImmutable(sprintf("+%d days", -$i)))
                ->setType($clientType)
                ->setName($clientName)
                ->setEmail($clientEmail)
                ->setPartner($partnerProxy);
        });
        $manager->flush();
    }
}
