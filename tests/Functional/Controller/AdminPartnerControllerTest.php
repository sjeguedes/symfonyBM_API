<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

/**
 * Class AdminPartnerControllerTest
 *
 * Manage AdminPartnerController test suite.
 */
class AdminPartnerControllerTest extends AbstractControllerTest
{
    /**
     * Provide controller URIs with corresponding HTTP method.
     *
     * @return array
     */
    public function provideCorrectURIs(): array
    {
        return [
            'List partners' => ['GET', '/admin/partners']
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
     * Test that a consumer cannot access controller actions.
     *
     * @dataProvider provideCorrectURIs
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    public function testConsumerCannotAccessControllerActions(string $method, string $uri): void
    {
        // Access controller actions with default consumer authentication
        $this->client->request($method, $uri);
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        // Check forbidden error response
        static::assertResponseStatusCodeSame(403);
        // Check filtered exception thanks to listener with custom error response
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertSame('Access denied.', $content['message']);
    }

    /**
     * Test that an admin can get all existing partners.
     *
     * @return void
     */
    public function testAdminCanListAllPartners(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/admin/partners');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated admin (partner) can list exactly 40 partners (database full list)!
        static::assertArrayHasKey('partners', $content['_embedded']);
        static::assertCount(40, $content['_embedded']['partners']);
        // Check serialization on first result
        static::assertArrayHasKey('uuid', $content['_embedded']['partners'][0]);
        static::assertArrayHasKey('username', $content['_embedded']['partners'][0]);
        static::assertArrayHasKey('email', $content['_embedded']['partners'][0]);
        static::assertArrayHasKey('creation_date', $content['_embedded']['partners'][0]);
        static::assertArrayHasKey('_links', $content['_embedded']['partners'][0]);
    }

    /**
     * Test that an admin can get all existing partners (himself included) with a coherent pagination.
     *
     * @return void
     */
    public function testAdminCanListAllPartnersWithCorrectPaginationData(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/admin/partners?page=5&per_page=3');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('page', $content);
        static::assertSame(5, $content['page']);
        static::assertArrayHasKey('per_page', $content);
        static::assertSame(3, $content['per_page']);
        static::assertArrayHasKey('pages', $content);
        static::assertSame(14, $content['pages']);
        static::assertArrayHasKey('total', $content);
        static::assertSame(40, $content['total']);
    }
}