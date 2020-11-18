<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Services\Faker\DataProvider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

/**
 * Class BaseFixture
 *
 * Load partner initial set of data.
 *
 * @see https://github.com/fzaninotto/Faker#user-content-faker-internals-understanding-providers
 */
abstract class BaseFixture extends Fixture
{
    /**
     * @var Faker\Generator
     */
    protected $faker;

    /**
     * @var ObjectManager;
     */
    protected $manager;

    /**
     * BaseFixture constructor.
     */
    public function __construct()
    {
        // Get a Faker instance for french data
        $this->faker = Faker\Factory::create('fr_FR');
        // Add app custom provider
        $this->faker->addProvider(new DataProvider($this->faker));
    }

    /**
     * Creates multiple entity instance with parameters.
     *
     * @param string   $className
     * @param int      $count
     * @param callable $factory
     * @param int|null $index
     *
     * @return void
     *
     * @see Doctrine\Common\DataFixtures\AbstractFixture for addReference() method
     */
    protected function createFixtures(string $className, int $count, callable $factory, int &$index = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            $entity = $factory($i);
            $this->manager->persist($entity);
            $identifier = $i + 1;
            // Manage multiple series provided by a loop
            $identifier = \is_null($index) ? $identifier : $index . $identifier;
            // Used to reference fixtures in between (inherited from AbstractFixture)
            $this->addReference($className . '_' . $identifier, $entity);
        }
    }

    /**
     * Get custom indexes for nested arrays.
     *
     * Please note that this is used for Doctrine Proxy objects references.
     *
     * @param array $elements
     *
     * @return array
     */
    protected function getAllIndexesRecursively(array $elements): array
    {
        // Store custom generated indexes
        $indexes = [];
        // Define an index for each value which is also an array
        $row = 0;
        // Define an item index for each nested array
        $i = 0;
        $recursion = function (array $elements) use (&$recursion, &$indexes, &$row, $i) {
            // Loop recursively
            foreach ($elements as $element) {
                if (\is_array($element)) {
                    $row ++;
                    // Call closure recursively
                    $recursion($element);
                } else {
                    $i ++;
                    $indexes[] = $row . $i;
                }
            }
        };
        $recursion($elements);
        return $indexes;
    }

    /**
     * Get app custom Faker provider.
     *
     * @return DataProvider|null
     *
     * @throws \Exception
     */
    public function getCustomFakerProvider(): DataProvider
    {
        $generatorProviders = $this->faker->getProviders();
        foreach ($generatorProviders as $provider) {
            if (1 !== preg_match('/^App\\\\Services\\\\Faker\\\\Provider.*/', \get_class($provider))) {
                return $provider;
            }
        }
        throw new \RuntimeException('Custom provider was not found: please check its namespace.');
    }

    /**
     * Load entity manager.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->loadData($manager);
    }

    /**
     * Method to be called in class to load fixtures.
     *
     * @param ObjectManager $manager
     *
     * @return void
     */
    abstract protected function loadData(ObjectManager $manager);
}
