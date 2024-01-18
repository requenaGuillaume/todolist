<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Interfaces\FormCreateEditInterface;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController implements FormCreateEditInterface
{
    public function __construct(private EntityManagerInterface $em)
    {

    }

    #[Route('/tasks', name: 'task_list', methods: ['GET'])]
    public function list(TaskRepository $taskRepository): Response
    {
        return $this->render('task/list.html.twig', ['tasks' => $taskRepository->findAll()]);
    }

    #[Route('/tasks/create', name: 'task_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): RedirectResponse|Response
    {
        return $this->formProcess($request, task: null);
    }

    #[Route('/tasks/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Task $task, Request $request): RedirectResponse|Response
    {
        return $this->formProcess($request, $task);
    }

    #[Route('/tasks/{id}/toggle', name: 'task_toggle', methods: ['GET'])]
    public function toggle(Task $task): RedirectResponse
    {
        $task->toggle(!$task->isDone());
        $this->em->flush();

        $this->addFlash('success', "La tâche {$task->getTitle()} a bien été marquée comme faite.");

        return $this->redirectToRoute('task_list');
    }

    #[Route('/tasks/{id}/delete', name: 'task_delete', methods: ['GET'])]
    public function delete(Task $task, UserRepository $userRepository): RedirectResponse
    {
        $anonymous = $userRepository->findOneBy(['username' => 'anonyme']);

        $taskOwner = $task->getUser();

        $canDeleteAnonymousTasks = $taskOwner === $anonymous && $this->isGranted('ROLE_ADMIN');

        if(!$canDeleteAnonymousTasks && $taskOwner !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'êtes pas propriétaire de cette tache, vous ne pouvez donc pas la supprimer.');
            return $this->redirectToRoute('task_list');
        }

        $this->em->remove($task);
        $this->em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }

    private function formProcess(Request $request, ?Task $task = null): RedirectResponse|Response
    {
        if(!$task) {
            $task = new Task();
            $mode = self::FORM_MODE_CREATE;
        } else {
            $renderArguments['task'] = $task;
            $mode = self::FORM_MODE_EDIT;
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $task->setUser($user);
            $successMessage = 'La tâche a bien été modifiée.';

            if(!$task->getId()) {
                $this->em->persist($task);
                $successMessage = 'La tâche a été bien été ajoutée.';
            }

            $this->em->flush();
            $this->addFlash('success', $successMessage);

            return $this->redirectToRoute('task_list');
        }

        $renderArguments['form'] = $form->createView();

        return $this->render("task/$mode.html.twig", $renderArguments);
    }

}
