<?php

namespace App\MessageHandler;

use App\Entity\Task;
use App\Entity\Video;
use App\Message\CleanupVideoChunks;
use App\Message\StitchVideoChunks;
use App\Repository\VideoChunkRepository;
use App\Service\VideoFileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class StitchVideoChunksHandler
{
    public function __construct(
        private readonly VideoChunkRepository $chunkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
        private readonly VideoFileService $videoFileService,
    ) {
    }

    public function __invoke(StitchVideoChunks $message): void
    {
        $uploadSessionId = $message->getUploadSessionId();

        $this->logger->info('Starting video stitching for session: '.$uploadSessionId->toRfc4122());

        // Get all chunks for this session
        $chunks = $this->chunkRepository->findByUploadSession($uploadSessionId);

        if (empty($chunks)) {
            $this->logger->error('No chunks found for session: '.$uploadSessionId->toRfc4122());

            return;
        }

        // Get metadata from the first chunk
        $firstChunk = $chunks[0];
        $title = $firstChunk->getTitle() ?? 'Untitled Recording';
        $description = $firstChunk->getDescription() ?? '';

        try {
            // Prepare absolute chunk paths for stitching
            $chunkPaths = [];
            foreach ($chunks as $chunk) {
                $absolutePath = $this->videoFileService->getChunkAbsolutePath(
                    $uploadSessionId->toRfc4122(),
                    $chunk->getChunkNumber()
                );
                $chunkPaths[] = $absolutePath;

                $this->logger->debug('Added chunk to stitch: '.$chunk->getChunkNumber());
            }

            // Stitch chunks together
            $stitchResult = $this->videoFileService->stitchChunks($chunkPaths);
            $totalSize = $stitchResult['size'];

            $this->logger->info('Video stitching complete. Total size: '.$totalSize.' bytes');

            // Create Video entity
            $video = new Video();
            $video->setTitle($title);
            $video->setDescription($description);
            $video->setFilePath($stitchResult['path']);
            $video->setMimeType('video/webm');
            $video->setFileSize($totalSize);

            // Create associated Task
            $task = new Task();
            $task->setTitle($title);
            $task->setDescription($description);
            $task->setVideo($video);

            $this->entityManager->persist($video);
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            $this->logger->info('Video and task created successfully');

            // Dispatch cleanup message to remove chunks asynchronously
            $this->messageBus->dispatch(new CleanupVideoChunks($uploadSessionId));
            $this->logger->info('Dispatched cleanup message for '.count($chunks).' chunks');
        } catch (\Exception $e) {
            $this->logger->error('Error stitching video: '.$e->getMessage());

            // Dispatch cleanup message to remove failed chunks
            $this->messageBus->dispatch(new CleanupVideoChunks($uploadSessionId));
            $this->logger->info('Dispatched cleanup message after error');

            throw $e;
        }
    }
}
