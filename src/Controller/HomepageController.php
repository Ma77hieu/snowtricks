<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\Repository\PropertyRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomepageController extends AbstractController
{
    public function index(): Response
    {
        $list=[1,1,1,1,1,1,1,1,1,1,1,1,1,1,1];
        return $this->render('tricks/tricksList.html.twig',['list'=>$list]);
    }

}
