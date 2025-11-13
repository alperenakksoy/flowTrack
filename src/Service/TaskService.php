<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskService extends AbstractController
{
    public function __construct(private readonly TaskRepository $taskRepository)
    {
    }

    /**
     * @return Task[]
     */
    public function getTeamTasks(User $user): array
    {
        $team = $user->getTeam();
        if (!$team) {
            throw $this->createNotFoundException('No Team found');
        }

        $teamId = $team->getId();
        if (null === $teamId) {
            throw $this->createNotFoundException('Team ID not found');
        }

        return $this->taskRepository->findTeamTasks($teamId);
    }
}
