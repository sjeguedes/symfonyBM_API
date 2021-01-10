<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;

/**
 * Class ClientControllerTest
 *
 * Manage ClientController test suite.
 */
class ClientControllerTest extends AbstractControllerTest
{
    /**
     * Define API default clients data for all common tests.
     */
    private const DEFAULT_DATA = [
        'admin'    => ['client_uuid' => 'af6d57b5-ba0d-39d1-ba1a-4c17a14f6ba8'],
        'consumer' => ['client_uuid' => '1c8567f4-bee1-376a-8b57-2567b6c9ed5e']
    ];

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * Initialize necessary data before each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->clientRepository = $this->entityManager->getRepository(Client::class);
    }

    /**
     * Provide controller URIs with corresponding HTTP method.
     *
     * @return array
     */
    public function provideCorrectURIs(): array
    {
        $clientUuid = self::DEFAULT_DATA['consumer']['client_uuid'];
        return [
            'List clients'  => ['GET', '/clients'],
            'Show client'   => ['GET', '/clients/' . $clientUuid],
            'Create client' => ['POST', '/clients'],
            'Delete client' => ['DELETE', '/clients/' . $clientUuid]
        ];
    }

    /**
     * Provide wrong controller URIs with corresponding HTTP method.
     *
     * @return array
     */
    public function provideWrongURIs(): array
    {
        $clientUuid = self::DEFAULT_DATA['consumer']['client_uuid'];
        return [
            'List clients with wrong URI'                 => ['GET', '/clients/list'],
            'Show client with wrong URI'                  => ['GET', '/client/' . $clientUuid],
            'Show client with misused collection filters' => ['GET', '/clients/' . $clientUuid . '?page=2&per_page=5'],
            'Delete client without uuid'                  => ['POST', '/clients/3'],
            'Delete client with no corresponding uuid'    => ['DELETE', '/clients/0a3ecd72-3994-4d00-bd16-ec0fb7c04e22']
        ];
    }

    /**
     * Provide correct request body client data keys and values to check creation validation.
     *
     * @return \Generator
     */
    public function provideCorrectClientRequestBodyData(): \Generator
    {
        yield 'Correct client data set 1' => [
            'data' => [
                'name'  => 'Duchemin',
                'type'  => 'Professionnel',
                'email' => 'info-25u2@duchemin-sarl.com'
            ]
        ];
        yield 'Correct client data set 2' => [
            'data' => [
                'name'  => 'Dupont',
                'type'  => 'Particulier',
                'email' => 'dupont-a743@gmail.com'
            ]
        ];
    }

    /**
     * Provide wrong request body client data keys and values to check creation validation.
     *
     * @return \Generator
     */
    public function provideWrongClientRequestBodyData(): \Generator
    {
        yield 'Wrong data key' => [
            'data' => [
                'username' => 'Duchemin',
                'type'     => 'Professionnel',
                'email'    => 'info-25u2@duchemin-sarl.com'
            ]
        ];
        yield 'Missing expected key "name"' => [
            'data' => [
                'type'  => 'Professionnel',
                'email' => 'info-25u2@duchemin-sarl.com'
            ]
        ];
        yield 'Missing expected key "type"' => [
            'data' => [
                'name'  => 'Duchemin',
                'email' => 'info-25u2@duchemin-sarl.com'
            ]
        ];
        yield 'Missing expected key "email"' => [
            'data' => [
                'name' => 'Duchemin',
                'type' => 'Professionnel'
            ]
        ];
        yield 'Wrong data value with empty name' => [
            'data' => [
                'name'  => '',
                'type'  => 'Professionnel',
                'email' => 'info-25u2@duchemin-sarl.com'
            ]
        ];
        yield 'Wrong data value with wrong type label' => [
            'data' => [
                'name'  => 'Duchemin',
                'type'  => 'Professionnels',
                'email' => 'info-25u2@duchemin-sarl.com'
            ]
        ];
        yield 'Wrong data value with wrong email format' => [
            'data' => [
                'name'  => 'Duchemin',
                'type'  => 'Professionnel',
                'email' => 'info-25u2duchemin-sarl.com'
            ]
        ];
    }

    /**
     * Provide wrong query filters keys and values in order to control that existing ones are checked.
     *
     * @return array
     */
    public function provideWrongQueryFilters(): array
    {
        return [
            'Wrong query filter with "unknown key"'                             => ['data' => ['wrong_filter_name', null]],
            'Wrong query filter name for "page"'                                => ['data' => ['pages', null]],
            'Wrong query filter name for "per_page"'                            => ['data' => ['per-page', null]],
            'Wrong query filter value as "string" for "page"'                   => ['data' => ['page', 'unexpected string']],
            'Wrong query filter value as "null" for "page"'                     => ['data' => ['page', null]],
            'Wrong query filter value as "negative int" for "page"'             => ['data' => ['page', -1]],
            'Wrong query filter for "per_page" without "page" filter'           => ['data' => ['per_page', 10]],
            'Wrong query filter value as "string" for "per_page"'               => ['data' => ['per_page', 'unexpected string']],
            'Wrong query filter value as "null" for "per_page"'                 => ['data' => ['per_page', null]],
            'Wrong query filter value as "negative int" for "per_page"'         => ['data' => ['per_page', -1]],
            'Wrong query filter value with "unexpected string" for "full_list"' => ['data' => ['full_list','unexpected value']],
            'Wrong query filter value with "unexpected int" for "per_page"'     => ['data' => ['full_list', 1]]
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
     * Test that a consumer cannot use defined filters on any request.
     *
     * @dataProvider provideWrongURIs
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    public function testConsumerCannotUseFiltersOnAnyRequests(string $method, string $uri): void
    {
        $this->client->request($method, $uri);
        static::assertJson($this->client->getResponse()->getContent());
        // Check bad request or not found JSON error response
        static::assertTrue(\in_array($this->client->getResponse()->getStatusCode(),[400, 404]));
    }

    /**
     * Test that a consumer can get his client list.
     *
     * @return void
     */
    public function testConsumerCanListHisClients(): void
    {
        $this->client->request('GET', '/clients');
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated consumer (partner) has exactly 1 client!
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('clients', $content['_embedded']);
        static::assertCount(2, $content['_embedded']['clients']);
        // Check serialization on first result
        static::assertArrayHasKey('uuid', $content['_embedded']['clients'][0]);
        static::assertArrayHasKey('name', $content['_embedded']['clients'][0]);
        static::assertArrayHasKey('email', $content['_embedded']['clients'][0]);
        static::assertArrayHasKey('creation_date', $content['_embedded']['clients'][0]);
        static::assertArrayHasKey('_links', $content['_embedded']['clients'][0]);
    }

    /**
     * Test that an admin can get all existing clients.
     *
     * @return void
     */
    public function testAdminCanListAllClients(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/clients?full_list');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated admin (partner) can list exactly 40 clients (database full list)!
        static::assertArrayHasKey('clients', $content['_embedded']);
        static::assertCount(40, $content['_embedded']['clients']);
    }

    /**
     * Test that an admin can get all existing clients with a coherent pagination.
     *
     * @return void
     */
    public function testAdminCanListAllClientsWithCorrectPaginationData(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/clients?full_list&page=2&per_page=5');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('page', $content);
        static::assertSame(2, $content['page']);
        static::assertArrayHasKey('per_page', $content);
        static::assertSame(5, $content['per_page']);
        static::assertArrayHasKey('pages', $content);
        static::assertSame(8, $content['pages']);
        static::assertArrayHasKey('total', $content);
        static::assertSame(40, $content['total']);
    }

    /**
     * Test that an admin can get all existing clients.
     *
     * @dataProvider provideWrongQueryFilters
     *
     * @param array $data
     *
     * @return void
     */
    public function testConsumerCannotUseUnexpectedFilter(array $data): void
    {
        // Get filter data
        $key = $data[0];
        $value = $data[1];
        $this->client->request('GET', '/clients?' . $key . '=' . $value);
        // Check bad request JSON response
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        static::assertResponseStatusCodeSame(400);
    }

    /**
     * Test that a consumer can show one of his clients.
     *
     * @return void
     */
    public function testConsumerCanShowOneOfHisClients(): void
    {
        $this->client->request('GET', '/clients/' . self::DEFAULT_DATA['consumer']['client_uuid']);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check serialization
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('uuid', $content);
        static::assertArrayHasKey('type', $content);
        static::assertArrayHasKey('name', $content);
        static::assertArrayHasKey('email', $content);
        static::assertArrayHasKey('creation_date', $content);
        static::assertArrayHasKey('update_date', $content);
        static::assertArrayHasKey('_links', $content);
    }

    /**
     * Test that a consumer cannot show one unassociated client.
     *
     * @return void
     */
    public function testConsumerCannotShowOneUnassociatedClient(): void
    {
        // Request with client uuid associated to another partner (admin consumer)
        $this->client->request('GET', '/clients/' . self::DEFAULT_DATA['admin']['client_uuid']);
        // Check forbidden access response
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        static::assertResponseStatusCodeSame(403);
    }

    /**
     * Test that a consumer can create a client.
     *
     * @dataProvider provideCorrectClientRequestBodyData
     *
     * @param array $data
     *
     * @return void
     */
    public function testConsumerCanCreateAClient(array $data): void
    {
        // Request with client body content
        $this->client->request(
            'POST',
            '/clients',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data) // New client expected data
        );
        // Check created content successful confirmation JSON response (empty response is not used!)
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/json');
        // Check that a "Location" response header is present!
        static::assertResponseHasHeader('Location');
        $locationHeader = $this->client->getResponse()->headers->get('Location');
        // Call repository to get new created client data!
        $newClientEntity = $this->clientRepository->findOneBy(['email' => $data['email']]);
        $pattern = '\/clients\/' . $newClientEntity->getUuid()->toString() . '$';
        // Check link to new resource in "Location" header
        static::assertRegExp('/' . $pattern . '/', $locationHeader);
        static::assertResponseStatusCodeSame(201);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertSame('Client resource successfully created', $content['message']);
    }

    /**
     * Test that a consumer cannot create a client.
     *
     * @dataProvider provideWrongClientRequestBodyData
     *
     * @param array $data
     *
     * @return void
     */
    public function testConsumerCannotCreateAClient(array $data): void
    {
        // Request with client body content
        $this->client->request(
            'POST',
            '/clients',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data) // New client expected data
        );
        // Check bad request JSON response
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        static::assertResponseStatusCodeSame(400);
    }

    /**
     * Test that a consumer can delete a client.
     *
     * @return void
     */
    public function testConsumerCanDeleteAClient(): void
    {
        // Call repository to get data for client created before in test in order to delete it here!
        $lastCreatedClientEntity = $this->clientRepository->findOneBy(['email' => 'info-25u2@duchemin-sarl.com']);
        $this->client->request('DELETE','/clients/' . $lastCreatedClientEntity->getUuid()->toString());
        // Check empty response
        static::assertEmpty($this->client->getResponse()->getContent());
        static::assertResponseNotHasHeader('Content-Type');
        static::assertResponseStatusCodeSame(204);
    }

    /**
     * Test that an admin can delete any (unassociated) client.
     *
     * @return void
     */
    public function testAdminCanDeleteAnyClient(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        // Call repository to get data for client created before in test in order to delete it here!
        $lastCreatedClientEntity = $this->clientRepository->findOneBy(['email' => 'dupont-a743@gmail.com']);
        $clientUuid = $lastCreatedClientEntity->getUuid()->toString();
        // Use consumer client uuid
        $this->client->request('DELETE','/clients/' . $clientUuid);
        // Check empty response
        static::assertEmpty($this->client->getResponse()->getContent());
        static::assertResponseNotHasHeader('Content-Type');
        static::assertResponseStatusCodeSame(204);
    }

    /**
     * Test that a consumer cannot delete one unassociated client.
     *
     * @return void
     */
    public function testConsumerCannotDeleteUnassociatedClient(): void
    {
        // Request with client uuid associated to another partner (admin consumer)
        $this->client->request('DELETE', '/clients/' . self::DEFAULT_DATA['admin']['client_uuid']);
        // Check forbidden access response
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        static::assertResponseStatusCodeSame(403);
    }

    /**
     * Reset necessary data after each test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->clientRepository = null;
        parent::tearDown();
    }
}