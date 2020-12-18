<?php

declare(strict_types=1);

namespace App\Services\API\Cache;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Class DoctrineCacheWarmer
 *
 * Manage operations for Doctrine cache on warm up.
 *
 * Please not that some similar operations can also be done with a cache clearer.
 *
 * @see https://symfony.com/doc/current/reference/dic_tags.html#kernel-cache-warmer
 */
class DoctrineCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * DoctrineCacheWarmer constructor.
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * Check whether this warmer is optional or not.
     *
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return false;
    }

    /**
     * Warm up the cache by creating particular operations.
     *
     * Please note that a custom directory is created here for Doctrine cache driver.
     *
     * {@inheritdoc}
     */
    public function warmUp($cacheDir): array
    {
        // Add a custom directory for SQLite Doctrine cache database file.
        $sqliteCacheDir = $this->parameterBag->get('api_doctrine_cache_directory_path');
        if (!\is_dir($sqliteCacheDir)) {
            mkdir($sqliteCacheDir, 0700, true);
        }
        // No file or class to return
        return [];
    }
}