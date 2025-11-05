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

    public function getTaskCompletedOnTime(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.status = :status')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('status', 'completed')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->andWhere('t.completedAt <= t.dueDate')
            ->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageCompletionTime(User $user): ?float
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('AVG(TIMESTAMPDIFF(HOUR, t.createdAt, t.completedAt)) AS avgHours')
            ->where('t.assignedTo = :user')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('user', $user);

        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }

    public function getAverageDelayHours(User $user): ?float
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('AVG(TIMESTAMPDIFF(HOUR, t.dueDate, t.completedAt)) AS avgDelayHours')
            ->where('t.assignedTo = :user')
            ->andWhere('t.completedAt IS NOT NULL')
            ->andWhere('t.completedAt > t.dueDate') // Only delayed tasks
            ->setParameter('user', $user);

        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
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

    public function getSuccessRateByPriority(User $user): array
    {
        $qb = $this->createQueryBuilder('t');

        return $qb->select('t.priority')
            ->addSelect('COUNT(t.id) as totalTasks')
            ->addSelect('SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) as completedTasks')
            ->addSelect('SUM(CASE WHEN t.status = :cancelled THEN 1 ELSE 0 END) as cancelledTasks')
            ->addSelect('SUM(CASE WHEN t.status = :inProgress THEN 1 ELSE 0 END) as inProgressTasks')
            ->addSelect('(SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) * 100.0 / COUNT(t.id)) as successRate')
            ->where('t.assignedTo = :user')
            ->groupBy('t.priority')
            ->orderBy('t.priority', 'ASC')
            ->setParameter('user', $user)
            ->setParameter('completed', 'completed')
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('inProgress', 'in_progress')
            ->getQuery()
            ->getResult();
    }

    public function getHighPrioritySuccessRate(User $user, int $highPriority = 1): ?float
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('COUNT(t.id) as total')
            ->addSelect('SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) as completed')
            ->where('t.assignedTo = :user')
            ->andWhere('t.priority = :priority')
            ->setParameter('user', $user)
            ->setParameter('priority', $highPriority)
            ->setParameter('completed', 'completed');

        $result = $qb->getQuery()->getSingleResult();

        if (0 == $result['total']) {
            return null;
        }

        return ($result['completed'] / $result['total']) * 100;
    }

    public function getTaskStatusByPriority(User $user): array
    {
        $qb = $this->createQueryBuilder('t');

        return $qb->select('t.priority, t.status')
            ->addSelect('COUNT(t.id) as count')
            ->where('t.assignedTo = :user')
            ->groupBy('t.priority, t.status')
            ->orderBy('t.priority', 'ASC')
            ->addOrderBy('t.status', 'ASC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getCompletionRateByPriority(User $user, int $priority): ?float
    {
        $qb = $this->createQueryBuilder('t');

        $total = $qb->select('COUNT(t.id)')
            ->where('t.assignedTo = :user')
            ->andWhere('t.priority = :priority')
            ->setParameter('user', $user)
            ->setParameter('priority', $priority)
            ->getQuery()
            ->getSingleScalarResult();

        if (0 == $total) {
            return null;
        }

        $completed = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignedTo = :user')
            ->andWhere('t.priority = :priority')
            ->andWhere('t.status = :completed')
            ->setParameter('user', $user)
            ->setParameter('priority', $priority)
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return ($completed / $total) * 100;
    }
}
