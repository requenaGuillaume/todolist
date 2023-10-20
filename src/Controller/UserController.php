<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
        
    }

    #[Route('/users', name: 'user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): Response
    {
        return $this->render('user/list.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/users/create', name: 'user_create', methods: ['GET', 'POST'])]
    public function create(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($password);

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', "L'utilisateur a bien été ajouté.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    // TODO edition de roles ne marche pas - l'ancien role (admin) reste
    #[Route('/users/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($password);
// dd($form->getData());


            //  CHECKBOX - marche - dd dans le UserController.php (en DB admin array, user json ??)
            // UserController.php on line 64:
            // App\Entity\User {#580 ▼
            //   -id: 3
            //   -username: "coco"
            //   -password: "$2y$13$NrJXrLZS5m6QStD16yXd7u2YlqI1RkV.nt32.HkqARLjgR65pw3WO"
            //   -email: "fdfd@fd.fd"
            //   -roles: array:1 [▼
            //     0 => "ROLE_ADMIN"
            //   ]
            // }

            // SELECT - Ne marche pas - dd dans le UserType.php
            // UserType.php on line 50:
            //     array:4 [▼
            //     "username" => "coco"
            //     "password" => array:2 [▶]
            //     "email" => "fdfd@fd.fd"
            //     "roles" => array:1 [▼
            //         0 => "ROLE_USER"
            //     ]
            // ]

            // // Supprimez les anciens rôles
            // $user->setRoles([]);

            // // Ajoutez le rôle sélectionné
            // $selectedRole = $form->get('roles')->getData();
            // $user->setRoles($selectedRole);

            $this->em->flush();

            $this->addFlash('success', "L'utilisateur a bien été modifié");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }
}
