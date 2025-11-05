<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\GoalRepository;
use App\Repository\TaskRepository;
use App\Repository\TeamRepository;

class ScoringService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly GoalRepository $goalRepository,
        private readonly TeamRepository $teamRepository,
        private readonly TaskService $taskService,
        private readonly DashboardService $dashboardService, )
    {
    }

    public function taskPerformanceScore(User $employee): array
    {
        $dashboardData = $this->dashboardService->getDashboardData($employee);
        $taskData = $dashboardData['taskStatistics']; // count (total, complete, on_going, canceled, task completion rate,

        $userCompleteTasks = $this->taskRepository->getUserCompletedTasks($employee); // tasks that employee completed in an array

        $taskCompletedOnTime = $this->taskRepository->getTaskCompletedOnTime($employee); // Tasks Completed On Time

        $countTaskCompletedOnTime = count($taskCompletedOnTime);

        if (count($taskData['completedTasks']) > 0) {
            $onTimeCompletionRate = (count($taskCompletedOnTime) / $taskData['completedTasks'] * 100); // On-Time Completion Rate (%)
        } else {
            $onTimeCompletionRate = 0;
        }
        // Time metric
        $avgCompletionTime = $this->taskRepository->getAverageCompletionTime($employee);
        $avgDelayTime = $this->taskRepository->getAverageDelayHours($employee);

        // Priority-based metrics
        $successRateByPriority = $this->taskRepository->getSuccessRateByPriority($employee);
        $highPrioritySuccessRate = $this->taskRepository->getHighPrioritySuccessRate($employee, 1);
        $taskStatusByPriority = $this->taskRepository->getTaskStatusByPriority($employee);

        // Individual priority completion rates
        $priorityCompletionRates = [
            1 => $this->taskRepository->getCompletionRateByPriority($employee, 1),
            2 => $this->taskRepository->getCompletionRateByPriority($employee, 2),
            3 => $this->taskRepository->getCompletionRateByPriority($employee, 3),
        ];

        return [
            'basicMetrics' => [
                'totalTasks' => $taskData['totalTasks'],
                'completedTasks' => $taskData['completedTasks'],
                'taskCompletionRate' => $taskData['taskCompletionRate'],
                'onTimeCompletionRate' => round($onTimeCompletionRate, 2),
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

    // Calculate an overall performance score (0-100).
    public function calculateOverallScore(User $employee): float
    {
        $metrics = $this->taskPerformanceScore($employee);

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

        // If delay exists, reduce the score proportionally
        $avgDelay = $metrics['timeMetrics']['averageDelayHours'];
        if (null === $avgDelay || $avgDelay <= 0) {
            $scores['timeEfficiency'] = 100;
        } else {
            $scores['timeEfficiency'] = max(0, 100 - ($avgDelay * 2));
        }
        // total score
        $totalScore = 0;
        foreach ($weights as $key => $weight) {
            $totalScore += $scores[$key] * $weight;
        }

        return round($totalScore, 2);
    }
}
