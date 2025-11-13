<?php

namespace App\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Permissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Task>
 */
class TaskVoter extends Voter
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
        /** @var ?User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_MANAGER')) {
            return true;
        }

        /** @var ?Task $task */
        $task = $subject;

        return match ($attribute) {
            Permissions::VIEW, Permissions::EDIT => $task && ($task->getCreatedBy()?->getId() === $user->getId()
                    || $task->getAssignedTo()?->getId() === $user->getId()),

            Permissions::CREATE, Permissions::DELETE => false,

            default => false,
        };
    }
}
