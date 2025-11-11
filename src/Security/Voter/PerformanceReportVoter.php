<?php

namespace App\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Permissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PerformanceReportVoter extends Voter
{
    private const array SUPPORTED_ATTRIBUTES = [
        Permissions::VIEW,
        Permissions::CREATE,
        Permissions::EDIT,
        Permissions::DELETE,
    ];

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, self::SUPPORTED_ATTRIBUTES, true)) {
            return false;
        }

        if (Permissions::CREATE === $attribute) {
            return Task::class === $subject || $subject instanceof Task;
        }

        return $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $loggedInUser = $token->getUser();

        if (!$loggedInUser instanceof User) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        if (Permissions::VIEW !== $attribute) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($loggedInUser->getId() === $targetUser->getId()) {
            return true;
        }

        if ($this->security->isGranted('ROLE_MANAGER')
            && null !== $loggedInUser->getTeam()
            && null !== $targetUser->getTeam()
            && $loggedInUser->getTeam()->getId() === $targetUser->getTeam()->getId()
        ) {
            return true;
        }

        return false;
    }
}
