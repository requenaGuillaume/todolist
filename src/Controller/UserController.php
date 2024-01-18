<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Interfaces\FormCreateEditInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController implements FormCreateEditInterface
{
    public function __construct(private EntityManagerInterface $em)
    {

    }

    #[Route('/users', name: 'user_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(UserRepository $userRepository): Response
    {
        return $this->render('user/list.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/users/create', name: 'user_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, UserPasswordHasherInterface $hasher): RedirectResponse|Response
    {
        return $this->formProcess($request, $hasher, user: null);
    }

    #[Route('/users/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(User $user, Request $request, UserPasswordHasherInterface $hasher): RedirectResponse|Response
    {
        return $this->formProcess($request, $hasher, $user);
    }

    private function formProcess(
        Request $request,
        UserPasswordHasherInterface $hasher,
        ?User $user = null
    ): RedirectResponse|Response {
        $user = $user ?? new User();

        $data = $this->initFormProcessData($user);
        $mode = $data['mode'];

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $user->getPassword()));

            $role = $form->get('roles')->getNormData();

            if(!in_array($role, User::ROLES_LIST)) {
                $this->addFlash('danger', 'Role invalid');
                return $this->redirectToRoute($data['redirectRouteIfError']);
            }

            $user->setRoles([$role]);

            if($mode === self::FORM_MODE_CREATE) {
                $this->em->persist($user);
            }

            $this->em->flush();
            $this->addFlash('success', $data['successMessage']);

            return $this->redirectToRoute('user_list');
        }

        $data['renderArguments']['form'] = $form->createView();

        return $this->render("user/$mode.html.twig", $data['renderArguments']);
    }

    private function initFormProcessData(User $user): array
    {
        $data = [
            'renderArguments' => [],
            'mode' => self::FORM_MODE_CREATE,
            'successMessage' => 'L\'utilisateur a bien été ajouté.',
            'redirectRouteIfError' => 'user_create'
        ];

        if($user->getId()) {
            $data['mode'] = self::FORM_MODE_EDIT;
            $data['successMessage'] = 'L\'utilisateur a bien été modifié.';
            $data['redirectRouteIfError'] = 'user_edit';
            $data['renderArguments']['user'] = $user;
        }

        return $data;
    }
}
