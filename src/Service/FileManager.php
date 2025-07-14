<?php

namespace App\Service;

use App\Entity\FileManager as EntityFileManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileManager
{
    private const FOLDER_DATA = '/data';

    private string $folderPath;

    public function __construct(
        private SluggerInterface $slugger,
        private Filesystem $filesystem,
        private string $projectDirectory
    ) {
        $this->folderPath = $projectDirectory . self::FOLDER_DATA;
        if (!$this->filesystem->exists($this->folderPath)) {
            $this->filesystem->mkdir($this->folderPath, 0700);
        }
    }

    /**
     * @param string $folderName
     * @return string
     */
    public function getDirectory(string $folderName): string
    {
        $directory = $this->folderPath . '/' . $folderName;
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory);
        }
        return $directory;
    }

    /**
     * @param string $folderName
     * @param string $name
     * @param UploadedFile $file
     * @return EntityFileManager|bool
     */
    public function upload(string $folderName, string $name, UploadedFile $file): EntityFileManager|bool
    {
        $safeFilename = $this->slugger->slug($name);

        $filename = sprintf(
            '%s-%s.%s',
            $safeFilename,
            uniqid(),
            $file->guessExtension()
        );

        try {
            $file->move(
                $this->getDirectory($folderName),
                $filename
            );
        } catch (FileException $e) {
            return false;
        }

        $fileManager = new EntityFileManager();
        $fileManager->setFolder($folderName)
            ->setName($filename);
        ;

        return $fileManager;
    }

    /**
     * @param EntityFileManager $fileManager
     * @return string
     */
    public function getFileEncoded(EntityFileManager $fileManager): string
    {
        $directory = $this->getDirectory($fileManager->getFolder());
        $filePath = $directory . '/' . $fileManager->getName();
        $mimeType = mime_content_type($filePath);

        return 'data:' . $mimeType . ';base64, ' . base64_encode(file_get_contents($filePath));
    }

    /**
     * @param string $filename
     * @param string $folder
     * @return bool
     */
    public function removeFile(string $filename, string $folder): bool
    {
        $directory = $this->getDirectory($folder);
        $filePath = $directory . '/' . $filename;
        if (!$this->filesystem->exists($filePath)) {
            return false;
        }

        $this->filesystem->remove($filePath);
        return true;
    }
}
