<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function getUserTaskStats(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->select(
                'COUNT(t.id) as total',
                "SUM(CASE WHEN t.status = 'open' THEN 1 ELSE 0 END) as openTasks",
                "SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as onGoingTasks",
                "SUM(CASE WHEN t.status = 'closed' THEN 1 ELSE 0 END) as completedTasks",
                "SUM(CASE WHEN t.status = 'cancelled' THEN 1 ELSE 0 END) as cancelledTasks"
            )
            ->where('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();
    }

    // In TaskRepository.php
    public function findTeamTasks($teamId)
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->join('t.createdBy', 'u')
            ->where('u.team = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('t.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // get all completed task datas for the user
    public function getUserCompletedTasks(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.status = :status')
            ->setParameter('status', 'closed')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUserInProgressTasks(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.status = :status')
            ->setParameter('status', 'in_progress')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUserCancelledTasks(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.status = :status')
            ->setParameter('status', 'cancelled')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAveragePriority(User $user): ?float
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('AVG(t.priority) as avgPriority')
            ->where('t.assignedTo = :user')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('user', $user);
        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }

    public function getUserCompletedTasksInPeriod(
        User $user,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('status', 'closed')
            ->setParameter('user', $user);

        if ($startDate) {
            $qb->andWhere('t.completedAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('t.completedAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getOpenTasksInPeriod(
        User $user,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->where('t.assignedTo = :user')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['open', 'in_progress']);

        if ($startDate) {
            $qb->andWhere('t.createdAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('t.createdAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTaskCompletedOnTime(
        User $user,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.completedAt IS NOT NULL')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.completedAt <= t.dueDate')
            ->setParameter('status', 'closed')
            ->setParameter('user', $user);

        if ($startDate) {
            $qb->andWhere('t.completedAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('t.completedAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageCompletionTime(User $user, ?\DateTimeImmutable $startDate = null, ?\DateTimeImmutable $endDate = null): ?float
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.assignedTo = :user')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('user', $user);

        if ($startDate) {
            $qb->andWhere('t.completedAt >= :startDate')->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('t.completedAt <= :endDate')->setParameter('endDate', $endDate);
        }

        $tasks = $qb->getQuery()->getResult();

        if (0 === count($tasks)) {
            return null;
        }

        $totalHours = 0;
        foreach ($tasks as $task) {
            if ($task->getCreatedAt() && $task->getCompletedAt()) {
                $interval = $task->getCreatedAt()->diff($task->getCompletedAt());
                $totalHours += ($interval->days * 24) + $interval->h + ($interval->i / 60);
            }
        }

        return $totalHours / count($tasks);
    }

    public function getAverageDelayHours(User $user, ?\DateTimeImmutable $startDate = null, ?\DateTimeImmutable $endDate = null): ?float
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.assignedTo = :user')
            ->andWhere('t.completedAt IS NOT NULL')
            ->andWhere('t.completedAt > t.dueDate')
            ->setParameter('user', $user);

        if ($startDate) {
            $qb->andWhere('t.completedAt >= :startDate')->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('t.completedAt <= :endDate')->setParameter('endDate', $endDate);
        }

        $tasks = $qb->getQuery()->getResult();

        if (0 === count($tasks)) {
            return null;
        }

        $totalDelayHours = 0;
        foreach ($tasks as $task) {
            if ($task->getDueDate() && $task->getCompletedAt()) {
                $interval = $task->getDueDate()->diff($task->getCompletedAt());
                $hours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
                $totalDelayHours += $hours;
            }
        }

        return $totalDelayHours / count($tasks);
    }

    public function getSuccessRateByPriority(
        User $user,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->select('t.priority')
            ->addSelect('COUNT(t.id) as totalTasks')
            ->addSelect('SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) as completedTasks')
            ->addSelect('SUM(CASE WHEN t.status = :cancelled THEN 1 ELSE 0 END) as cancelledTasks')
            ->addSelect('SUM(CASE WHEN t.status = :inProgress THEN 1 ELSE 0 END) as inProgressTasks')
            ->addSelect('(SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) * 100.0 / COUNT(t.id)) as successRate')
            ->where('t.assignedTo = :user')
            ->groupBy('t.priority')
            ->orderBy('t.priority', 'ASC')
            ->setParameter('user', $user)
            ->setParameter('completed', 'closed')
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('inProgress', 'in_progress');

        if ($startDate || $endDate) {
            // Filter by completion date for completed tasks, creation date for others
            $qb->andWhere('(t.completedAt IS NOT NULL AND t.completedAt BETWEEN :start AND :end) OR (t.completedAt IS NULL AND t.createdAt BETWEEN :start AND :end)')
                ->setParameter('start', $startDate ?? new \DateTimeImmutable('1970-01-01'))
                ->setParameter('end', $endDate ?? new \DateTimeImmutable('2099-12-31'));
        }

        return $qb->getQuery()->getResult();
    }

    public function getHighPrioritySuccessRate(
        User $user,
        int $highPriority = 1,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): ?float {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) as total')
            ->addSelect('SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) as completed')
            ->where('t.assignedTo = :user')
            ->andWhere('t.priority = :priority')
            ->setParameter('user', $user)
            ->setParameter('priority', $highPriority)
            ->setParameter('completed', 'closed');

        if ($startDate || $endDate) {
            $qb->andWhere('(t.completedAt IS NOT NULL AND t.completedAt BETWEEN :start AND :end) OR (t.completedAt IS NULL AND t.createdAt BETWEEN :start AND :end)')
                ->setParameter('start', $startDate ?? new \DateTimeImmutable('1970-01-01'))
                ->setParameter('end', $endDate ?? new \DateTimeImmutable('2099-12-31'));
        }

        $result = $qb->getQuery()->getSingleResult();

        if (0 == $result['total']) {
            return null;
        }

        return ($result['completed'] / $result['total']) * 100;
    }

    public function getTaskStatusByPriority(
        User $user,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->select('t.priority, t.status')
            ->addSelect('COUNT(t.id) as count')
            ->where('t.assignedTo = :user')
            ->groupBy('t.priority, t.status')
            ->orderBy('t.priority', 'ASC')
            ->addOrderBy('t.status', 'ASC')
            ->setParameter('user', $user);

        if ($startDate || $endDate) {
            $qb->andWhere('(t.completedAt IS NOT NULL AND t.completedAt BETWEEN :start AND :end) OR (t.completedAt IS NULL AND t.createdAt BETWEEN :start AND :end)')
                ->setParameter('start', $startDate ?? new \DateTimeImmutable('1970-01-01'))
                ->setParameter('end', $endDate ?? new \DateTimeImmutable('2099-12-31'));
        }

        return $qb->getQuery()->getResult();
    }

    public function getCompletionRateByPriority(
        User $user,
        int $priority,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): ?float {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignedTo = :user')
            ->andWhere('t.priority = :priority')
            ->setParameter('user', $user)
            ->setParameter('priority', $priority);

        if ($startDate || $endDate) {
            $qb->andWhere('(t.completedAt IS NOT NULL AND t.completedAt BETWEEN :start AND :end) OR (t.completedAt IS NULL AND t.createdAt BETWEEN :start AND :end)')
                ->setParameter('start', $startDate ?? new \DateTimeImmutable('1970-01-01'))
                ->setParameter('end', $endDate ?? new \DateTimeImmutable('2099-12-31'));
        }

        $total = $qb->getQuery()->getSingleScalarResult();

        if (0 == $total) {
            return null;
        }

        $qb2 = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignedTo = :user')
            ->andWhere('t.priority = :priority')
            ->andWhere('t.status = :completed')
            ->setParameter('user', $user)
            ->setParameter('priority', $priority)
            ->setParameter('completed', 'closed');

        if ($startDate) {
            $qb2->andWhere('t.completedAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb2->andWhere('t.completedAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $completed = $qb2->getQuery()->getSingleScalarResult();

        return ($completed / $total) * 100;
    }
}
