<?php

namespace App\Services;

use App\Entity\Media;
use App\Entity\MediaType;
use App\Entity\Trick;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
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
        $this->imagesDirectory=$imagesDirectory;
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
    public function createMedia(int $trickId, int $mediaType, $form):array
    {
        $media=new Media();
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
                        } else if ($mediaType == 2) {
                            $urlConverted = $this->convertVideoUrl($file);
                            $urlAccepted = $this->checkVideoUrl($urlConverted);
                            $media->setIsMain(false);
                            if ($urlAccepted != true) {
                                /*$this->addFlash("danger",
                                    "Merci de choisir une video youtube et cliquer sur \"partager\" puis \"integrer\"  
                                    Utilisez ensuite l'url commencant par https://www.youtube.com/embed");
                                return $this->redirectToRoute('Media.create', [
                                    'trickId' => $trickId,
                                    'mediaType' => $mediaType]);*/
                                return ['returnType'=>'redirect',
                                    'path'=>'Media.create',
                                    'flashType'=>'danger',
                                    'flashMessage'=>"Merci de choisir une video youtube et cliquer sur \"partager\" puis \"integrer\"  
                                    Utilisez ensuite l'url commencant par https://www.youtube.com/embed",
                                    'data'=>['trickId' => $trickId,
                                        'mediaType' => $mediaType]];
                            }
                            $media->setUrl($urlConverted);
                            $type = $this->em->find(MediaType::class, $mediaType);
                            $media->setMediaType($type);
                        }
                        $media->setTrick($trick);
                        $this->em->persist($media);
                        $this->em->flush();
                        /*$this->addFlash("success", "Le média a été enregistré");
                        return $this->redirectToRoute('Trick.show', ['trickId' => $trickId]);*/
                        return ['returnType'=>'redirect',
                            'path'=>'Trick.show',
                            'flashType'=>'success',
                            'flashMessage'=>'Le média a été enregistré',
                        'data'=>['trickId' => $trickId]];
                    }

                } else {
                    /*$this->addFlash("danger", "Merci de choisir une image à uploader ou une url valide avant de valider");
                    return $this->redirectToRoute('Media.create', [
                        'trickId' => $trickId,
                        'mediaType' => $mediaType]);*/
                    return ['returnType'=>'redirect',
                        'path'=>'Media.create',
                        'flashType'=>'danger',
                        'flashMessage'=>'Merci de choisir une image à uploader ou une url valide avant de valider',
                        'data'=>['trickId' => $trickId,
                            'mediaType' => $mediaType]];
                }

            }
        }
        /*return $this->render('media/mediaCreation.html.twig', [
            'controller_name' => 'MediaController',
            'trickName' => $trickName,
            'mediaForm' => $form->createView(),
            'mediaType' => $mediaType,
        ]);*/
        return ['returnType'=>'render',
            'path'=>'media/mediaCreation.html.twig',
            'flashType'=>'',
            'flashMessage'=>'',
            'data'=>['trickName' => $trickName,
                'mediaForm' => $form->createView(),
                'mediaType' => $mediaType]];
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
                /*$this->getParameter('images_directory'),*/
                $this->imagesDirectory,
                $newFilename
            );
            $fileCreationError = null;
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
            /*$this->addFlash("danger", "une erreur est survenue lors de l'enregistrement de l'image, description:" . $e);*/
            $newFilename = null;
            $fileCreationError = $e;
        }
        return ['newFilename' => $newFilename, 'fileCreationError' => $fileCreationError];
    }
}

/*return ['returnType'=>'',
    'path'=>'',
    'flashType'=>'',
    'flashMessage'=>'',
    'data'=>[''=>'',''=>'']];*/