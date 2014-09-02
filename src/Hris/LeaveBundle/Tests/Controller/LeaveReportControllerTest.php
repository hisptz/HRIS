<?php

namespace Hris\LeaveBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LeaveReportControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
    }

    public function testCalendar()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/calendar');
    }

}
