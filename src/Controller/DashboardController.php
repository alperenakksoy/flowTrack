<?php

namespace App\Controller;

use App\Service\DashboardService;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboardService, private readonly TaskService $taskService)
    {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
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
        } else {
            return $this->render('dashboard/user.html.twig', [
                'dashboardData' => $userDashboardData,
                'user' => $user,
            ]);
        }
    }
}
