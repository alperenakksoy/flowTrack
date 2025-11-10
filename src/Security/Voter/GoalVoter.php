<?php

namespace App\Security\Voter;

use App\Entity\Goal;
use App\Entity\User;
use App\Security\Permissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GoalVoter extends Voter
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
        if (!in_array($attribute, self::SUPPORTED_ATTRIBUTES)) {
            return false;
        }
        if (!$subject instanceof Goal) {
            return Goal::class === $subject && Permissions::CREATE === $attribute;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var ?User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_MANAGER')) {
            return true;
        }

        /** @var ?Goal $goal */
        $goal = $subject instanceof Goal ? $subject : null;

        return match ($attribute) {
            Permissions::VIEW => $goal && ($goal->getEmployee()?->getId() === $user->getId()
                    || $goal->getCreatedBy()?->getId() === $user->getId()),

            Permissions::CREATE, Permissions::EDIT, Permissions::DELETE => false,

            default => false,
        };
    }
}
