<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Services\Faker\DataProvider;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Class OfferFixtures
 *
 * Load offer initial set of data.
 */
class OfferFixtures extends BaseFixture implements DependentFixtureInterface
{
    /**
     * Get class dependencies with other entities.
     *
     * @return array
     */
    public function getDependencies() : array
    {
        return [
            PartnerFixtures::class,
            PhoneFixtures::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        // Get all partner company names sets indexes which are used for Partner Proxy objects
        $partnerProxyIndexes = $this->getAllIndexesRecursively(DataProvider::PARTNER_COMPANY_SETS['references']);
        // Get all phone models indexes which are used for Partner Proxy objects
        $phoneProxyIndexes = $this->getAllIndexesRecursively(DataProvider::PHONE_MODELS['references']);
        // Create offers (phones associated to partners)
        $this->createFixtures(Offer::class, 40, function ($i) use ($partnerProxyIndexes, $phoneProxyIndexes) {
            // Get a partner entity reference
            /** @var object|Partner $partnerProxy */
            $partnerProxy = $this->getReference(Partner::class . '_' . array_rand(array_flip($partnerProxyIndexes)));
            // Get a phone entity reference
            /** @var object|Phone $phoneProxy */
            $phoneProxy = $this->getReference(Phone::class . '_' . array_rand(array_flip($phoneProxyIndexes)));
            $offer = new Offer();
            return $offer
                ->setCreationDate(new \DateTimeImmutable(sprintf("+%d days", -$i)))
                ->setPartner($partnerProxy)
                ->setPhone($phoneProxy);
        });
        $manager->flush();
    }
}
