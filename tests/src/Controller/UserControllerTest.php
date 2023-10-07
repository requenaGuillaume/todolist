<?php

namespace Tests\App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class UserControllerTest extends WebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_list', $client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals('Liste des utilisateurs', $crawler->filter('h1')->text());
    }

    public function testCreateSuccess(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/users/create');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_create', $client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals('Créer un utilisateur', $crawler->filter('h1')->text());

        // If user already exist, the test will fail, so check if exist and delete if so
        // TODO - not like this ? use mock + can i do it in other way ?
        // if not, use database test ?
        $newUser = $userRepository->findOneBy(['email' => 'toto@gmail.com']);

        if($newUser){
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->remove($newUser);
            $em->flush();
        }

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'user' => [
                'username' => 'toto',
                'password' => ['first' => 'password', 'second' => 'password'],
                'email' => 'toto@gmail.com'
            ]
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'Superbe ! L\'utilisateur a bien été ajouté.');

        $newUser = $userRepository->findOneBy(['email' => 'toto@gmail.com']);

        $this->assertEquals('toto@gmail.com', $newUser->getEmail());
        $this->assertEquals('toto', $newUser->getUserName());
        $this->assertEquals('user_list', $client->getRequest()->attributes->get('_route'));
    }

    public function testCreateUserAlreadyExist(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/users/create');

        $newUser = $userRepository->findOneBy(['email' => 'toto@gmail.com']);

        // Result of condition will depend on the previous test
        if(!$newUser){
            $newUser = new User();
            $newUser->setUsername('toto')
                ->setEmail('toto@gmail.com')
                ->setPassword('password'); // not hashed but doesn't matter

            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->flush();
        }

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'user' => [
                'username' => 'toto',
                'password' => ['first' => 'password', 'second' => 'password'],
                'email' => 'toto@gmail.com'
            ]
        ]);

        $client->submit($form);

        $this->assertSelectorTextContains('li', 'This value is already used.');
        $this->assertEquals('user_create', $client->getRequest()->attributes->get('_route'));
    }

    public function testCreateUserValuesInvalid(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/users/create');

        $form = $crawler->selectButton('Ajouter')->form();

        $form->setValues([
            'user' => [
                'username' => '',
                'password' => ['first' => '', 'second' => ''],
                'email' => 'totogmail.com'
            ]
        ]);

        $client->submit($form);

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

        $client->submit($form);

        // Single line add two assertions ? Instead of one
        $this->assertSelectorTextContains('li', 'Les deux mots de passe doivent correspondre.');
        $this->assertEquals('user_create', $client->getRequest()->attributes->get('_route'));
    }

    public function testEditSuccess(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);
        $userName = $testUser->getUsername();

        $client->loginUser($testUser);
        $crawler = $client->request('GET', "/users/{$testUser->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_edit', $client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals("Modifier $userName", $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Modifier')->form();

        $form->setValues([
            'user' => [
                'username' => 'testEdit',
                'password' => ['first' => 'password', 'second' => 'password'],
                'email' => 'testEdit@test.test'
            ]
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'Superbe ! L\'utilisateur a bien été modifié');

        $updatedUser = $userRepository->findOneBy(['email' => 'testEdit@test.test']);

        $this->assertEquals($testUser->getId(), $updatedUser->getId());
        $this->assertNotEquals($testUser->getUsername(), $updatedUser->getUsername());
        $this->assertNotEquals($testUser->getEmail(), $updatedUser->getEmail());
        $this->assertEquals('user_list', $client->getRequest()->attributes->get('_route'));
    }

    // /!\ TODO - remove after setup() and reset database (with external library ?)
    public function testResetTestUser(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $updatedUser = $userRepository->findOneBy(['email' => 'testEdit@test.test']);

        $updatedUser->setUsername('test')->setEmail('test@test.test');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->flush();

        // just to have no risky test
        $this->assertTrue(true);
    }

    public function testEditFails(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);
        $userName = $testUser->getUsername();

        $client->loginUser($testUser);
        $crawler = $client->request('GET', "/users/{$testUser->getId()}/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals('user_edit', $client->getRequest()->attributes->get('_route'));
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals("Modifier $userName", $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Modifier')->form();

        $form->setValues([
            'user' => [
                'username' => '',
                'password' => ['first' => 'newPassword', 'second' => 'oldPassword'],
                'email' => 'testEdittest.test'
            ]
        ]);

        $client->submit($form);

        $updatedUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $this->assertSelectorTextContains('form div:nth-of-type(1) li', 'Vous devez saisir un nom d\'utilisateur.');
        $this->assertSelectorTextContains('form div:nth-of-type(2) li', 'Les deux mots de passe doivent correspondre.');
        $this->assertSelectorTextContains('form div:nth-of-type(3) li', 'Le format de l\'adresse n\'est pas correcte.'); 

        $this->assertEquals($testUser->getId(), $updatedUser->getId());
        $this->assertEquals($testUser->getUsername(), $updatedUser->getUsername());
        $this->assertEquals($testUser->getEmail(), $updatedUser->getEmail());
        $this->assertEquals('user_edit', $client->getRequest()->attributes->get('_route'));
    }

}
