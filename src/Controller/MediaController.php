<?php

namespace App\Controller;

use App\Entity\Media;
use App\Form\MediaFormType;
use App\Services\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class MediaController extends AbstractController
{
    /**
     * @var MediaService
     */
    private MediaService $mediaService;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var GenericController
     */
    private GenericController $genericController;


    public function __construct(MediaService $mediaService, EntityManagerInterface $em, GenericController $genericController)
    {
        $this->mediaService = $mediaService;
        $this->em = $em;
        $this->genericController = $genericController;
    }

    /**
     * Displays the media creation page for Media.create route
     * @param int $trickId
     * @param int $mediaType
     * @param Request $request
     * @return Response
     */
    public function create(int $trickId, int $mediaType, Request $request): Response
    {
        $media = new Media();
        $form = $this->createForm(MediaFormType::class, $media, array('type_of_media' => $mediaType));
        $form->handleRequest($request);
        $serviceReturn = $this->mediaService->createMedia($trickId, $mediaType, $form);
        return $this->genericController->controllerReturn($serviceReturn);
    }

    /**
     * Deletes a media
     * @param int $mediaId the id of the media to be deleted
     * @return Response
     */
    public function delete(int $mediaId): Response
    {
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $media = $mediaRepository->find($mediaId);
        $trickId = $media->getTrick()->getId();
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($media);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez supprimé un media');
        return $this->redirectToRoute('Trick.show', [
            'trickId' => $trickId]);
    }

    /**
     * Updates a media url and the fact that an image is or is not the main media of a trick
     * @param int $mediaId the id of the media to be updated
     * @param Request $request
     * @return Response
     */
    public function update(int $mediaId, Request $request): Response
    {
        $mediaToUpdate = $this->em->find(Media::class, $mediaId);
        $type = $mediaToUpdate->getMediaType()->getId();
        $was_main = $mediaToUpdate->getIsMain();
        $form = $this->createForm(MediaFormType::class, $mediaToUpdate, array('type_of_media' => $type));
        $form->handleRequest($request);
        $serviceReturn = $this->mediaService->update($mediaId, $form,$mediaToUpdate,$was_main);
        return $this->genericController->controllerReturn($serviceReturn);
    }
}
