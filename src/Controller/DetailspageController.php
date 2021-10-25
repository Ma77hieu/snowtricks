<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\Repository\PropertyRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DetailspageController extends AbstractController
{
    public function show(): Response
    {
        $tricks=1;
        $medias=[1,1,1,1,1];
        $videos=null;//tests purpose
        $images=1;//tests purpose
        $tags=[1,1,1];//tests purpose
        $comments=[1,1,1];//tests purpose
        return $this->render('tricks/tricksDetails.html.twig',[
            'tricks'=>$tricks,
            'medias'=>$medias,
            'videos'=>$videos,
            'images'=>$images,
            'tags'=>$tags,
            'comments'=>$comments]);
    }

}
