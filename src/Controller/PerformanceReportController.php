<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Permissions;
use App\Service\ScoringService;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PerformanceReportController extends AbstractController
{
    public function __construct(
        private readonly ScoringService $scoringService,
        private readonly UserRepository $userRepository,
        private readonly Pdf $pdf,
    ) {
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

        $this->denyAccessUnlessGranted(Permissions::VIEW, $targetUser);

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

        $this->denyAccessUnlessGranted(Permissions::VIEW, $targetUser);


        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
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

        $this->denyAccessUnlessGranted(Permissions::VIEW, $targetUser);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
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

    #[Route('/performance/weekly/{id}/download', name: 'performance_weekly_download', requirements: ['id' => '\d+'])]
    public function downloadWeeklyPerformance(Request $request, int $id): Response
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

        $week = $request->query->getInt('week', (int) date('W'));
        $year = $request->query->getInt('year', (int) date('Y'));

        $weeklyPerformance = $this->scoringService->getWeeklyPerformanceScore($targetUser, $week, $year);

        $html = $this->renderView('performance/weekly_pdf.html.twig', [
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
            'isPdfDownload' => true,
        ]);

        return new Response(
            $this->pdf->getOutputFromHtml($html, [
                'enable-javascript' => false,
                'enable-local-file-access' => true,
            ]),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="weekly-performance-report-'.$targetUser->getId().'-W'.$week.'-'.$year.'.pdf"',
            ]
        );
    }

    #[Route('/performance/monthly/{id}/download', name: 'performance_monthly_download', requirements: ['id' => '\d+'])]
    public function downloadMonthlyPerformance(int $id): Response
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

        $monthlyScore = $this->scoringService->calculateOverallScore($targetUser, 'month');
        $monthlyMetrics = $this->scoringService->taskPerformanceScore($targetUser, 'month');

        $html = $this->renderView('performance/monthly_pdf.html.twig', [
            'targetUser' => $targetUser,
            'isOwnPerformance' => $loggedInUser->getId() === $targetUser->getId(),
            'score' => $monthlyScore,
            'metrics' => $monthlyMetrics,
            'isPdfDownload' => true,
        ]);

        $currentMonth = date('F-Y');
        $filename = sprintf(
            'monthly-performance-report-%s-%s.pdf',
            $targetUser->getId(),
            $currentMonth
        );

        return new Response(
            $this->pdf->getOutputFromHtml($html, [
                'enable-javascript' => false,
                'enable-local-file-access' => true,
            ]),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    #[Route('/performance/overall/{id}/download', name: 'performance_overall_download', requirements: ['id' => '\d+'])]
    public function downloadOverallPerformance(int $id): Response
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

        $overallScore = $this->scoringService->calculateOverallScore($targetUser, 'all');
        $overallMetrics = $this->scoringService->taskPerformanceScore($targetUser, 'all');

        $html = $this->renderView('performance/overall_pdf.html.twig', [
            'targetUser' => $targetUser,
            'isOwnPerformance' => $loggedInUser->getId() === $targetUser->getId(),
            'score' => $overallScore,
            'metrics' => $overallMetrics,
            'isPdfDownload' => true,
        ]);

        $currentDate = date('Y-m-d');
        $filename = sprintf(
            'overall-performance-report-%s-%s.pdf',
            $targetUser->getId(),
            $currentDate
        );

        return new Response(
            $this->pdf->getOutputFromHtml($html, [
                'enable-javascript' => false,
                'enable-local-file-access' => true,
            ]),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
