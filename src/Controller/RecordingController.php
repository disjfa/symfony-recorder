<?php

namespace App\Controller;

use App\Entity\Video;
use App\Entity\VideoChunk;
use App\Message\StitchVideoChunks;
use App\Repository\VideoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class RecordingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/recording', name: 'app_recording', methods: ['GET'])]
    public function recording(): Response
    {
        return $this->render('recording/index.html.twig');
    }

    #[Route('/gallery', name: 'app_gallery', methods: ['GET'])]
    public function gallery(VideoRepository $videoRepository): Response
    {
        $videos = $videoRepository->findAllOrderedByCreated();

        return $this->render('recording/gallery.html.twig', [
            'videos' => $videos,
        ]);
    }

    #[Route('/api/upload-chunk', name: 'api_upload_chunk', methods: ['POST'])]
    public function uploadChunk(Request $request): JsonResponse
    {
        try {
            // Get chunk data
            $chunkFile = $request->files->get('chunk');
            $uploadSessionId = $request->request->get('uploadSessionId');
            $chunkNumber = (int) $request->request->get('chunkNumber');
            $isLast = filter_var($request->request->get('isLast', 'false'), FILTER_VALIDATE_BOOLEAN);
            $title = $request->request->get('title', 'Untitled Recording');
            $description = $request->request->get('description', '');

            if (!$chunkFile) {
                return new JsonResponse(['error' => 'No chunk received'], Response::HTTP_BAD_REQUEST);
            }

            if (!$uploadSessionId) {
                return new JsonResponse(['error' => 'No upload session ID'], Response::HTTP_BAD_REQUEST);
            }

            // Create chunks directory
            $chunkDir = $this->getParameter('kernel.project_dir').'/public/chunks';
            $this->filesystem->mkdir($chunkDir, 0755);

            // Generate unique filename for chunk
            $filename = sprintf('chunk_%s_%d.webm', $uploadSessionId, $chunkNumber);

            // Save chunk file
            $chunkFile->move($chunkDir, $filename);
            $filePath = $chunkDir.'/'.$filename;

            // Use Symfony File class to get file information
            $uploadedFile = new File($filePath);

            // Create VideoChunk entity
            $chunk = new VideoChunk();
            $chunk->setUploadSessionId(Uuid::fromString($uploadSessionId));
            $chunk->setChunkNumber($chunkNumber);
            $chunk->setFilePath('/chunks/'.$filename);
            $chunk->setFileSize($uploadedFile->getSize());
            $chunk->setTitle($title);
            $chunk->setDescription($description);
            $chunk->setIsLast($isLast);

            $this->entityManager->persist($chunk);
            $this->entityManager->flush();

            // If this is the last chunk, dispatch message to stitch video
            if ($isLast) {
                $this->messageBus->dispatch(new StitchVideoChunks(Uuid::fromString($uploadSessionId)));
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Chunk uploaded successfully',
                'chunkNumber' => $chunkNumber,
                'isLast' => $isLast,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to upload chunk: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/video/{id}/delete', name: 'app_delete_video', methods: ['GET'])]
    public function deleteVideo(Video $video): JsonResponse
    {
        // Delete the file
        $filePath = $this->getParameter('kernel.project_dir').'/public'.$video->getFilePath();
        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }

        $this->entityManager->remove($video);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Video deleted successfully']);
    }
}
