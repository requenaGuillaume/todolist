<?php

namespace Tests\App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLoginSuccess(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);                
        $this->assertEquals('app_login', $client->getRequest()->attributes->get('_route'));

        $form->setValues([
            '_username' => 'test',
            '_password' => 'password'
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK); 
        $this->assertEquals('homepage', $client->getRequest()->attributes->get('_route'));
    }

    public function testLoginFails(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);                
        $this->assertEquals('app_login', $client->getRequest()->attributes->get('_route'));

        $form->setValues([
            '_username' => 'dummy',
            '_password' => 'dummy'
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK); 
        $this->assertSelectorTextContains('div.alert.alert-danger', 'Invalid credentials.');
        $this->assertEquals('app_login', $client->getRequest()->attributes->get('_route'));
    }

}
