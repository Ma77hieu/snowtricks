<?php

namespace App\Services;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;

class MediaService
{
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /** Returns all media objects that are main images for tricks
     * @return array
     */
    public function getAllMainMedias(): array
    {
        $mediaRepository = $this->em->getRepository(Media::class);
        $allMainMedias = $mediaRepository->findBy(['isMain' => true]);
        return ($allMainMedias);
    }

    /**
     * returns an array of all the ids of medias objects that are given as parameter
     * @param Media $medias
     * @return array
     */
    public function getMediasIds($medias): array
    {
        $mediasIds = [];

        foreach ($medias as $media) {
            array_push($mediasIds, $media->getTrick()->getId());
        }
        return ($mediasIds);
    }
}