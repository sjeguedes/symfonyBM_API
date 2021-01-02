<?php

declare(strict_types=1);

namespace App\Services\Gesdinet\Event\Listener;

use Gesdinet\JWTRefreshTokenBundle\Event\RefreshEvent;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

/**
 * Class RefreshedTokenListener
 *
 * Delete all invalid refresh tokens each time a new refresh token is created
 * independently of authenticated partner.
 *
 * Please note that a CRON job with Gesdinet bundle Symfony Command would be more adapted!
 *
 * @see https://github.com/markitosgv/JWTRefreshTokenBundle#user-content-events
 * @see https://github.com/markitosgv/JWTRefreshTokenBundle/blob/master/Doctrine/RefreshTokenManager.php
 */
final class RefreshedTokenListener
{
    /**
     * @var RefreshTokenManagerInterface
     */
    private $refreshTokenManager;

    /**
     * RefreshedTokenListener constructor.
     *
     * @param RefreshTokenManagerInterface $refreshTokenManager
     */
    public function __construct(RefreshTokenManagerInterface $refreshTokenManager)
    {
        // Use Gesdinet service directly
        $this->refreshTokenManager = $refreshTokenManager;
    }

    /**
     * Delete all invalid existing refresh tokens in database when a new refresh token is created.
     *
     * @param RefreshEvent $event
     *
     * @return void
     */
    public function onTokenRefreshed(RefreshEvent $event): void
    {
        // This is a kind of way to purge database table regularly.
        $this->refreshTokenManager->revokeAllInvalid();
    }
}