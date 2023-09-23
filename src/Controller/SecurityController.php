<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// TODO Changer le login avec le make:user ?
class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function loginAction(Request $request): Response
    {
        // $authenticationUtils = $this->get('security.authentication_utils');

        // $error = $authenticationUtils->getLastAuthenticationError();
        // $lastUsername = $authenticationUtils->getLastUsername();

        // return $this->render('security/login.html.twig', array(
        //     'last_username' => $lastUsername,
        //     'error'         => $error,
        // ));
        return new Response('TODO');
    }

    #[Route('/login_check', name: 'login_check')]
    public function loginCheck()
    {
        // This code is never executed.
    }

    #[Route('/logout', name: 'logout')]
    public function logoutCheck()
    {
        // This code is never executed.
    }
}
