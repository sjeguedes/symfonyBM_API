<?php

declare(strict_types=1);

namespace App\Services\Lexik\Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

/**
 * Class JWTCreatedListener
 *
 * Customize JWT payload when it is created.
 *
 * https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/2-data-customization.md
 */
final class JWTCreatedListener
{
    /**
     * Add custom data to JWT payload.
     *
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function __invoke(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        // Username and roles are added by default: add uuid and email
        $payload['uuid'] = $event->getUser()->getUuid();
        $payload['email'] = $event->getUser()->getEmail();
        // Update payload with custom data
        $event->setData($payload);
    }
}