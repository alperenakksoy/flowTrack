<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\GoalRepository;
use App\Repository\TaskRepository;

class ScoringService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly GoalRepository $goalRepository,
        private readonly DashboardService $dashboardService,
    ) {
    }

    /**
     * Get a performance score for a specific time period.
     *
     * @param string   $period 'week'|'month'|'quarter'|'all'
     * @param int|null $week   Week number (for weekly)
     * @param int|null $year   Year
     */
    public function taskPerformanceScore(
        User $employee,
        string $period = 'all',
        ?int $week = null,
        ?int $year = null,
    ): array {
        // Set date range based on period
        $dateRange = $this->getDateRange($period, $week, $year);

        // Get time-filtered metrics
        $taskCompletedOnTime = $this->taskRepository->getTaskCompletedOnTime(
            $employee,
            $dateRange['start'],
            $dateRange['end']
        );

        $countTaskCompletedOnTime = count($taskCompletedOnTime);

        // Get completed tasks in period
        $completedTasksInPeriod = $this->taskRepository->getUserCompletedTasksInPeriod(
            $employee,
            $dateRange['start'],
            $dateRange['end']
        );

        $completedCount = count($completedTasksInPeriod);

        // Get open/in-progress tasks in period
        $openTasksInPeriod = $this->taskRepository->getOpenTasksInPeriod(
            $employee,
            $dateRange['start'],
            $dateRange['end']
        );
        $countOpenTasksInPeriod = count($openTasksInPeriod);

        // Total tasks in period
        $totalTasksInPeriod = $completedCount + $countOpenTasksInPeriod;

        // Calculate rates
        $taskCompletionRate = $totalTasksInPeriod > 0
            ? round(($completedCount / $totalTasksInPeriod) * 100, 2)
            : 0;

        $onTimeCompletionRate = $completedCount > 0
            ? round(($countTaskCompletedOnTime / $completedCount) * 100, 2)
            : 0;

        // Time metrics for period
        $avgCompletionTime = $this->taskRepository->getAverageCompletionTime(
            $employee,
            $dateRange['start'],
            $dateRange['end']
        );

        $avgDelayTime = $this->taskRepository->getAverageDelayHours(
            $employee,
            $dateRange['start'],
            $dateRange['end']
        );

        // Priority metrics for period
        $successRateByPriority = $this->taskRepository->getSuccessRateByPriority(
            $employee,
            $dateRange['start'],
            $dateRange['end']
        );

        $highPrioritySuccessRate = $this->taskRepository->getHighPrioritySuccessRate(
            $employee,
            1,
            $dateRange['start'],
            $dateRange['end']
        );

        $taskStatusByPriority = $this->taskRepository->getTaskStatusByPriority(
            $employee,
            $dateRange['start'],
            $dateRange['end']
        );

        $priorityCompletionRates = [
            1 => $this->taskRepository->getCompletionRateByPriority(
                $employee,
                1,
                $dateRange['start'],
                $dateRange['end']
            ),
            2 => $this->taskRepository->getCompletionRateByPriority(
                $employee,
                2,
                $dateRange['start'],
                $dateRange['end']
            ),
            3 => $this->taskRepository->getCompletionRateByPriority(
                $employee,
                3,
                $dateRange['start'],
                $dateRange['end']
            ),
        ];

        return [
            'period' => [
                'type' => $period,
                'week' => $week,
                'year' => $year,
                'startDate' => $dateRange['start']?->format('Y-m-d'),
                'endDate' => $dateRange['end']?->format('Y-m-d'),
            ],
            'basicMetrics' => [
                'totalTasksInPeriod' => $totalTasksInPeriod,
                'completedTasks' => $completedCount,
                'openTasks' => $countOpenTasksInPeriod,
                'taskCompletionRate' => $taskCompletionRate,
                'onTimeCompletionRate' => $onTimeCompletionRate,
                'tasksCompletedOnTime' => $countTaskCompletedOnTime,
            ],
            'timeMetrics' => [
                'averageCompletionTime' => $avgCompletionTime,
                'averageDelayHours' => $avgDelayTime,
            ],
            'priorityMetrics' => [
                'successRateByPriority' => $successRateByPriority,
                'highPrioritySuccessRate' => $highPrioritySuccessRate,
                'statusByPriority' => $taskStatusByPriority,
                'completionRateByPriority' => $priorityCompletionRates,
            ],
        ];
    }

    /**
     * Calculate date range based on period type.
     */
    private function getDateRange(string $period, ?int $week, ?int $year): array
    {
        $now = new \DateTimeImmutable();
        $currentYear = $year ?? (int) $now->format('Y');

        return match ($period) {
            'week' => $this->getWeekRange($week ?? (int) $now->format('W'), $currentYear),
            'month' => $this->getMonthRange($now),
            'quarter' => $this->getQuarterRange($now),
            'year' => $this->getYearRange($currentYear),
            default => ['start' => null, 'end' => null], // All time
        };
    }

    private function getWeekRange(int $week, int $year): array
    {
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $start = \DateTimeImmutable::createFromMutable($dto->modify('monday this week 00:00:00'));
        $end = \DateTimeImmutable::createFromMutable($dto->modify('sunday this week 23:59:59'));

        return ['start' => $start, 'end' => $end];
    }

    private function getMonthRange(\DateTimeImmutable $date): array
    {
        $start = $date->modify('first day of this month 00:00:00');
        $end = $date->modify('last day of this month 23:59:59');

        return ['start' => $start, 'end' => $end];
    }

    private function getQuarterRange(\DateTimeImmutable $date): array
    {
        $month = (int) $date->format('n');
        $year = (int) $date->format('Y');

        $quarter = (int) ceil($month / 3);
        $startMonth = (($quarter - 1) * 3) + 1;
        $endMonth = $startMonth + 2;

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$year-$startMonth-01 00:00:00");
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', "$year-$endMonth-01")
            ->modify('last day of this month 23:59:59');

        return ['start' => $start, 'end' => $end];
    }

    private function getYearRange(int $year): array
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$year-01-01 00:00:00");
        $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$year-12-31 23:59:59");

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Calculate overall score with period support.
     */
    public function calculateOverallScore(
        User $employee,
        string $period = 'all',
        ?int $week = null,
        ?int $year = null,
    ): float {
        $metrics = $this->taskPerformanceScore($employee, $period, $week, $year);

        $weights = [
            'completionRate' => 0.30,
            'onTimeRate' => 0.25,
            'highPrioritySuccess' => 0.25,
            'timeEfficiency' => 0.20,
        ];

        $scores = [];
        $scores['completionRate'] = $metrics['basicMetrics']['taskCompletionRate'];
        $scores['onTimeRate'] = $metrics['basicMetrics']['onTimeCompletionRate'];
        $scores['highPrioritySuccess'] = $metrics['priorityMetrics']['highPrioritySuccessRate'] ?? 0;

        $avgDelay = $metrics['timeMetrics']['averageDelayHours'];
        if (null === $avgDelay || $avgDelay <= 0) {
            $scores['timeEfficiency'] = 100;
        } else {
            $scores['timeEfficiency'] = max(0, 100 - ($avgDelay * 2));
        }

        $totalScore = 0;
        foreach ($weights as $key => $weight) {
            $totalScore += $scores[$key] * $weight;
        }

        return round($totalScore, 2);
    }

    /**
     * Get COMBINED performance score (Tasks + Goals)
     * This is especially important for weekly evaluation.
     */
    public function getWeeklyPerformanceScore(User $employee, ?int $week = null, ?int $year = null): array
    {
        $currentWeek = $week ?? (int) date('W');
        $currentYear = $year ?? (int) date('Y');

        // Task performance for the week
        $taskScore = $this->calculateOverallScore($employee, 'week', $currentWeek, $currentYear);
        $taskMetrics = $this->taskPerformanceScore($employee, 'week', $currentWeek, $currentYear);

        // Goal performance for the week
        $goalScore = $this->calculateGoalScore($employee, $currentWeek, $currentYear);
        $goalMetrics = $this->getGoalMetrics($employee, $currentWeek, $currentYear);

        // Combined score (50% tasks, 50% goals)
        $combinedScore = ($taskScore * 0.5) + ($goalScore * 0.5);

        return [
            'week' => $currentWeek,
            'year' => $currentYear,
            'taskScore' => $taskScore,
            'goalScore' => $goalScore,
            'combinedScore' => round($combinedScore, 2),
            'taskMetrics' => $taskMetrics,
            'goalMetrics' => $goalMetrics,
        ];
    }

    /**
     * Calculate goal performance score.
     */
    private function calculateGoalScore(User $employee, int $week, int $year): float
    {
        $goals = $this->goalRepository->findBy([
            'employee' => $employee,
            'week' => $week,
            'year' => $year,
        ]);

        if (0 === count($goals)) {
            return 0; // No goals set
        }

        $totalProgress = 0;
        $completedGoals = 0;

        foreach ($goals as $goal) {
            if ($goal->getTargetValue() > 0) {
                $progressPercentage = ($goal->getProgress() / $goal->getTargetValue()) * 100;
                $totalProgress += min(100, $progressPercentage); // Cap at 100%
            }

            if ('closed' === $goal->getStatus()) {
                ++$completedGoals;
            }
        }

        $avgProgress = $totalProgress / count($goals);
        $completionRate = (count($goals) > 0) ? ($completedGoals / count($goals)) * 100 : 0;

        // 70% weight on average progress, 30% on completion rate
        return ($avgProgress * 0.7) + ($completionRate * 0.3);
    }

    /**
     * Get detailed goal metrics.
     */
    private function getGoalMetrics(User $employee, int $week, int $year): array
    {
        $goals = $this->goalRepository->findBy([
            'employee' => $employee,
            'week' => $week,
            'year' => $year,
        ]);

        $totalGoals = count($goals);
        $completedGoals = 0;
        $inProgressGoals = 0;
        $totalProgress = 0;

        foreach ($goals as $goal) {
            if ('closed' === $goal->getStatus()) {
                ++$completedGoals;
            } elseif ('in_progress' === $goal->getStatus()) {
                ++$inProgressGoals;
            }

            if ($goal->getTargetValue() > 0) {
                $progressPercentage = ($goal->getProgress() / $goal->getTargetValue()) * 100;
                $totalProgress += min(100, $progressPercentage);
            }
        }

        return [
            'totalGoals' => $totalGoals,
            'completedGoals' => $completedGoals,
            'inProgressGoals' => $inProgressGoals,
            'averageProgress' => $totalGoals > 0 ? round($totalProgress / $totalGoals, 2) : 0,
            'completionRate' => $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 2) : 0,
        ];
    }
}
