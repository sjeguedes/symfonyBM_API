<?php

declare(strict_types=1);

namespace App\Services\API\Security;

use App\Entity\Partner;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class PartnerVoter
 *
 * Manage permissions against particular authenticated Partner actions on API Partner entities.
 */
class PartnerVoter extends Voter
{
    /**
     * Define permissions labels.
     */
    const CAN_VIEW = 'can_view';

    /**
     * Define all permissions to check.
     */
    const PERMISSIONS = [
        self::CAN_VIEW
    ];

    /**
     * @var AuthorizationCheckerInterface
     */
    private $securityChecker;

    /**
     * PartnerVoter constructor.
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
        // Only vote on "Partner" instance
        if (!$subject instanceof Partner) {
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
        // Get Partner
        /** @var Partner $partner */
        $partner = $subject;
        // Check permissions
        switch ($attribute) {
            case self::CAN_VIEW:
                return $this->isAllowedToDo($partner, $authenticatedPartner);
        }
        throw new \LogicException('Unknown Partner permission definition');
    }

    /**
     * Check if an authenticated partner can view requested partner information.
     *
     * @param Partner               $partner
     * @param UserInterface|Partner $authenticatedPartner
     *
     * @return bool
     */
    private function isAllowedToDo(Partner $partner, UserInterface $authenticatedPartner): bool
    {
        // Check simple partner
        if (!$this->securityChecker->isGranted(Partner::API_ADMIN_ROLE)) {
            // Check if requested partner and authenticated partner are not the same!
            $requestedPartnerUuid = $partner->getUuid()->toString();
            $authenticatedPartnerUuid = $authenticatedPartner->getUuid()->toString();
            if ($requestedPartnerUuid !== $authenticatedPartnerUuid) {
                // Will return a custom error response managed thanks to kernel exception listener
                return false;
            }
        }
        // Authenticated partner is allowed to perform action.
        return true;
    }
}