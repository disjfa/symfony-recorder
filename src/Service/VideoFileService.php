<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class VideoFileService
{
    private Filesystem $filesystem;

    public function __construct(
        private readonly string $chunksDir,
        private readonly string $videosDir,
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Save an uploaded chunk file and return file size.
     */
    public function saveChunk(string $uploadSessionId, int $chunkNumber, string $chunkContent): int
    {
        $this->filesystem->mkdir($this->chunksDir, 0755);

        $filename = sprintf('chunk_%s_%d.webm', $uploadSessionId, $chunkNumber);
        $filePath = $this->chunksDir.'/'.$filename;

        $this->filesystem->dumpFile($filePath, $chunkContent);

        return (new File($filePath))->getSize();
    }

    /**
     * Get the relative path for a chunk file.
     */
    public function getChunkRelativePath(string $uploadSessionId, int $chunkNumber): string
    {
        $filename = sprintf('chunk_%s_%d.webm', $uploadSessionId, $chunkNumber);

        return '/chunks/'.$filename;
    }

    /**
     * Stitch multiple chunk files together into a single video.
     */
    public function stitchChunks(array $chunkPaths): array
    {
        if (empty($chunkPaths)) {
            throw new \RuntimeException('No chunk paths provided for stitching');
        }

        $this->filesystem->mkdir($this->videosDir, 0755);

        $outputContent = '';
        $totalSize = 0;

        foreach ($chunkPaths as $chunkPath) {
            if (!$this->filesystem->exists($chunkPath)) {
                throw new \RuntimeException(sprintf('Chunk file not found: %s', $chunkPath));
            }

            $chunkFile = new File($chunkPath);
            $outputContent .= $chunkFile->getContent();
            $totalSize += $chunkFile->getSize();
        }

        // Generate unique filename for final video
        $filename = 'video_'.uniqid('', true).'.webm';
        $outputPath = $this->videosDir.'/'.$filename;

        $this->filesystem->dumpFile($outputPath, $outputContent);

        return [
            'path' => '/videos/'.$filename,
            'size' => $totalSize,
        ];
    }

    /**
     * Delete a video file.
     */
    public function deleteVideo(string $videoPath): void
    {
        // Convert relative path (/videos/filename.webm) to absolute path
        $absolutePath = str_replace('/videos/', $this->videosDir.'/', $videoPath);

        if ($this->filesystem->exists($absolutePath)) {
            $this->filesystem->remove($absolutePath);
        }
    }

    /**
     * Delete a chunk file.
     */
    public function deleteChunk(string $uploadSessionId, int $chunkNumber): void
    {
        $filename = sprintf('chunk_%s_%d.webm', $uploadSessionId, $chunkNumber);
        $filePath = $this->chunksDir.'/'.$filename;

        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }
    }

    /**
     * Delete all chunks for a session.
     */
    public function deleteChunksForSession(string $uploadSessionId): int
    {
        $pattern = $this->chunksDir.'/chunk_'.$uploadSessionId.'_*.webm';
        $files = glob($pattern);

        $deletedCount = 0;
        foreach ($files as $file) {
            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);
                ++$deletedCount;
            }
        }

        return $deletedCount;
    }

    /**
     * Get the absolute path for a chunk file.
     */
    public function getChunkAbsolutePath(string $uploadSessionId, int $chunkNumber): string
    {
        $filename = sprintf('chunk_%s_%d.webm', $uploadSessionId, $chunkNumber);

        return $this->chunksDir.'/'.$filename;
    }

    /**
     * Get the absolute path for a video file.
     */
    public function getVideoAbsolutePath(string $videoPath): string
    {
        return str_replace('/videos/', $this->videosDir.'/', $videoPath);
    }
}
