<?php

namespace App\Repository;

use App\Entity\VideoChunk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<VideoChunk>
 */
class VideoChunkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VideoChunk::class);
    }

    /**
     * Find all chunks for a given upload session, ordered by chunk number.
     */
    public function findByUploadSession(Uuid $uploadSessionId): array
    {
        return $this->createQueryBuilder('vc')
            ->andWhere('vc.uploadSessionId = :sessionId')
            ->setParameter('sessionId', $uploadSessionId, 'uuid')
            ->orderBy('vc.chunkNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if all chunks have been uploaded (i.e., we received the last chunk).
     */
    public function isUploadComplete(Uuid $uploadSessionId): bool
    {
        return $this->createQueryBuilder('vc')
            ->select('COUNT(vc.id)')
            ->andWhere('vc.uploadSessionId = :sessionId')
            ->andWhere('vc.isLast = :isLast')
            ->setParameter('sessionId', $uploadSessionId, 'uuid')
            ->setParameter('isLast', true)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Get total number of chunks for a session.
     */
    public function getChunkCount(Uuid $uploadSessionId): int
    {
        return $this->createQueryBuilder('vc')
            ->select('COUNT(vc.id)')
            ->andWhere('vc.uploadSessionId = :sessionId')
            ->setParameter('sessionId', $uploadSessionId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
