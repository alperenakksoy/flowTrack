<?php

namespace App\Repository;

use App\Entity\Goal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Goal>
 */
class GoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Goal::class);
    }

    /**
     * Returns goal statistics for a given user.
     *
     * @return array{
     *     total: int,
     *     open: int,
     *     in_progress: int,
     *     closed: int,
     *     cancelled: int,
     *     completionRate: float
     * }
     */
    public function getStatisticsByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g.status, COUNT(g.id) AS count')
            ->where('g.employee = :user')
            ->setParameter('user', $user)
            ->groupBy('g.status');

        /** @var array<int, array{status: string, count: int|string}> */
        $rawResults = $qb->getQuery()->getResult();

        $stats = [
            'open' => 0,
            'in_progress' => 0,
            'closed' => 0,
            'cancelled' => 0,
        ];

        foreach ($rawResults as $row) {
            $status = $row['status'];
            if (isset($stats[$status])) {
                $stats[$status] = (int) $row['count'];
            }
        }

        $stats['total'] = $stats['open'] + $stats['in_progress'] + $stats['closed'] + $stats['cancelled'];

        $validTotal = $stats['open'] + $stats['in_progress'] + $stats['closed'];
        $stats['completionRate'] = $validTotal > 0
            ? round(($stats['closed'] / $validTotal) * 100, 2)
            : 0;

        return $stats;
    }
}
