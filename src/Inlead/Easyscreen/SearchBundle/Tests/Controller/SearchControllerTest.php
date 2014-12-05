<?php

namespace Inlead\Easyscreen\SearchBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function testSearch()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/search');
    }

    public function testSingle()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/single');
    }

}
