<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\GoalRepository;
use App\Repository\TaskRepository;
use App\Repository\TeamRepository;

class DashboardService
{
    public function __construct(
        private readonly GoalRepository $goalRepository,
        private readonly TaskRepository $taskRepository,
        private readonly TeamRepository $teamRepository,
    ) {
    }

    public function getDashboardData(User $user): array
    {
        return [
            'taskStatistics' => $this->getTaskStatistics($user),
            'goalStatistics' => $this->getGoalStatistics($user),
            'tasks' => $this->getUserTasks($user),
            'goals' => $this->getUserGoals($user),
        ];
    }

    public function getManagerDashboardData(User $manager): array
    {
        return [
            'team' => $this->getManagerTeamData($manager),
        ];
    }

    private function getTaskStatistics(User $user): array
    {
        $taskStats = $this->taskRepository->getUserTaskStats($user);

        $taskCompletionRate = $taskStats['total'] > 0
            ? round(($taskStats['completedTasks'] / $taskStats['total']) * 100, 2)
            : 0;

        return [
            'totalTasks' => (int) $taskStats['total'],
            'openTasks' => (int) $taskStats['openTasks'],
            'onGoingTasks' => (int) $taskStats['onGoingTasks'],
            'completedTasks' => (int) $taskStats['completedTasks'],
            'cancelledTasks' => (int) $taskStats['cancelledTasks'],
            'taskCompletionRate' => $taskCompletionRate,
        ];
    }

    private function getGoalStatistics(User $user): array
    {
        $currentWeek = (int) date('W');
        $currentYear = (int) date('Y');

        $goalStats = $this->goalRepository->getStatisticsByUser($user);

        $currentWeekGoals = $this->goalRepository->findBy([
            'employee' => $user,
            'week' => $currentWeek,
            'year' => $currentYear,
        ]);

        $weeklyProgress = 0;
        $formattedWeeklyGoals = $this->formatGoals($currentWeekGoals);

        if (count($formattedWeeklyGoals) > 0) {
            $totalProgress = array_sum(array_column($formattedWeeklyGoals, 'progressPercentage'));
            $weeklyProgress = round($totalProgress / count($formattedWeeklyGoals), 1);
        }

        return [
            'totalGoals' => $goalStats['total'],
            'openGoals' => $goalStats['open'],
            'inProgressGoals' => $goalStats['in_progress'],
            'completedGoals' => $goalStats['closed'],
            'cancelledGoals' => $goalStats['cancelled'],
            'completionRate' => $goalStats['completionRate'],
            'currentWeek' => $currentWeek,
            'currentYear' => $currentYear,
            'weeklyGoals' => $this->formatGoals($currentWeekGoals),
            'AverageWeeklyProgress' => $weeklyProgress,
        ];
    }

    private function getUserTasks(User $user): array
    {
        $tasks = $this->taskRepository->findBy(
            ['assignedTo' => $user],
            ['createdAt' => 'DESC'],
            50
        );

        return $this->formatTasks($tasks);
    }

    private function formatTasks(array $tasks): array
    {
        return array_map(fn ($task) => [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'dueDate' => $task->getDueDate()?->format('Y-m-d H:i:s'),
            'createdAt' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $task->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'completedAt' => $task->getCompletedAt()?->format('Y-m-d H:i:s'),
            'createdBy' => [
                'id' => $task->getCreatedBy()?->getId(),
                'name' => $task->getCreatedBy()?->getFirstName().' '.$task->getCreatedBy()?->getLastName(),
            ],
            'assignedTo' => [
                'id' => $task->getAssignedTo()?->getId(),
                'name' => $task->getAssignedTo()?->getFirstName().' '.$task->getAssignedTo()?->getLastName(),
            ],
        ], $tasks);
    }

    private function getUserGoals(User $user): array
    {
        $goals = $this->goalRepository->findBy(
            ['employee' => $user],
            ['year' => 'DESC', 'week' => 'DESC'],
            50
        );

        return $this->formatGoals($goals);
    }

    private function formatGoals(array $goals): array
    {
        return array_map(fn ($goal) => [
            'id' => $goal->getId(),
            'description' => $goal->getDescription(),
            'progress' => $goal->getProgress(),
            'targetValue' => $goal->getTargetValue(),
            'unit' => $goal->getUnit(),
            'status' => $goal->getStatus(),
            'week' => $goal->getWeek(),
            'year' => $goal->getYear(),
            'progressPercentage' => $goal->getTargetValue() > 0
                ? round(($goal->getProgress() / $goal->getTargetValue()) * 100, 2)
                : 0,
            'createdAt' => $goal->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $goal->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'employee' => [
                'id' => $goal->getEmployee()?->getId(),
                'name' => $goal->getEmployee()?->getFirstName().' '.$goal->getEmployee()?->getLastName(),
            ],
        ], $goals);
    }

    public function getManagerTeamData(User $manager): array
    {
        $teams = $this->teamRepository->findBy(
            ['manager' => $manager],
            ['team_name' => 'ASC']
        );

        return $this->formatTeams($teams);
    }

    private function formatTeams(array $teams): array
    {
        return array_map(fn ($team) => [
            'id' => $team->getId(),
            'teamName' => $team->getTeamName(),
            'manager' => $team->getManager() ? [
                'id' => $team->getManager()->getId(),
                'name' => trim($team->getManager()->getFirstName().' '.$team->getManager()->getLastName()),
            ] : null,
            'members' => array_map(fn ($member) => [
                'id' => $member->getId(),
                'name' => trim($member->getFirstName().' '.$member->getLastName()),
            ], $team->getMembers()->toArray() ?? []),
            'createdAt' => $team->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], $teams);
    }
}
