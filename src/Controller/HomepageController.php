<?php

namespace App\Controller;

use App\Entity\Media;
use App\Entity\Trick;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomepageController extends AbstractController
{
    public function index(): Response
    {
        $trickRepository = $this->getDoctrine()->getRepository(Trick::class);
        $tricks = $trickRepository->findAll();
        $mainMedias=$this->getAllMainMedias();
        $mainMediasId=$this->getMediasIds($mainMedias);
        return $this->render('tricks/tricksList.html.twig',
            [
                'tricks'=>$tricks,
                'mainMedias'=>$mainMedias,
                'mainMediasId'=>$mainMediasId,
            ]
        );
    }

    public function getAllMainMedias(): array
    {
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $allMainMedias = $mediaRepository->findBy(['isMain'=>true]);
        return($allMainMedias);
    }

    public function getMediasIds($medias): array
    {
        $mediasIds = [];

        foreach($medias as $media){
            array_push($mediasIds,$media->getTrick()->getId());
        }
        return($mediasIds);
    }


}
