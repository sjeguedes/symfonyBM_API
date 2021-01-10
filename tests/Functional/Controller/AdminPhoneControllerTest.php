<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

/**
 * Class AdminPhoneControllerTest
 *
 * Manage AdminPhoneController test suite.
 */
class AdminPhoneControllerTest extends AbstractControllerTest
{
    /**
     * Define API default partners data for all common tests.
     */
    private const DEFAULT_DATA = [
        'consumer' => [
            'uuid'        => '14da0978-5078-399d-885c-263bdaacc6f3',
            'phone_uuid'  => 'ff3988e0-8fc3-3ec0-b4d1-57051b4c6cab'
        ],
        'another_consumer' => [ // direction-421c@georges-scop.fr
            'uuid'        => 'c7570c62-4114-3551-988f-bc46be9e2c6c',
            'phone_uuid'  => '592c493a-175c-3953-84b3-e0e62802ea9b'
        ]
    ];

    /**
     * Provide controller URIs with corresponding HTTP method.
     *
     * @return array
     */
    public function provideCorrectURIs(): array
    {
        $partnerUuid = self::DEFAULT_DATA['consumer']['uuid'];
        return [
            'List phones per partner' => ['GET', '/admin/partners/' . $partnerUuid . '/phones']
        ];
    }

    /**
     * Provide wrong pagination filters data.
     *
     * @return array
     */
    public function provideWrongPaginationFiltersData(): array
    {
        return [
            'Wrong "page" value set to "zero"' => [
                'data' => [
                    'page'     => 0,
                    'per_page' => 1
                ]
            ],
            'Wrong "page" value set to "negative int"' => [
                'data' => [
                    'page'     => -1,
                    'per_page' => 1
                ]
            ],
            'Wrong "per_page" value set to "zero"' => [
                'data' => [
                    'page'     => 1,
                    'per_page' => 0
                ]
            ],
            'Wrong "per_page" value set to "negative int"' => [
                'data' => [
                    'page'     => 1,
                    'per_page' => -1
                ]
            ],
            'Wrong "page" value set to "out of range int"' => [
                'data' => [
                    'page'     => 3,
                    'per_page' => 1
                ]
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
     * Test that an admin can get all existing partner associated phones.
     *
     * @return void
     */
    public function testAdminCanListAllPartnerPhones(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/phones');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated admin (partner) can list exactly 2 phones associated to selected partner!
        static::assertArrayHasKey('phones', $content['_embedded']);
        static::assertCount(2, $content['_embedded']['phones']);
        // Check serialization on first result
        static::assertArrayHasKey('uuid', $content['_embedded']['phones'][0]);
        static::assertArrayHasKey('brand', $content['_embedded']['phones'][0]);
        static::assertArrayHasKey('model', $content['_embedded']['phones'][0]);
        static::assertArrayHasKey('price', $content['_embedded']['phones'][0]);
        static::assertArrayHasKey('creation_date', $content['_embedded']['phones'][0]);
        static::assertArrayHasKey('_links', $content['_embedded']['phones'][0]);
    }

    /**
     * Test that an admin can get all existing partner associated phones with a coherent pagination.
     *
     * @return void
     */
    public function testAdminCanListAllPartnerPhonesWithCorrectPaginationData(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request(
            'GET',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/phones?page=2&per_page=1'
        );
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('page', $content);
        static::assertSame(2, $content['page']);
        static::assertArrayHasKey('per_page', $content);
        static::assertSame(1, $content['per_page']);
        static::assertArrayHasKey('pages', $content);
        static::assertSame(2, $content['pages']);
        static::assertArrayHasKey('total', $content);
        static::assertSame(2, $content['total']);
    }

    /**
     * Test that an admin cannot list partner associated phones with a wrong pagination.
     *
     * @dataProvider provideWrongPaginationFiltersData
     *
     * @param array $data
     *
     * @return void
     */
    public function testAdminCannotListPartnerPhonesWithWrongPaginationData(array $data): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $page = $data['page'];
        $perPage = $data['per_page'];
        $this->client->request(
            'GET',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/phones?page=' . $page . '&per_page=' . $perPage
        );
        static::assertJson($this->client->getResponse()->getContent());
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        // Check bad request error response
        static::assertResponseStatusCodeSame(400);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertTrue(\in_array(
            $content['message'],
            [
                'Pagination number (page) parameter failure: expected value >= 1',
                'Pagination limit (per_page) parameter failure: expected value >= 1',
                'No phone list result found'
            ]
        ));
    }
}