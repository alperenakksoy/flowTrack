<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\Permissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User>
 */
class PerformanceReportVoter extends Voter
{
    private const array SUPPORTED_ATTRIBUTES = [
        Permissions::VIEW,
    ];

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES, true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User|null $loggedInUser */
        $loggedInUser = $token->getUser();

        if (!$loggedInUser instanceof User) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MANAGER')) {
            return true;
        }

        if ($loggedInUser->getId() === $targetUser->getId()) {
            return true;
        }

        return false;
    }
}
