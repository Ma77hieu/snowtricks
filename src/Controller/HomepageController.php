<?php

namespace App\Controller;

use App\Entity\Trick;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomepageController extends AbstractController
{
    public function index(): Response
    {
        $trickRepository = $this->getDoctrine()->getRepository(Trick::class);
        $tricks = $trickRepository->findAll();
        return $this->render('tricks/tricksList.html.twig',['tricks'=>$tricks]);
    }

}
