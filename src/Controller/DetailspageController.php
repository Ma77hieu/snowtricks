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
        $user_logged_in=null;
        $medias=[1,1,1,1,1];
        $videos=1;//tests purpose
        $images=null;//tests purpose
        $tags=[1,1,1];//tests purpose
        $comments=[1,1,1];//tests purpose
        $edition_mode=false;
        return $this->render('tricks/tricksDetails.html.twig',[
            'tricks'=>$tricks,
            'medias'=>$medias,
            'videos'=>$videos,
            'images'=>$images,
            'tags'=>$tags,
            'comments'=>$comments,
            'user_logged_in'=>$user_logged_in,
            'edition_mode'=>$edition_mode]);
    }

    public function edit(): Response
    {
        $tricks=1;
        $user_logged_in=null;
        $medias=[1,1,1,1,1];
        $videos=1;//tests purpose
        $images=null;//tests purpose
        $tags=[1,1,1];//tests purpose
        $comments=[1,1,1];//tests purpose
        $edition_mode=true;
        $description="tecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores e aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?";
        $groupes=['Groupe A','Groupe B','Groupe C'];
        return $this->render('tricks/tricksDetails.html.twig',[
            'tricks'=>$tricks,
            'medias'=>$medias,
            'videos'=>$videos,
            'images'=>$images,
            'tags'=>$tags,
            'comments'=>$comments,
            'user_logged_in'=>$user_logged_in,
            'edition_mode'=>$edition_mode,
            'description'=>$description,
            'groups'=>$groupes]);
    }

}
