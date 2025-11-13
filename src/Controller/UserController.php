<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\Permissions;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    #[IsGranted(Permissions::VIEW, subject: 'user')]
    #[Route('/user/{id}', name: 'user_show')]
    public function show(User $user): Response
    {
        $userData = $this->dashboardService->getDashboardData($user);

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'userData' => $userData,
        ]);
    }
}
