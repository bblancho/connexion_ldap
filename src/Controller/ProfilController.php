<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\Password;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route('/profil', name: 'profil_')]
class ProfilController extends AbstractController
{
    
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Security("is_granted('ROLE_ADMIN')")]
    #[Route('/', name: 'index', methods: ["GET"])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('profil/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Security("(is_granted('ROLE_USER') and user === currentUser) or is_granted('ROLE_ADMIN') ")]
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ["GET"])]
    public function show(User $currentUser): Response
    {
        if ( !$currentUser ) {
            throw $this->createNotFoundException('Aucun utilisateur trouvé.') ;
        }

        return $this->render('profil/show.html.twig', [
            'user' => $currentUser,
        ]);
    }

    /**
     * @param Request $request
     * @param User $user
     * 
     * @return Response
     */
    #[Security("(is_granted('ROLE_USER') and user === currentUser) or is_granted('ROLE_ADMIN') ")]
    #[Route('/{id}/edite-compte', name: 'edit',  methods: ['GET', 'POST'])]
    public function edit(Request $request, User $currentUser, UserRepository $userRepository): Response
    {        
        if ( !$currentUser ) {
            throw $this->createNotFoundException('Aucun utilisateur trouvé.') ;
        }

        $form = $this->createForm(UserType::class, $currentUser);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {
            $userRepository->save($currentUser, true);
            
            $this->addFlash('success', 'Vos données ont bien été mise à jour.');

            if ( $this->isGranted('ROLE_ADMIN') ) {
                return $this->redirectToRoute('profil_index', [], Response::HTTP_SEE_OTHER);
            } 

            return $this->redirectToRoute('profil_show', ['id' => $currentUser->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profil/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $currentUser,
        ]);
    }

    /**
     * @param Request $request
     * @param User $user
     * @param UserRepository $userRepository
     * @param UserPasswordHasherInterface $encoder
     * 
     * @return Response
     */
    #[Security("(is_granted('ROLE_USER') and user === currentUser) or is_granted('ROLE_ADMIN') ")]
    #[Route('/{id}/newpass', name: 'edit_pass', methods: ['GET', 'POST'])]
    public function editPassword(Request $request, User $currentUser,UserRepository $userRepository, UserPasswordHasherInterface $encoder): Response
    {
        if ( !$currentUser ) {
            throw $this->createNotFoundException('Aucun utilisateur trouvé.') ;
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pass = new Password();
        $form = $this->createForm(ChangePassType::class, $pass);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {

            $checkPass = $encoder->isPasswordValid($currentUser, $pass->getOldPass());

            if ($checkPass === true) {
                $encodedPassword = $encoder->hashPassword(
                    $currentUser,
                    $form->get('plainPassword')->getData()
                );

                $userRepository->upgradePassword($currentUser, $encodedPassword);
                $this->addFlash('success', "Votre mot de passe a bien été mis à jour.");

            } else {
                $this->addFlash('danger', "Erreur lors de la mise à jour du mot de passe.");
            }

            return $this->redirectToRoute('edit_pass', ['id' => $currentUser->getId(),]);
        }

        return $this->render('profil/pass.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param User $user
     * @param UserRepository $userRepository
     * @param UserPasswordHasherInterface $encoder
     * 
     * @return Response
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ( $this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token')) ) {
            
            $userRepository->remove($user, true);
            
            $this->addFlash('success', "L'utilisateur a bien été supprimé.");
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }

}
