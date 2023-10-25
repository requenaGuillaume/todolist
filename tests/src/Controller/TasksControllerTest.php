<?php

namespace Tests\App\Controller;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use App\Repository\TaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class TasksControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private TaskRepository $taskRepository;
    private User $testUser;
    
    public function setUp(): void
    {
        $container = static::getContainer();
        $this->taskRepository = $container->get(TaskRepository::class);
        $this->em = $container->get(EntityManagerInterface::class);

        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $this->testUser = new User();
        $this->testUser->setUsername('test')
            ->setEmail('test@test.test')
            ->setPassword('$2y$04$Gy1WKJfRNPtDjynITKF9o.8z5hMtxC8wA0m8wTBR2LBhGUjcC4tOC');
        
        $this->em->persist($this->testUser);
        $this->em->flush();

        $this->client->loginUser($this->testUser);
    }

    public function tearDown(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $connection->executeQuery('DELETE FROM task');
        $connection->executeQuery('DELETE FROM user');
    }

    public function testListEmpty(): void
    {        
        $crawler = $this->client->request('GET', '/tasks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_list', $this->client->getRequest()->attributes->get('_route'));
        $this->assertEquals(0, $crawler->filter('h1')->count());
        $this->assertEquals(1, $crawler->filter('div[class="alert alert-warning mt-3"]')->count());
        $this->assertSelectorTextContains('div[class="alert alert-warning mt-3"]', 'Il n\'y a pas encore de tâche enregistrée.');
    }
    
    public function testListNotEmpty(): void
    {
        $this->createTestTask($this->testUser);    
        $crawler = $this->client->request('GET', '/tasks');
    
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_list', $this->client->getRequest()->attributes->get('_route'));
        $this->assertEquals(0, $crawler->filter('h1')->count());
        $this->assertEquals(1, $crawler->filter('h4')->count());
    }
    
    public function testCreateSuccess(): void
    {
        $crawler = $this->client->request('GET', '/tasks/create');
    
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_create', $this->client->getRequest()->attributes->get('_route'));
    
        $form = $crawler->selectButton('Ajouter')->form();
    
        $form->setValues([
            'task' => [
                'title' => 'test',
                'content' => 'test test test'
            ]
        ]);
    
        $this->client->submit($form);
        $this->client->followRedirect();
    
        $this->assertSelectorTextContains('div.alert.alert-success', 'Superbe ! La tâche a été bien été ajoutée.');
    
        $newTask = $this->taskRepository->findOneBy(['title' => 'test']);
    
        $this->assertEquals('test', $newTask->getTitle());
        $this->assertEquals('test test test', $newTask->getContent());
        $this->assertEquals('task_list', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testCreateTaskValuesInvalid(): void
    {
        $crawler = $this->client->request('GET', '/tasks/create');
    
        $form = $crawler->selectButton('Ajouter')->form();
    
        $form->setValues([
            'task' => [
                'title' => '',
                'content' => ''
            ]
        ]);
    
        $this->client->submit($form);
    
        $this->assertSelectorTextContains('form div:nth-of-type(1) li', 'Vous devez saisir un titre.');
        $this->assertSelectorTextContains('form div:nth-of-type(2) li', 'Vous devez saisir du contenu.');       
    }

    public function testEditSuccess(): void
    {
        $now = new DateTimeImmutable();
        $task = $this->createTestTask($this->testUser, $now); 
 
        $crawler = $this->client->request('GET', "/tasks/{$task->getId()}/edit");
    
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_edit', $this->client->getRequest()->attributes->get('_route'));
    
        $form = $crawler->selectButton('Modifier')->form();
    
        $form->setValues([
            'task' => [
                'title' => 'testEdit',
                'content' => 'testEdit'
            ]
        ]);
    
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'La tâche a bien été modifiée.');
    
        $updatedTask = $this->taskRepository->find($task->getId());

        $this->assertEquals($task->getId(), $updatedTask->getId());
        $this->assertNotEquals($task->getTitle(), $updatedTask->getTitle());
        $this->assertNotEquals($task->getContent(), $updatedTask->getContent());
        $this->assertEquals($now->format('Y-m-d'), $updatedTask->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('task_list', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testEditFails(): void
    {
        $task = $this->createTestTask($this->testUser);

        $crawler = $this->client->request('GET', "/tasks/{$task->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('task_edit', $this->client->getRequest()->attributes->get('_route'));

        $form = $crawler->selectButton('Modifier')->form();

        $form->setValues([
            'task' => [
                'title' => '',
                'content' => ''
            ]
        ]);

        $this->client->submit($form);

        $updatedTask = $this->taskRepository->find($task->getId());

        $this->assertSelectorTextContains('form div:nth-of-type(1) li', 'Vous devez saisir un titre.');
        $this->assertSelectorTextContains('form div:nth-of-type(2) li', 'Vous devez saisir du contenu.');

        $this->assertEquals($task->getId(), $updatedTask->getId());
        $this->assertEquals($task->getTitle(), $updatedTask->getTitle());
        $this->assertEquals($task->getContent(), $updatedTask->getContent());
        $this->assertEquals('task_edit', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testToggle(): void
    {
        $task = $this->createTestTask($this->testUser);

        $this->assertFalse($task->isDone());

        $this->client->request('GET', "/tasks/{$task->getId()}/toggle");
        $this->client->followRedirect();

        $this->em->clear();
        $updatedTask = $this->taskRepository->find($task->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($updatedTask->isDone());
        $this->assertSelectorTextContains('div.alert.alert-success', "Superbe ! La tâche {$task->getTitle()} a bien été marquée comme faite."); 
        $this->assertEquals('task_list', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testDelete(): void
    {
        $task = $this->createTestTask($this->testUser);

        $this->client->request('GET', "/tasks/{$task->getId()}/delete");
        $this->client->followRedirect();

        $this->em->clear();
        $deletedTask = $this->taskRepository->find($task->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('div.alert.alert-success', "Superbe ! La tâche a bien été supprimée.");
        $this->assertNull($deletedTask);
        $this->assertEquals('task_list', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testDeleteFail(): void
    {    
        $user = new User();
        $user->setEmail('dummy@dummy.dummy')
            ->setUsername('dummy')
            ->setPassword('pass');

        $this->em->persist($user);
        $this->em->flush();

        $task = $this->createTestTask($user);

        $this->client->request('GET', "/tasks/{$task->getId()}/delete");
        $this->client->followRedirect();

        $this->em->clear();
        $deletedTask = $this->taskRepository->find($task->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('div.alert.alert-danger', "Oops ! Vous n'êtes pas propriétaire de cette tache, vous ne pouvez donc pas la supprimer.");
        $this->assertNotNull($deletedTask);
        $this->assertEquals('task_list', $this->client->getRequest()->attributes->get('_route'));
    }

    private function createTestTask(
        User $user,
        DateTimeImmutable $dateTime = new DateTimeImmutable(),  
        bool $isDone = false
    ): Task
    {
        $task = new Task();

        $task->setTitle('test')
            ->setContent('test test test')
            ->setCreatedAt($dateTime)
            ->setUser($user)
            ->isDone($isDone);

        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

}
