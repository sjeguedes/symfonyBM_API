<?php

declare(strict_types=1);

namespace App\Services\API\Security;

use App\Entity\Client;
use App\Entity\Partner;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ClientVoter
 *
 * Manage permissions against particular authenticated Partner actions on API Client entities.
 */
final class ClientVoter extends Voter
{
    /**
     * Define permissions labels.
     */
    const CAN_DELETE = 'can_delete';
    const CAN_VIEW = 'can_view';

    /**
     * Define all permissions to check.
     */
    const PERMISSIONS = [
        self::CAN_DELETE,
        self::CAN_VIEW
    ];

    /**
     * @var AuthorizationCheckerInterface
     */
    private $securityChecker;

    /**
     * ClientVoter constructor.
     *
     * @param AuthorizationCheckerInterface $securityChecker
     */
    public function __construct(AuthorizationCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one supported, don't imply this voter.
        if (!\in_array($attribute, self::PERMISSIONS)) {
            return false;
        }
        // Only vote on "Client" instance
        if (!$subject instanceof Client) {
            return false;
        }
        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Partner|UserInterface $authenticatedPartner */
        $authenticatedPartner = $token->getUser();
        // Check if the user is fully authenticated!
        if (!$authenticatedPartner instanceof UserInterface) {
            return false;
        }
        // Get client
        /** @var Client $client */
        $client = $subject;
        // Check permissions
        switch ($attribute) {
            case self::CAN_DELETE:
            case self::CAN_VIEW:
                return $this->isAllowedToDo($client, $authenticatedPartner);
        }
        throw new \LogicException('Unknown Partner permission definition');
    }

    /**
     * Check if an authenticated partner can delete or view a Client resource.
     *
     * @param Client                $client
     * @param UserInterface|Partner $authenticatedPartner
     *
     * @return bool
     */
    private function isAllowedToDo(Client $client, UserInterface $authenticatedPartner): bool
    {
        // Check simple partner
        if (!$this->securityChecker->isGranted(Partner::API_ADMIN_ROLE)) {
            // Check if client associated partner and authenticated partner are not the same!
            $authenticatedPartnerUuid = $authenticatedPartner->getUuid();
            $clientPartnerUuid = $client->getPartner()->getUuid();
            if ($clientPartnerUuid->toString() !== $authenticatedPartnerUuid->toString()) {
                // Will return a custom error response managed thanks to kernel exception listener
                return false;
            }
        }
        // Authenticated partner is allowed to perform action.
        return true;
    }
}