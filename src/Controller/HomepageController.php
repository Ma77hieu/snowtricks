<?php

namespace App\Controller;

use App\Entity\Media;
use App\Entity\Trick;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomepageController extends AbstractController
{
    /**
     * Returns the homepage, can also return the additionnal tricks' cards when
     * the user clicks on "see more tricks"
     * @param int $page used for pagination, each page represents a batch of tricks' cards
     * @return Response
     */
    public function index(int $page = 1): Response
    {
        $trickRepository = $this->getDoctrine()->getRepository(Trick::class);
        $tricks = $trickRepository->getTricksFromPage($page);
        $mainMedias = $this->getAllMainMedias();
        $mainMediasId = $this->getMediasIds($mainMedias);
        if ($page == 1) {
            return $this->render('tricks/tricksList.html.twig',
                [
                    'tricks' => $tricks,
                    'mainMedias' => $mainMedias,
                    'mainMediasId' => $mainMediasId,
                ]
            );
        } else {
            if ($tricks != null) {
                return $this->render('tricks/_tricksCards.html.twig',
                    [
                        'tricks' => $tricks,
                        'mainMedias' => $mainMedias,
                        'mainMediasId' => $mainMediasId,
                    ]
                );
            } else {
                return $this->render('tricks/_noMoreTricks.html.twig');
            }
        }
    }

    public function getAllMainMedias(): array
    {
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
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
