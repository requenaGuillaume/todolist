<?php

namespace Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
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
    }

}
