<?php

namespace App\Service;

use App\Entity\Goal;
use App\Entity\Task;
use App\Entity\Team;
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

    /**
     * @return array{taskStatistics: array<string, int|float>, goalStatistics: array<string, mixed>, tasks: array<int, array<string, mixed>>, goals: array<int, array<string, mixed>>}
     */
    public function getDashboardData(User $user): array
    {
        return [
            'taskStatistics' => $this->getTaskStatistics($user),
            'goalStatistics' => $this->getGoalStatistics($user),
            'tasks' => $this->getUserTasks($user),
            'goals' => $this->getUserGoals($user),
        ];
    }

    /**
     * @return array{team: array<int, array<string, mixed>>}
     */
    public function getManagerDashboardData(User $manager): array
    {
        return [
            'team' => $this->getManagerTeamData($manager),
        ];
    }

    /**
     * @return array{totalTasks: int, openTasks: int, onGoingTasks: int, completedTasks: int, cancelledTasks: int, taskCompletionRate: float}
     */
    private function getTaskStatistics(User $user): array
    {
        $taskStats = $this->taskRepository->getUserTaskStats($user);

        $total = is_numeric($taskStats['total']) ? (int) $taskStats['total'] : 0;
        $completedTasks = is_numeric($taskStats['completedTasks']) ? (int) $taskStats['completedTasks'] : 0;

        $taskCompletionRate = $total > 0
            ? round(($completedTasks / $total) * 100, 2)
            : 0;

        return [
            'totalTasks' => $total,
            'openTasks' => is_numeric($taskStats['openTasks']) ? (int) $taskStats['openTasks'] : 0,
            'onGoingTasks' => is_numeric($taskStats['onGoingTasks']) ? (int) $taskStats['onGoingTasks'] : 0,
            'completedTasks' => $completedTasks,
            'cancelledTasks' => is_numeric($taskStats['cancelledTasks']) ? (int) $taskStats['cancelledTasks'] : 0,
            'taskCompletionRate' => $taskCompletionRate,
        ];
    }

    /**
     * @return array{totalGoals: int, openGoals: int, inProgressGoals: int, completedGoals: int, cancelledGoals: int, completionRate: float, currentWeek: int, currentYear: int, weeklyGoals: array<int, array<string, mixed>>, AverageWeeklyProgress: float}
     */
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUserTasks(User $user): array
    {
        $tasks = $this->taskRepository->findBy(
            ['assignedTo' => $user],
            ['createdAt' => 'DESC'],
            50
        );

        return $this->formatTasks($tasks);
    }

    /**
     * @param Task[] $tasks
     *
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUserGoals(User $user): array
    {
        $goals = $this->goalRepository->findBy(
            ['employee' => $user],
            ['year' => 'DESC', 'week' => 'DESC'],
            50
        );

        return $this->formatGoals($goals);
    }

    /**
     * @param Goal[] $goals
     *
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getManagerTeamData(User $manager): array
    {
        $teams = $this->teamRepository->findBy(
            ['manager' => $manager],
            ['team_name' => 'ASC']
        );

        return $this->formatTeams($teams);
    }

    /**
     * @param Team[] $teams
     *
     * @return array<int, array<string, mixed>>
     */
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
            ], $team->getMembers()->toArray()),
            'createdAt' => $team->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], $teams);
    }
}
