<?php

namespace App\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Permissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TaskVoter extends Voter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Task && Task::class !== $subject) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var ?User $user */
        $user = $token->getUser(); // required to call user with token in security

        return match ($attribute) {
            Permissions::LIST, Permissions::VIEW => true,
            Permissions::CREATE => null !== $user,
            Permissions::EDIT => $subject instanceof Task && ($subject->getCreatedBy()->getId() === $user?->getId()
                    || $this->security->isGranted('ROLE_MANAGER')),
            Permissions::DELETE => $subject instanceof Task && $this->security->isGranted('ROLE_MANAGER'),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }
}
