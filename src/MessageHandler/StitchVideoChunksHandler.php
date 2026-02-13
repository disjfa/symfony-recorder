<?php

namespace App\MessageHandler;

use App\Entity\Task;
use App\Entity\Video;
use App\Message\CleanupVideoChunks;
use App\Message\StitchVideoChunks;
use App\Repository\VideoChunkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
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
        private readonly Filesystem $filesystem,
        private readonly string $projectDir,
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

        // Get metadata from the first chunk (or last chunk)
        $firstChunk = $chunks[0];
        $title = $firstChunk->getTitle() ?? 'Untitled Recording';
        $description = $firstChunk->getDescription() ?? '';

        // Create output directory
        $videoDir = $this->projectDir.'/public/videos';
        $this->filesystem->mkdir($videoDir, 0755);

        // Generate unique filename for final video
        $filename = 'video_'.uniqid('', true).'.webm';
        $outputPath = $videoDir.'/'.$filename;

        try {
            // Concatenate all chunks
            $outputContent = '';
            $totalSize = 0;

            foreach ($chunks as $chunk) {
                $chunkPath = $this->projectDir.'/public'.$chunk->getFilePath();

                if (!$this->filesystem->exists($chunkPath)) {
                    $this->logger->error('Chunk file not found: '.$chunkPath);
                    continue;
                }

                $chunkFile = new File($chunkPath);
                $chunkContent = $chunkFile->getContent();
                $outputContent .= $chunkContent;
                $totalSize += $chunk->getFileSize();

                $this->logger->debug('Processed chunk: '.$chunk->getChunkNumber());
            }

            $this->filesystem->dumpFile($outputPath, $outputContent);

            $this->logger->info('Video stitching complete. Total size: '.$totalSize.' bytes');

            // Create Video entity
            $video = new Video();
            $video->setTitle($title);
            $video->setDescription($description);
            $video->setFilePath('/videos/'.$filename);
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

            // Clean up output file if it exists
            if (isset($outputPath)) {
                $this->filesystem->remove($outputPath);
            }

            // Dispatch cleanup message to remove failed chunks
            $this->messageBus->dispatch(new CleanupVideoChunks($uploadSessionId));
            $this->logger->info('Dispatched cleanup message after error');

            throw $e;
        }
    }
}
