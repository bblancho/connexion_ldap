<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\JsonResponse;

class GoogleController extends AbstractController
{
    public function __construct(private ClientRegistry $clientRegistry)
    {
        
    }
    
    #[Route('/connexion/google', name: 'connect_google')]
    public function connectAction()
    {
        //Redirect to google
        // $clientRegistry = $this->get('knpu.oauth2.registry'); 
        return $this->clientRegistry
            ->getClient('google') // the name use in config/packages/knpu_oauth2_client.yaml 
            ->redirect([], []);  // 'public_profile', 'email' ,  the scopes you want to access
    }

    /**
     * After going to Google, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/connexion/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator

        $client = $this->clientRegistry->getClient('google') ;

        if( !$client ) {
            return new JsonResponse( array('status' => false, 'message' => "User not found !")) ;
        }else{
            
            return $this->redirectToRoute('app_home') ;
        }
        // try {
        //     $user = $client->fetchUser();
        //     // do something with all this new power!
        //     // e.g. $name = $user->getFirstName();
        //     dd($user); die;
        
        // } catch (IdentityProviderException $e) {
        //     // something went wrong!
        //     // probably you should return the reason to the user
        //     var_dump($e->getMessage()); die;
        // }
    }
}
