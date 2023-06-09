<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GithubController extends AbstractController
{
    public function __construct(private ClientRegistry $clientRegistry)
    {
        
    }
    
    #[Route('/connexion/github', name: 'connect_github')]
    public function connectAction()
    {
        //dd( $this->clientRegistry->getClient('github') );

        return $this->clientRegistry
            ->getClient('github') // the name use in config/packages/knpu_oauth2_client.yaml 
            ->redirect([], []);  // 'public_profile', 'email' ,  the scopes you want to access
        // $api_key = $this->getParameter('app.github_client_id') ;
        // $url="0";

        // return RedirectResponse("https://github.com/login/oauth/authorize?client_id={$api_key}".$url) ;
    }

    /**
     * After going to Google, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/connexion/github/check', name: 'connect_github_check')]
    public function connectCheckAction()
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator

        $client = $this->clientRegistry->getClient('github');
        
        if( !$client ) {
            return new JsonResponse( array('status' => false, 'message' => "User not found !"));
        }else{
            return $this->redirectToRoute('app_home');
        }
        
    }
}
