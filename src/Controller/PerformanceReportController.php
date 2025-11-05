<?php

namespace App\Controller;

use App\Service\ScoringService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PerformanceReportController extends AbstractController
{
    public function __construct(
        private readonly ScoringService $scoringService,
    ) {
    }

    #[Route('/performance/weekly', name: 'performance_weekly')]
    public function weeklyPerformance(Request $request): Response
    {
        $user = $this->getUser();

        $week = $request->query->getInt('week', (int) date('W'));
        $year = $request->query->getInt('year', (int) date('Y'));

        // Get combined weekly performance (tasks + goals)
        $weeklyPerformance = $this->scoringService->getWeeklyPerformanceScore($user, $week, $year);

        return $this->render('performance/weekly.html.twig', [
            'week' => $weeklyPerformance['week'],
            'year' => $weeklyPerformance['year'],
            'taskScore' => $weeklyPerformance['taskScore'],
            'goalScore' => $weeklyPerformance['goalScore'],
            'combinedScore' => $weeklyPerformance['combinedScore'],
            'score' => $weeklyPerformance['combinedScore'], // For consistency with other templates
            'taskMetrics' => $weeklyPerformance['taskMetrics'],
            'goalMetrics' => $weeklyPerformance['goalMetrics'],
            'metrics' => [
                'taskMetrics' => $weeklyPerformance['taskMetrics'],
                'goalMetrics' => $weeklyPerformance['goalMetrics'],
            ],
        ]);
    }

    #[Route('/performance/monthly', name: 'performance_monthly')]
    public function monthlyPerformance(): Response
    {
        $user = $this->getUser();
        $monthlyScore = $this->scoringService->calculateOverallScore($user, 'month');
        $monthlyMetrics = $this->scoringService->taskPerformanceScore($user, 'month');

        return $this->render('performance/monthly.html.twig', [
            'score' => $monthlyScore,
            'metrics' => $monthlyMetrics,
        ]);
    }

    #[Route('/performance/overall', name: 'performance_overall')]
    public function overallPerformance(): Response
    {
        $user = $this->getUser();
        $overallScore = $this->scoringService->calculateOverallScore($user, 'all');
        $overallMetrics = $this->scoringService->taskPerformanceScore($user, 'all');

        return $this->render('performance/overall.html.twig', [
            'score' => $overallScore,
            'metrics' => $overallMetrics,
        ]);
    }
}
