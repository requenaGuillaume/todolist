<?php

namespace Tests\App\Controller;

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
    }
}
