<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

/**
 * Class PartnerControllerTest
 *
 * Manage PartnerController test suite.
 */
class PartnerControllerTest extends AbstractControllerTest
{
    /**
     * Define API default partners data for all common tests.
     */
    private const DEFAULT_DATA = [
        'admin'    => ['uuid' => '858f4e2e-2ed9-3c9c-8957-75fb5fa7f6de'],
        'consumer' => ['uuid' => '14da0978-5078-399d-885c-263bdaacc6f3']
    ];

    /**
     * Provide controller URIs with corresponding HTTP method.
     *
     * @return array
     */
    public function provideCorrectURIs(): array
    {
        return [
            'Show partner by uuid'  => ['GET', '/partners/' . self::DEFAULT_DATA['consumer']['uuid']],
            'Show partner by email' => ['GET', '/partners/' . self::DEFAULT_CREDENTIALS['consumer']['email']]
        ];
    }

    /**
     * Test that all controller actions are secured.
     *
     * @dataProvider provideCorrectURIs
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    public function testAllControllerActionsAreSecured(string $method, string $uri): void
    {
        $this->client->setServerParameter('HTTP_Authorization', '');
        $this->client->request($method, $uri);
        static::assertJson($this->client->getResponse()->getContent());
        // Check unauthorized response
        static::assertResponseStatusCodeSame(401);
    }

    /**
     * Test that a consumer can show is own partner data by uuid.
     *
     * @return void
     */
    public function testConsumerCanShowHisOwnDataByUuid(): void
    {
        $this->client->request('GET', '/partners/' . self::DEFAULT_DATA['consumer']['uuid']);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check serialization
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('uuid', $content);
        static::assertArrayHasKey('type', $content);
        static::assertArrayHasKey('username', $content);
        static::assertArrayHasKey('email', $content);
        static::assertArrayHasKey('roles', $content);
        static::assertArrayHasKey('creation_date', $content);
        static::assertArrayHasKey('update_date', $content);
        static::assertArrayHasKey('_links', $content);
    }

    /**
     * Test that a consumer cannot show another partner data by uuid.
     *
     * @return void
     */
    public function testConsumerCannotShowAnotherPartnerDataByUuid(): void
    {
        // Use admin consumer (partner) uuid to check forbidden access
        $this->client->request('GET', '/partners/' . self::DEFAULT_DATA['admin']['uuid']);
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        // Check forbidden error response
        static::assertResponseStatusCodeSame(403);
        // Check filtered exception thanks to listener with custom error response
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertSame('Partner resource view action not allowed', $content['message']);

    }

    /**
     * Test that a consumer can show is own partner data by email.
     *
     * Please note that this test checks the same content as request by uuid
     * due to use of ControllerTrait::forward method.
     *
     * @return void
     */
    public function testConsumerCanShowHisOwnDataByEmail(): void
    {
        $this->client->request('GET', '/partners/' . self::DEFAULT_CREDENTIALS['consumer']['email']);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * Test that cache response headers are correctly set.
     *
     * @return void
     */
    public function testCacheResponseHeadersAreSet(): void
    {
        $this->client->request('GET', '/partners/' . self::DEFAULT_DATA['consumer']['uuid']);
        $responseHeaders = $this->client->getResponse()->headers;
        // Check cache expiration strategy headers
        static::assertResponseHasHeader('Cache-Control');
        static::assertNotNull($responseHeaders->getCacheControlDirective('max-age'));
        static::assertNotNull($responseHeaders->getCacheControlDirective('proxy-revalidate'));
        static::assertNotNull($responseHeaders->getCacheControlDirective('public'));
        static::assertResponseHasHeader('X-App-Cache-Ttl'); // custom header used in CacheKernel
        static::assertIsInt((int) $responseHeaders->get('X-App-Cache-Ttl'));
        // Check cache validation strategy headers
        static::assertResponseHasHeader('Etag');
        static::assertRegExp('/"[a-z0-9]+"/', $responseHeaders->get('Etag'));
        static::assertResponseHasHeader('Last-Modified');
        // Check cache differentiation headers
        static::assertResponseHasHeader('X-App-Cache-Id'); // custom header (HTTPCache uuid)
        static::assertRegExp('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/', $responseHeaders->get('X-App-Cache-Id'));
        static::assertResponseHasHeader('Vary');
        static::assertTrue(\in_array('Authorization', $responseHeaders->all()['vary']));
        static::assertTrue(\in_array('X-App-Cache-Id', $responseHeaders->all()['vary']));
    }
}