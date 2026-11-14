<?php

namespace App\Tests\Integration;

class ApiDocumentationTest extends ApiTestCase
{
    public function testEveryEndpointIsDescribed(): void
    {
        $this->client->request('GET', '/api/doc/json');

        $this->assertResponseIsSuccessful();
        $paths = $this->responseData()['paths'];
        $this->assertNotEmpty($paths);

        foreach ($paths as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $name = strtoupper($method) . ' ' . $path;
                $this->assertNotEmpty($operation['summary'] ?? null, $name . ' has no summary');
                $this->assertNotEmpty($operation['tags'] ?? null, $name . ' has no tag');
                $this->assertArrayNotHasKey('default', $operation['responses'], $name . ' has no responses');
            }
        }
    }

    public function testSwaggerUiIsAvailable(): void
    {
        $this->client->request('GET', '/api/doc');

        $this->assertResponseIsSuccessful();
    }
}
