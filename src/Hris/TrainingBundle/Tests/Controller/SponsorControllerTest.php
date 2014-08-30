<?php

namespace Hris\TrainingBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SponsorControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
    }

    public function testAddsponsor()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/addSponsor');
    }

    public function testEditsponsor()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/editSponsor');
    }

    public function testDeletesponsor()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/deleteSponsor');
    }

}
