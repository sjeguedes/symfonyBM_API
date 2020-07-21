<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Services\Faker\DataProvider;
use App\Entity\Phone;
use Doctrine\Persistence\ObjectManager;

/**
 * Class PhoneFixtures
 *
 * Load phone initial set of data.
 */
class PhoneFixtures extends BaseFixture
{
    /**
     * {@inheritDoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        // Get all existing phones models
        $data = DataProvider::PHONE_MODELS['references'];
        // Use a row index per phone brand (will be used for Proxy objects references)
        $index = 1;
        // Create phone type Phones
        foreach ($data as $phoneBrandIndex => $phoneModelsIndexes) {
            $this->createFixtures(Phone::class, \count($phoneModelsIndexes), function ($i) use ($phoneBrandIndex, $phoneModelsIndexes) {
                // Get phone brand (which at this time a simple digit or number) for the current model
                $phoneBrand = DataProvider::PHONE_BRANDS['references'][0][$phoneBrandIndex];
                // Create a phone brand with its index and a common label
                $phoneBrand = DataProvider::PHONE_BRANDS['label']. ' ' .$phoneBrand;
                // Create a phone model with its index / storage and a common label
                $phoneModel = DataProvider::PHONE_MODELS['label'] . ' ' . $phoneModelsIndexes[$i];
                // Get a custom random color
                $phoneColor = $this->getCustomFakerProvider()->customPhoneColor();
                // Get a custom random description
                $phoneDescription = $this->getCustomFakerProvider()->customPhoneDescription();
                // Get a corresponding phone price
                $phonePrice = $this->getCustomFakerProvider()->customPhonePrice($phoneBrandIndex, $i);
                // Get phone type depending on price
                $phoneType = $this->getCustomFakerProvider()->customPhoneType($phonePrice);
                // Get phone storage depending on phone type
                $phoneStorage = $this->getCustomFakerProvider()->customPhoneStorage($phoneType);
                // Adjust phone model with phone storage
                $phoneModel = $phoneModel  . ' ' . $phoneStorage;
                $Phone = new Phone();
                return $Phone
                    ->setCreationDate(new \DateTimeImmutable(sprintf("+%d days", -$i)))
                    ->setUpdateDate(new \DateTimeImmutable(sprintf("+%d days", -$i)))
                    ->setType($phoneType)
                    ->setBrand($phoneBrand)
                    ->setModel($phoneModel)
                    ->setColor($phoneColor)
                    ->setDescription($phoneDescription)
                    ->setPrice($phonePrice);
            }, $index);
            // Manage multiple series
            $index ++;
        }
        $manager->flush();
    }
}
