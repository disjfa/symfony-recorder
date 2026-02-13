<?php

namespace App\MessageHandler;

use App\Message\CleanupVideoChunks;
use App\Repository\VideoChunkRepository;
use App\Service\VideoFileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CleanupVideoChunksHandler
{
    public function __construct(
        private readonly VideoChunkRepository $chunkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly VideoFileService $videoFileService,
    ) {
    }

    public function __invoke(CleanupVideoChunks $message): void
    {
        $uploadSessionId = $message->getUploadSessionId();

        $this->logger->info('Starting chunk cleanup for session: '.$uploadSessionId->toRfc4122());

        // Get all chunks for this session
        $chunks = $this->chunkRepository->findByUploadSession($uploadSessionId);

        if (empty($chunks)) {
            $this->logger->warning('No chunks found to clean up for session: '.$uploadSessionId->toRfc4122());

            return;
        }

        // Delete chunk files from filesystem
        $deletedFiles = $this->videoFileService->deleteChunksForSession($uploadSessionId->toRfc4122());

        // Remove chunk entities from database
        $deletedRecords = 0;
        foreach ($chunks as $chunk) {
            $this->entityManager->remove($chunk);
            ++$deletedRecords;
        }

        $this->entityManager->flush();

        $this->logger->info(sprintf(
            'Cleanup complete for session %s: Deleted %d files and %d database records',
            $uploadSessionId->toRfc4122(),
            $deletedFiles,
            $deletedRecords
        ));
    }
}
