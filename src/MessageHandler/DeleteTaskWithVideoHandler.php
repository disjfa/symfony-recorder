<?php

namespace App\MessageHandler;

use App\Message\DeleteTaskWithVideo;
use App\Repository\TaskRepository;
use App\Service\VideoFileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteTaskWithVideoHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly VideoFileService $videoFileService,
    ) {
    }

    public function __invoke(DeleteTaskWithVideo $message): void
    {
        $taskId = $message->getTaskId();

        $this->logger->info('Processing task deletion for: '.$taskId->toRfc4122());

        // Find the task
        $task = $this->taskRepository->find($taskId);

        if (!$task) {
            $this->logger->warning('Task not found: '.$taskId->toRfc4122());

            return;
        }

        // Get associated video before deleting task
        $video = $task->getVideo();

        if (!$video) {
            $this->logger->info('No video associated with task: '.$taskId->toRfc4122());
            // Just delete the task
            $this->entityManager->remove($task);
            $this->entityManager->flush();

            return;
        }

        $this->logger->info('Deleting video associated with task: '.$taskId->toRfc4122());

        // Delete the video file from filesystem using the service
        $this->videoFileService->deleteVideo($video->getFilePath());
        $this->logger->info('Deleted video file for task: '.$taskId->toRfc4122());

        // Remove video and task entities from database
        $this->entityManager->remove($video);
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->logger->info('Deleted video and task: '.$taskId->toRfc4122());
    }
}
