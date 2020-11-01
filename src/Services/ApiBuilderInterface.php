<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Interface ApiBuilderInterface.
 *
 * Define a contract to build a particular object.
 */
interface ApiBuilderInterface
{
    /**
     * Build an expected instance.
     *
     * @return object
     */
    public function build(): object;
}