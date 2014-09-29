<?php

namespace Hris\TrainingBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class instanceRecordControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/index');
    }

    public function testAddrecordinstance()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/addrecordInstance');
    }

    public function testSaverecordinstance()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/saverecordInstance');
    }

    public function testEditrecordinstance()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/editrecordInstance');
    }

    public function testDeleterecordinstance()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/deleterecordInstance');
    }

}
