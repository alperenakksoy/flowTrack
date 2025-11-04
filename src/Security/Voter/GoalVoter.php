<?php

namespace App\Security\Voter;

use App\Entity\Goal;
use App\Entity\User;
use App\Security\Permissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GoalVoter extends Voter
{
    private const array SUPPORTED_ATTRIBUTES = [
        Permissions::LIST,
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
        if (!in_array($attribute, self::SUPPORTED_ATTRIBUTES)) {
            return false;
        }

        if (!$subject instanceof Goal && Goal::class !== $subject) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var ?User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            Permissions::LIST, Permissions::VIEW => true,
            Permissions::CREATE => $this->security->isGranted('ROLE_MANAGER'),
            Permissions::EDIT => $subject instanceof Goal && ($subject->getCreatedBy()?->getId() === $user->getId()
                    || $this->security->isGranted('ROLE_MANAGER')),
            Permissions::DELETE => $subject instanceof Goal && $this->security->isGranted('ROLE_MANAGER'),
            default => false,
        };
    }
}
