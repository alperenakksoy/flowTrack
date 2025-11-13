<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DashboardService;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboardService, private readonly TaskService $taskService)
    {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $userDashboardData = $this->dashboardService->getDashboardData($user);
        $managerDashboardData = $this->dashboardService->getManagerDashboardData($user);
        $teamTasks = $this->taskService->getTeamTasks($user);

        if (in_array('ROLE_MANAGER', $user->getRoles())) {
            return $this->render('dashboard/manager.html.twig', [
                'dashboardData' => $userDashboardData,
                'managerDashboardData' => $managerDashboardData,
                'user' => $user,
                'teamTasks' => $teamTasks,
            ]);
        }

        return $this->render('dashboard/user.html.twig', [
            'dashboardData' => $userDashboardData,
            'user' => $user,
        ]);
    }
}
