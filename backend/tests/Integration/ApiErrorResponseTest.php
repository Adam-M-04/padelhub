<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiErrorResponseTest extends WebTestCase
{
    public function testUnknownApiRouteReturnsJsonError(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/does-not-exist');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame(
            ['error' => ['code' => 'NOT_FOUND', 'message' => 'Not Found']],
            json_decode($client->getResponse()->getContent(), true),
        );
    }
}
