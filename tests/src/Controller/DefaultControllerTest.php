<?php

namespace Tests\App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testIndexIfNotLogged(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);              
        
        $this->client->followRedirect();
        $this->assertEquals('app_login', $this->client->getRequest()->attributes->get('_route'));
    }

    public function testIndexIfLogged(): void
    {
        $testUser = new User();
        $testUser->setUsername('test')
            ->setEmail('test@test.test')
            ->setPassword('$2y$04$Gy1WKJfRNPtDjynITKF9o.8z5hMtxC8wA0m8wTBR2LBhGUjcC4tOC');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($testUser);
        $em->flush();

        $this->client->loginUser($testUser);
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);       
        $this->assertSame(1, $crawler->filter('h1')->count());
        $this->assertEquals(
            "Bienvenue sur Todo List, l'application vous permettant de gérer l'ensemble de vos tâches sans effort !", 
            $crawler->filter('h1')->text()
        );

        $em->remove($testUser);
        $em->flush();
    }
}
