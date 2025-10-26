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

}
