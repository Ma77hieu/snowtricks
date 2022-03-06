<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GenericController extends AbstractController
{
    /**
     * Generic function used by the various functions of the controller to redirect or return the response
     * Also returns the datas to be sent to the templates and the flash message if one is needed
     * @param $input
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function controllerReturn($input)
    {
        $returnType = $input['returnType'];
        $path = $input['path'];
        $flashType = $input['flashType'];
        $flashMessage = $input['flashMessage'];
        $data = $input['data'];
        if ($flashMessage) {
            $this->addFlash($flashType, $flashMessage);
        }
        if ($returnType === 'render') {
            return $this->render($path, $data);
        }
        if ($returnType === 'redirect') {
            return $this->redirectToRoute($path, $data);
        } else {
            $this->addFlash("danger", "une erreur interne est survenue, vous avez été redirigé vers la page d'accueil");
            return $this->redirectToRoute('index');
        }
    }
}
