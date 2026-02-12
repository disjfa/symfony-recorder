<?php

namespace App\Repository;

use App\Entity\Task;
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

    public function findAllOrderedByCreated(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.video', 'v')
            ->addSelect('v')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.video', 'v')
            ->addSelect('v')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.video', 'v')
            ->addSelect('v')
            ->where('t.priority = :priority')
            ->setParameter('priority', $priority)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
