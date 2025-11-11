<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Permissions;
use App\Service\ScoringService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PerformanceReportController extends AbstractController
{
    public function __construct(
        private readonly ScoringService $scoringService,
        private readonly UserRepository $userRepository,
    ) {
    }

    private function canViewPerformance(User $loggedInUser, User $targetUser): bool
    {
        if ($loggedInUser->getId() === $targetUser->getId()) {
            return true;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_MANAGER')) {
            if (null !== $loggedInUser->getTeam()
                && null !== $targetUser->getTeam()
                && $loggedInUser->getTeam()->getId() === $targetUser->getTeam()->getId()) {
                return true;
            }
        }

        return false;
    }
    #[Route('/performance/weekly/{id}', name: 'performance_weekly', requirements: ['id' => '\d+'])]
    public function weeklyPerformance(Request $request, int $id): Response
    {
        /** @var User|null $loggedInUser */
        $loggedInUser = $this->getUser();

        if (!$loggedInUser) {
            return $this->redirectToRoute('app_login');
        }

        $targetUser = $this->userRepository->find($id);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }

//        $this->denyAccessUnlessGranted(Permissions::VIEW, $targetUser);

        $week = $request->query->getInt('week', (int) date('W'));
        $year = $request->query->getInt('year', (int) date('Y'));

        $weeklyPerformance = $this->scoringService->getWeeklyPerformanceScore($targetUser, $week, $year);

        return $this->render('performance/weekly.html.twig', [
            'targetUser' => $targetUser,
            'isOwnPerformance' => $loggedInUser->getId() === $targetUser->getId(),
            'week' => $weeklyPerformance['week'],
            'year' => $weeklyPerformance['year'],
            'taskScore' => $weeklyPerformance['taskScore'],
            'goalScore' => $weeklyPerformance['goalScore'],
            'combinedScore' => $weeklyPerformance['combinedScore'],
            'score' => $weeklyPerformance['combinedScore'],
            'taskMetrics' => $weeklyPerformance['taskMetrics'],
            'goalMetrics' => $weeklyPerformance['goalMetrics'],
            'metrics' => [
                'taskMetrics' => $weeklyPerformance['taskMetrics'],
                'goalMetrics' => $weeklyPerformance['goalMetrics'],
            ],
        ]);
    }

    #[Route('/performance/weekly', name: 'performance_weekly_own')]
    public function weeklyPerformanceOwn(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('performance_weekly', [
            'id' => $user->getId(),
            'week' => $request->query->getInt('week', (int) date('W')),
            'year' => $request->query->getInt('year', (int) date('Y')),
        ]);
    }

    #[Route('/performance/monthly/{id}', name: 'performance_monthly', requirements: ['id' => '\d+'])]
    public function monthlyPerformance(int $id): Response
    {
        /** @var User|null $loggedInUser */
        $loggedInUser = $this->getUser();

        if (!$loggedInUser) {
            return $this->redirectToRoute('app_login');
        }

        $targetUser = $this->userRepository->find($id);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }

        if (!$this->canViewPerformance($loggedInUser, $targetUser)) {
            throw $this->createAccessDeniedException('You cannot view this user\'s performance');
        }

        $monthlyScore = $this->scoringService->calculateOverallScore($targetUser, 'month');
        $monthlyMetrics = $this->scoringService->taskPerformanceScore($targetUser, 'month');

        return $this->render('performance/monthly.html.twig', [
            'targetUser' => $targetUser,
            'isOwnPerformance' => $loggedInUser->getId() === $targetUser->getId(),
            'score' => $monthlyScore,
            'metrics' => $monthlyMetrics,
        ]);
    }
    #[Route('/performance/overall/{id}', name: 'performance_overall', requirements: ['id' => '\d+'])]
    public function overallPerformance(int $id): Response
    {
        /** @var User|null $loggedInUser */
        $loggedInUser = $this->getUser();

        if (!$loggedInUser) {
            return $this->redirectToRoute('app_login');
        }

        $targetUser = $this->userRepository->find($id);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }

        if (!$this->canViewPerformance($loggedInUser, $targetUser)) {
            throw $this->createAccessDeniedException('You cannot view this user\'s performance');
        }

        $overallScore = $this->scoringService->calculateOverallScore($targetUser, 'all');
        $overallMetrics = $this->scoringService->taskPerformanceScore($targetUser, 'all');

        return $this->render('performance/overall.html.twig', [
            'targetUser' => $targetUser,
            'isOwnPerformance' => $loggedInUser->getId() === $targetUser->getId(),
            'score' => $overallScore,
            'metrics' => $overallMetrics,
        ]);
    }
}
