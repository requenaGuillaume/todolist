<?php

namespace Tests\App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class UserControllerTest extends WebTestCase
{
    private User $testUser;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->testUser = new User();
        $this->testUser->setUsername('test')
            ->setEmail('test@test.test')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword('$2y$04$Gy1WKJfRNPtDjynITKF9o.8z5hMtxC8wA0m8wTBR2LBhGUjcC4tOC');

        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->persist($this->testUser);
        $this->em->flush();

        $this->userRepository = $container->get(UserRepository::class);
        $this->client->loginUser($this->testUser);
    }

    public function tearDown(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        // $connection->executeQuery('TRUNCATE TABLE user');
        $connection->executeQuery('DELETE FROM user');

    }

    public function testList(): void
    {        
        $crawler = $this->client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_list', $this->client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals('Liste des utilisateurs', $crawler->filter('h1')->text());
    }

    public function testCreateSuccess(): void
    {
        $crawler = $this->client->request('GET', '/users/create');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_create', $this->client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals('Créer un utilisateur', $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'user' => [
                'username' => 'toto',
                'password' => ['first' => 'password', 'second' => 'password'],
                'email' => 'toto@gmail.com'
            ]
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'Superbe ! L\'utilisateur a bien été ajouté.');

        $newUser = $this->userRepository->findOneBy(['email' => 'toto@gmail.com']);

        $this->assertEquals('toto@gmail.com', $newUser->getEmail());
        $this->assertEquals('toto', $newUser->getUserName());
        $this->assertEquals('user_list', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testCreateUserAlreadyExist(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/users/create');

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'user' => [
                'username' => 'test',
                'password' => [
                    'first' => '$2y$04$Gy1WKJfRNPtDjynITKF9o.8z5hMtxC8wA0m8wTBR2LBhGUjcC4tOC', 
                    'second' => '$2y$04$Gy1WKJfRNPtDjynITKF9o.8z5hMtxC8wA0m8wTBR2LBhGUjcC4tOC'
                ],
                'email' => 'test@test.test'
            ]
        ]);

        $this->client->submit($form);

        $this->assertSelectorTextContains('li', 'This value is already used.');
        $this->assertEquals('user_create', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testCreateUserValuesInvalid(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/users/create');

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'user' => [
                'username' => '',
                'password' => ['first' => '', 'second' => ''],
                'email' => 'totogmail.com'
            ]
        ]);

        $this->client->submit($form);

        $this->assertSelectorTextContains('form div:nth-of-type(1) li', 'Vous devez saisir un nom d\'utilisateur.');
        $this->assertSelectorTextContains('form div:nth-of-type(2) li', 'Vous devez saisir un mot de passe.');
        $this->assertSelectorTextContains('form div:nth-of-type(3) li', 'Le format de l\'adresse n\'est pas correcte.');        

        $form->setValues([
            'user' => [
                'username' => 'dummy',
                'password' => ['first' => 'toto', 'second' => 'tata'],
                'email' => 'dummy@gmail.com'
            ]
        ]);

        $this->client->submit($form);

        $this->assertSelectorTextContains('li', 'Les deux mots de passe doivent correspondre.');
        $this->assertEquals('user_create', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testEditSuccess(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', "/users/{$this->testUser->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_edit', $this->client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals("Modifier {$this->testUser->getUsername()}", $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Modifier')->form();

        $form->setValues([
            'user' => [
                'username' => 'testEdit',
                'password' => ['first' => 'password', 'second' => 'password'],
                'email' => 'testEdit@test.test'
            ]
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'Superbe ! L\'utilisateur a bien été modifié');

        $updatedUser = $this->userRepository->find($this->testUser->getId());

        $this->assertEquals($this->testUser->getId(), $updatedUser->getId());
        $this->assertNotEquals($this->testUser->getUsername(), $updatedUser->getUsername());
        $this->assertNotEquals($this->testUser->getEmail(), $updatedUser->getEmail());
        $this->assertEquals('user_list', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testEditFails(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', "/users/{$this->testUser->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_edit', $this->client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals("Modifier {$this->testUser->getUsername()}", $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Modifier')->form();

        $form->setValues([
            'user' => [
                'username' => '',
                'password' => ['first' => 'newPassword', 'second' => 'oldPassword'],
                'email' => 'testEdittest.test'
            ]
        ]);

        $this->client->submit($form);

        $updatedUser = $this->userRepository->find($this->testUser->getId());

        $this->assertSelectorTextContains('form div:nth-of-type(1) li', 'Vous devez saisir un nom d\'utilisateur.');
        $this->assertSelectorTextContains('form div:nth-of-type(2) li', 'Les deux mots de passe doivent correspondre.');
        $this->assertSelectorTextContains('form div:nth-of-type(3) li', 'Le format de l\'adresse n\'est pas correcte.'); 

        $this->assertEquals($this->testUser->getId(), $updatedUser->getId());
        $this->assertEquals($this->testUser->getUsername(), $updatedUser->getUsername());
        $this->assertEquals($this->testUser->getEmail(), $updatedUser->getEmail());
        $this->assertEquals('user_edit', $this->client->getRequest()->attributes->get('_route'));
    }

}
