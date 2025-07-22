<?php

namespace App\Twig;

use App\Entity\FileManager as EntityFileManager;
use App\Repository\FileManagerRepository;
use App\Service\FileManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FileExtension extends AbstractExtension
{
    public function __construct(
        private FileManager $fileManager,
        private FileManagerRepository $fmRepo
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('file_encoded', [$this, 'getFileEncoded']),
            new TwigFunction('file_encoded_id', [$this, 'getFileEncodedById'])
        ];
    }

    public function getFileEncoded(EntityFileManager $fileEntity): string
    {
        return $this->fileManager->getFileEncoded($fileEntity);
    }

    public function getFileEncodedById(int $fileId): ?string
    {
        $fileEntity = $this->fmRepo->find($fileId);
        if ($fileEntity) {
            return $this->getFileEncoded($fileEntity);
        }
        return null;
    }
}
