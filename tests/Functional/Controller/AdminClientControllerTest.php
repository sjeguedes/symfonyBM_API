<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;

/**
 * Class AdminClientControllerTest
 *
 * Manage AdminClientController test suite.
 */
class AdminClientControllerTest extends AbstractControllerTest
{
    /**
     * Define API default partners data for all common tests.
     */
    private const DEFAULT_DATA = [
        'consumer' => [
            'uuid'        => '14da0978-5078-399d-885c-263bdaacc6f3',
            'client_uuid' => '1c8567f4-bee1-376a-8b57-2567b6c9ed5e'
        ],
        'another_consumer' => [ // direction-421c@georges-scop.fr
            'uuid'        => 'c7570c62-4114-3551-988f-bc46be9e2c6c',
            'client_uuid' => '67b7bebb-3b65-36b6-8c83-94bea28898ed'
        ]
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
        $partnerUuid = self::DEFAULT_DATA['consumer']['uuid'];
        $clientUuid = self::DEFAULT_DATA['consumer']['client_uuid'];
        return [
            'List clients per partner'         => ['GET', '/admin/partners/' . $partnerUuid . '/clients'],
            'Create partner associated client' => ['POST', '/admin/partners/' . $partnerUuid . '/clients'],
            'Delete partner associated client' => ['DELETE', '/admin/partners/' . $partnerUuid . '/clients/' . $clientUuid]
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
                'name'  => 'Durand',
                'type'  => 'Professionnel',
                'email' => 'business-a329@durand-sas.net'
            ]
        ];
        yield 'Correct client data set 2' => [
            'data' => [
                'name'  => 'Lefranc',
                'type'  => 'Particulier',
                'email' => 'lefranc-k751@orange.fr'
            ]
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
     * Test that an admin can get all existing partner associated clients.
     *
     * @return void
     */
    public function testAdminCanListAllPartnerClients(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/clients');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated admin (partner) can list exactly 2 clients associated to selected partner!
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
     * Test that an admin can get all existing partner associated clients with a coherent pagination.
     *
     * @return void
     */
    public function testAdminCanListAllPartnerClientsWithCorrectPaginationData(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request(
            'GET',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/clients?page=1&per_page=10'
        );
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('page', $content);
        static::assertSame(1, $content['page']);
        static::assertArrayHasKey('per_page', $content);
        static::assertSame(10, $content['per_page']);
        static::assertArrayHasKey('pages', $content);
        static::assertSame(1, $content['pages']);
        static::assertArrayHasKey('total', $content);
        static::assertSame(2, $content['total']);
    }

    /**
     * Test that an admin cannot list partner associated clients with a wrong pagination.
     *
     * @return void
     */
    public function testAdminCannotListPartnerClientsWithWrongPaginationData(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request(
            'GET',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/clients?page=2&per_page=10'
        );
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        // Check bad request error response
        static::assertResponseStatusCodeSame(400);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertSame('No client list result found', $content['message']);
    }

    /**
     * Test that an admin can create a client associated to a particular partner.
     *
     * @dataProvider provideCorrectClientRequestBodyData
     *
     * @param array $data
     *
     * @return void
     */
    public function testAdminCanCreateAClientAssociatedToAPartner(array $data): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        // Request with client body content
        $this->client->request(
            'POST',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/clients',
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
     * Test that an admin can delete a client associated to a particular partner.
     *
     * @dataProvider provideCorrectClientRequestBodyData
     *
     * @param array $data
     *
     * @return void
     */
    public function testAdminCanDeleteAClientAssociatedToAPartner(array $data): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        // Call repository to get data for client created before in test in order to delete it here!
        $lastCreatedClientEntity = $this->clientRepository->findOneBy(['email' => $data['email']]);
        $clientUuid = $lastCreatedClientEntity->getUuid()->toString();
        $this->client->request(
            'DELETE',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/clients/' . $clientUuid
        );
        // Check empty response
        static::assertEmpty($this->client->getResponse()->getContent());
        static::assertResponseNotHasHeader('Content-Type');
        static::assertResponseStatusCodeSame(204);
    }

    /**
     * Test that an admin cannot delete a client unassociated to a particular partner.
     *
     * @return void
     */
    public function testAdminCannotDeleteAClientUnassociatedToAPartner(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        // Use another partner client uuid to throw an exception
        $anotherConsumerClientUuid = self::DEFAULT_DATA['another_consumer']['client_uuid'];
        $this->client->request(
            'DELETE',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/clients/' . $anotherConsumerClientUuid
        );
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        // Check bad request custom error message in JSON response due to exception thrown
        static::assertResponseStatusCodeSame(400);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertSame('Selected Client to remove unassociated to chosen Partner', $content['message']);
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