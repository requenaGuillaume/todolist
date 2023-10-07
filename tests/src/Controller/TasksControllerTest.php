<?php

namespace Tests\App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class TasksControllerTest extends WebTestCase
{
    public function testListEmpty(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/tasks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_list', $client->getRequest()->attributes->get('_route'));
        $this->assertEquals(0, $crawler->filter('h1')->count());
        $this->assertEquals(1, $crawler->filter('div[class="alert alert-warning mt-3"]')->count());
        $this->assertSelectorTextContains('div[class="alert alert-warning mt-3"]', 'Il n\'y a pas encore de tâche enregistrée.');
    }

    public function testListNotEmpty(): void
    {
        $client = static::createClient();

        $task = new Task();
        $task->setTitle('test')
            ->setContent('test test test')
            ->setCreatedAt(new \DateTimeImmutable());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($task);
        $em->flush();       

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/tasks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_list', $client->getRequest()->attributes->get('_route'));
        $this->assertEquals(0, $crawler->filter('h1')->count());
        $this->assertEquals(1, $crawler->filter('h4')->count());

        $em->remove($task);
        $em->flush();
    }

    public function testCreateSuccess(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/tasks/create');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_create', $client->getRequest()->attributes->get('_route'));

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'task' => [
                'title' => 'test',
                'content' => 'test test test'
            ]
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'Superbe ! La tâche a été bien été ajoutée.');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $newTask = $taskRepository->findOneBy(['title' => 'test']);

        $this->assertEquals('test', $newTask->getTitle());
        $this->assertEquals('test test test', $newTask->getContent());
        $this->assertEquals('task_list', $client->getRequest()->attributes->get('_route'));
    }

    public function testCreateTaskValuesInvalid(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/tasks/create');

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'task' => [
                'title' => '',
                'content' => ''
            ]
        ]);

        $client->submit($form);

        $this->assertSelectorTextContains('form div:nth-of-type(1) li', 'Vous devez saisir un titre.');
        $this->assertSelectorTextContains('form div:nth-of-type(2) li', 'Vous devez saisir du contenu.');       
    }

    public function testEditSuccess(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', "/tasks/{$task->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_edit', $client->getRequest()->attributes->get('_route'));

        $form = $crawler->selectButton('Modifier')->form();

        $form->setValues([
            'task' => [
                'title' => 'testEdit',
                'content' => 'testEdit'
            ]
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'La tâche a bien été modifiée.');

        $updatedTask = $taskRepository->findOneBy(['title' => 'testEdit']);

        $this->assertEquals($task->getId(), $updatedTask->getId());
        $this->assertNotEquals($task->getTitle(), $updatedTask->getTitle());
        $this->assertNotEquals($task->getContent(), $updatedTask->getContent());
        $this->assertEquals('task_list', $client->getRequest()->attributes->get('_route'));
    }

    public function testEditFails(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'testEdit']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', "/tasks/{$task->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_edit', $client->getRequest()->attributes->get('_route'));

        $form = $crawler->selectButton('Modifier')->form();

        $form->setValues([
            'task' => [
                'title' => '',
                'content' => ''
            ]
        ]);

        $client->submit($form);

        $updatedTask = $taskRepository->findOneBy(['title' => 'testEdit']);

        $this->assertSelectorTextContains('form div:nth-of-type(1) li', 'Vous devez saisir un titre.');
        $this->assertSelectorTextContains('form div:nth-of-type(2) li', 'Vous devez saisir du contenu.');

        $this->assertEquals($task->getId(), $updatedTask->getId());
        $this->assertEquals($task->getTitle(), $updatedTask->getTitle());
        $this->assertEquals($task->getContent(), $updatedTask->getContent());
        $this->assertEquals('task_edit', $client->getRequest()->attributes->get('_route'));
    }

    public function testToggle(): void
    {
        $client = $this->createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'testEdit']);
        $task->isDone(false);
        
        $client->loginUser($testUser);

        $client->request('GET', "/tasks/{$task->getId()}/toggle");
        $client->followRedirect();

        $updatedTask = $taskRepository->findOneBy(['title' => 'testEdit']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($updatedTask->isDone());
        $this->assertSelectorTextContains('div.alert.alert-success', "Superbe ! La tâche {$task->getTitle()} a bien été marquée comme faite."); 
        $this->assertEquals('task_list', $client->getRequest()->attributes->get('_route'));
    }

    public function testDelete(): void
    {
        $client = $this->createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'testEdit']);

        $client->request('GET', "/tasks/{$task->getId()}/delete");
        $client->followRedirect();

        $deletedTask = $taskRepository->findOneBy(['title' => 'testEdit']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('div.alert.alert-success', "Superbe ! La tâche a bien été supprimée.");
        $this->assertNull($deletedTask);
        $this->assertEquals('task_list', $client->getRequest()->attributes->get('_route'));
    }

}
