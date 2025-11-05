<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskService extends AbstractController
{
    public function __construct(private readonly TaskRepository $taskRepository)
    {
    }

    // all tasks of the team that manager can see
    public function getTeamTasks(User $user): array
    {
        $team = $user->getTeam();
        if (!$team) {
            throw $this->createNotFoundException('No Team found');
        }

        return $this->taskRepository->findTeamTasks($team->getId());
    }
}
