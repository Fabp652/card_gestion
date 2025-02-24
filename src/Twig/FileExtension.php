<?php

namespace App\Twig;

use App\Entity\FileManager as EntityFileManager;
use App\Service\FileManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FileExtension extends AbstractExtension
{
    public function __construct(private FileManager $fileManager)
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('file_encoded', [$this, 'getFileEncoded'])
        ];
    }

    public function getFileEncoded(EntityFileManager $fileEntity): string
    {
        return $this->fileManager->getFileEncoded($fileEntity);
    }
}
