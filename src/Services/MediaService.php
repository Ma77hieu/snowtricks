<?php

namespace App\Services;

use App\Entity\Media;
use App\Entity\MediaType;
use App\Entity\Trick;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class MediaService
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var string
     */
    private string $imagesDirectory;

    /**
     * @var SluggerInterface
     */
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger, string $imagesDirectory)
    {
        $this->em = $em;
        $this->slugger = $slugger;
        $this->imagesDirectory = $imagesDirectory;
    }

    /**
     * Creates a new media, if it is an image save it in public/uploads
     * for both image and video, creates a line in the media table with the url field being:
     * - the file name for an image
     * - the url for a video
     * @param int $trickId
     * @param int $mediaType
     * @param $form
     * @return array
     */
    public function createMedia(int $trickId, int $mediaType, $form): array
    {
        $media = new Media();
        $trick = $this->em->find(Trick::class, $trickId);
        $trickName = $trick->getName();
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $file = $form->get('url')->getData();
                // this condition is needed because the 'url' field is not required
                // so the file must be processed only when a file is uploaded
                if ($file) {
                    //if the file is an image, we manage the uploading
                    if ($mediaType == 1) {
                        $fileCreation = $this->createNewFile($this->slugger, $file);
                        $e = $fileCreation['fileCreationError'];
                        $newFilename = $fileCreation['newFilename'];
                    }
                    //next condition is true if there was no problem during file upload or if the media is a video
                    if (!isset($e)) {
                        if ($mediaType == 1) {
                            $media->setUrl($newFilename);
                            $type = $this->em->find(MediaType::class, $mediaType);
                            $media->setMediaType($type);
                            $media->setIsMain($form["isMain"]->getData());
                        } elseif ($mediaType == 2) {
                            $urlConverted = $this->convertVideoUrl($file);
                            $urlAccepted = $this->checkVideoUrl($urlConverted);
                            $media->setIsMain(false);
                            if ($urlAccepted != true) {
                                return ['returnType' => 'redirect',
                                    'path' => 'Media.create',
                                    'flashType' => 'danger',
                                    'flashMessage' => "Merci de choisir une video youtube et cliquer sur
                                     \"partager\" puis \"integrer\"  
                                    Utilisez ensuite l'url commencant par https://www.youtube.com/embed",
                                    'data' => ['trickId' => $trickId,
                                        'mediaType' => $mediaType]];
                            }
                            $media->setUrl($urlConverted);
                            $type = $this->em->find(MediaType::class, $mediaType);
                            $media->setMediaType($type);
                        }
                        $media->setTrick($trick);
                        $this->em->persist($media);
                        $this->em->flush();
                        return ['returnType' => 'redirect',
                            'path' => 'Trick.show',
                            'flashType' => 'success',
                            'flashMessage' => 'Le média a été enregistré',
                            'data' => ['trickId' => $trickId]];
                    }
                } else {
                    return ['returnType' => 'redirect',
                        'path' => 'Media.create',
                        'flashType' => 'danger',
                        'flashMessage' => 'Merci de choisir une image à uploader ou une url valide avant de valider',
                        'data' => ['trickId' => $trickId,
                            'mediaType' => $mediaType]];
                }
            }
        }
        return ['returnType' => 'render',
            'path' => 'media/mediaCreation.html.twig',
            'flashType' => '',
            'flashMessage' => '',
            'data' => ['trickName' => $trickName,
                'mediaForm' => $form->createView(),
                'mediaType' => $mediaType]];
    }

    /**
     * Updates an existing media (image or video)
     * @param int $mediaId the id of the media to be updated
     * @param $form
     * @param Media $mediaToUpdate
     * @param bool $was_main was the media the main image before form submitting
     * @return array
     */
    public function update(int $mediaId, $form, Media $mediaToUpdate, bool $was_main): array
    {
        $currentUrl = $mediaToUpdate->getUrl();
        $trickId = $mediaToUpdate->getTrick()->getId();
        $trickName = $mediaToUpdate->getTrick()->getName();
        $type = $mediaToUpdate->getMediaType()->getId();

        if ($form->isSubmitted() && $form->isValid()) {
            $url = $form->get('url')->getData();
            $changes_done = false;
            if ($type == 1) {
                if ($url) {
                    $fileCreation = $this->createNewFile($this->slugger, $url);
                    $e = $fileCreation['fileCreationError'];
                    $newFilename = $fileCreation['newFilename'];
                    if (!isset($e)) {
                        $mediaToUpdate->setUrl($newFilename);
                        $flashType = 'success';
                        $flashMessage = "Modification de l'image effectuée";
                        $changes_done = true;
                    } else {
                        $flashType = 'danger';
                        $flashMessage = "une erreur est survenue lors de 
                        l'enregistrement de l'image, description:" . $e;
                    }
                }
                $is_main = $form->get('isMain')->getData();
                //detect if user wants to change the is_main property
                if ($is_main != $was_main) {
                    //identify the main media for the related trick
                    $mediaRepository = $this->em->getRepository(Media::class);
                    $initialMainMedia = $mediaRepository->findMainMediaWithTrickId($trickId);
                    if ($is_main == true) {
                        //set initial main media is_main value to false if
                        // updated media is not the same and requested to be main
                        if ($initialMainMedia != null and ($initialMainMedia->getId() != $mediaId)) {
                            $initialMainMedia->setIsMain(false);
                            $this->em->persist($initialMainMedia);
                            $this->em->flush();
                        }
                        $mediaToUpdate->setIsMain(true);
                    } else {
                        //change is_main property of media to false if it was true
                        if ($mediaToUpdate->getIsMain() == true) {
                            $mediaToUpdate->setIsMain(false);
                        }
                    }
                    $changes_done = true;
                }
            }
            if ($type == 2) {
                $urlConverted = $this->convertVideoUrl($url);
                $urlAccepted = $this->checkVideoUrl($urlConverted);
                if (($urlAccepted != true) || ($urlConverted == $currentUrl)) {
                    if ($urlAccepted != true) {
                        $flashType = 'danger';
                        $flashMessage = "Format d'url incorrect. Format accepté https://www.youtube.com/watch?v=xxxxx
                         OU https://www.youtube.com/embed/xxxx";
                    }
                    if ($urlConverted == $currentUrl) {
                        $flashType = 'warning';
                        $flashMessage = "L'url entrée est identique à celle enregistrée, pas de modification effectuée";
                        return ['returnType' => 'redirect',
                            'path' => 'Trick.show',
                            'flashType' => $flashType,
                            'flashMessage' => $flashMessage,
                            'data' => ['trickId' => $trickId]];
                    }

                    return ['returnType' => 'redirect',
                        'path' => 'Media.create',
                        'flashType' => $flashType,
                        'flashMessage' => $flashMessage,
                        'data' => ['trickId' => $trickId,
                            'mediaType' => $type]];
                } else {
                    $changes_done = true;
                    $mediaToUpdate->setUrl($urlConverted);
                }
            }
            $this->em->persist($mediaToUpdate);
            $this->em->flush();
            if ($changes_done) {
                $flashType = 'success';
                $flashMessage = "Modification enregistrée";
            }
            return ['returnType' => 'redirect',
                'path' => 'Trick.show',
                'flashType' => $flashType,
                'flashMessage' => $flashMessage,
                'data' => ['trickId' => $trickId]];
        }
        return ['returnType' => 'render',
            'path' => 'media/mediaCreation.html.twig',
            'flashType' => '',
            'flashMessage' => '',
            'data' => ['trickName' => $trickName,
                'mediaForm' => $form->createView(),
                'mediaType' => $type]];
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

    /**
     * Returns an array with all the media id and url ['mediaUrl' => xxx,'mediaId' => xxx]
     * from the repository given as a parameter
     * @param array $mediaRepository
     * @return array
     */
    public function getMediaUrlAndId(array $mediaRepository): array
    {
        $MediaUrl = null;
        $MediaId = null;
        foreach ($mediaRepository as $media) {
            if ($media->getIsMain()) {
                $MediaUrl = $media->getUrl();
                $MediaId = $media->getId();
            }
        }
        return (['mediaUrl' => $MediaUrl,
            'mediaId' => $MediaId]);
    }

    /**
     * Make sure that the url for a video media is valid
     * @param string $url
     * @return bool
     */
    public function checkVideoUrl(string $url): bool
    {

        if (str_contains($url, 'https://www.youtube.com/embed')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Converts a video url from a regular youtube url to the "embed" version of the url
     * this is required to display the video in the cards
     * @param string $url
     * @return string
     */
    public function convertVideoUrl(string $url): string
    {
        if (str_contains($url, 'watch?v=')) {
            return str_replace('watch?v=', 'embed/', $url);
        } else {
            return $url;
        }
    }

    /**
     * Save the image file into the right directory
     * @param SluggerInterface $slugger
     * @param mixed $file the image file itself
     * @return array
     */
    public function createNewFile(SluggerInterface $slugger, $file): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Move the file to the directory where images are stored
        try {
            $file->move(
                $this->imagesDirectory,
                $newFilename
            );
            $fileCreationError = null;
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
            $newFilename = null;
            $fileCreationError = $e;
        }
        return ['newFilename' => $newFilename, 'fileCreationError' => $fileCreationError];
    }
}
