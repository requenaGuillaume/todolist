<?php

namespace Tests\App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class LoginControllerTest extends WebTestCase
{
    private Form $form;
    private User $testUser;
    private Crawler $crawler;
    private static KernelBrowser $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = static::createClient();
    }

    public function setUp(): void
    {
        $this->testUser = new User();
        $this->testUser->setUsername('test')
            ->setEmail('test@test.test')
            ->setPassword('$2y$04$Gy1WKJfRNPtDjynITKF9o.8z5hMtxC8wA0m8wTBR2LBhGUjcC4tOC');
        
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($this->testUser);
        $em->flush();

        $this->crawler = self::$client->request('GET', '/login');
        $this->form = $this->crawler->selectButton('Se connecter')->form();
    }

    public function tearDown(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $connection->executeQuery('TRUNCATE TABLE user');
    }

    public function testLoginFails(): void
    {
        $this->form->setValues([
            '_username' => 'dummy',
            '_password' => 'dummy'
        ]);

        self::$client->submit($this->form);
        self::$client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK); 
        $this->assertSelectorTextContains('div.alert.alert-danger', 'Invalid credentials.');
        $this->assertEquals('app_login', self::$client->getRequest()->attributes->get('_route'));
    }

    public function testLoginSuccess(): void
    {   
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);                
        $this->assertEquals('app_login', self::$client->getRequest()->attributes->get('_route'));

        $this->form->setValues([
            '_username' => 'test',
            '_password' => 'password'
        ]);

        self::$client->submit($this->form);
        self::$client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK); 
        $this->assertEquals('homepage', self::$client->getRequest()->attributes->get('_route'));
    }

}
