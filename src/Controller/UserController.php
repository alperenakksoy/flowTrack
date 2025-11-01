<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private DashboardService $dashboardService)
    {
    }

    #[Route('/user/{id}', name: 'user_show')]
    #[IsGranted('ROLE_MANAGER')]
    public function show(User $user)
    {
        if (!$user) {
            $user = $this->getUser();
        }
        $userData = $this->dashboardService->getDashboardData($user);

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'userData' => $userData,
        ]);
    }
}
