<?php

namespace App\Controller;

use App\Service\DashboardService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;

class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    #[Route('/dashboard', name: 'dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $userDashboardData = $this->dashboardService->getDashboardData($user);
        $managerDashboardData = $this->dashboardService->getManagerDashboardData($user);


        return $this->render('dashboard/index.html.twig', [
            'dashboardData' => $userDashboardData,
            'user' => $user,
        ]);
    }
}
