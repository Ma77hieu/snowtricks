<?php

namespace App\Services;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;

class MediaService
{
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    public function getAllMainMedias(): array
    {
        $mediaRepository = $this->em->getRepository(Media::class);
        $allMainMedias = $mediaRepository->findBy(['isMain' => true]);
        return ($allMainMedias);
    }

    public function getMediasIds($medias): array
    {
        $mediasIds = [];

        foreach ($medias as $media) {
            array_push($mediasIds, $media->getTrick()->getId());
        }
        return ($mediasIds);
    }
}