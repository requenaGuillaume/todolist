<?php

namespace Tests\App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultControllerTest extends WebTestCase
{
    public function testIndexIfNotLogged(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $urlGenerator = $client->getContainer()->get('router.default');
        $url = $urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);        
        $this->assertEquals($url, $client->getResponse()->headers->get('Location'));        
        
        $client->followRedirect();
        $this->assertEquals('app_login', $client->getRequest()->attributes->get('_route'));
    }

    public function testIndexIfLogged(): void
    {
        $client = static::createClient();

        // TODO - use fixtures ? create the user here ?
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'test@test.test']);

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);       
        $this->assertSame(1, $crawler->filter('h1')->count());
        $this->assertEquals(
            "Bienvenue sur Todo List, l'application vous permettant de gérer l'ensemble de vos tâches sans effort !", 
            $crawler->filter('h1')->text()
        );
    }
}
