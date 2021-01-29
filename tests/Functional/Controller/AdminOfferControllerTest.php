<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

/**
 * Class AdminOfferControllerTest
 *
 * Manage AdminOfferController test suite.
 */
class AdminOfferControllerTest extends AbstractControllerTest
{
    /**
     * Define API default partners data for all common tests.
     */
    private const DEFAULT_DATA = [
        'consumer' => [
            'uuid'        => '14da0978-5078-399d-885c-263bdaacc6f3',
            'offer_uuid'  => '654d0335-7687-3fd6-81a1-ad88b7360cb5',
            'phone_uuid'  => 'ff3988e0-8fc3-3ec0-b4d1-57051b4c6cab'
        ],
        'another_consumer' => [ // direction-421c@georges-scop.fr
            'uuid'        => 'c7570c62-4114-3551-988f-bc46be9e2c6c',
            'offer_uuid'  => '0913faad-232d-3cd1-b455-7de24886e706',
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
        $offerUuid = self::DEFAULT_DATA['consumer']['offer_uuid'];
        $phoneUuid = self::DEFAULT_DATA['consumer']['phone_uuid'];
        return [
            'List offers'             => ['GET', '/admin/offers'],
            'Show offer'              => ['GET', '/admin/offers/' . $offerUuid],
            'List offers per partner' => ['GET', '/admin/partners/' . $partnerUuid . '/offers'],
            'List offers per phone'   => ['GET', '/admin/phones/' . $phoneUuid . '/offers']
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
     * Test that an admin can get all existing offers (relation between a partner and a phone).
     *
     * @return void
     */
    public function testAdminCanListAllOffers(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        // Get offers complete list ("full_list" filter is unused here since it's an admin action!)
        $this->client->request('GET', '/admin/offers');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated admin (partner) can list exactly 40 offers (database full list)!
        static::assertArrayHasKey('offers', $content['_embedded']);
        static::assertCount(40, $content['_embedded']['offers']);
    }

    /**
     * Test that an admin can show any offer.
     *
     * @return void
     */
    public function testAdminCanShowAnyOffer(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/admin/offers/' . self::DEFAULT_DATA['consumer']['offer_uuid']);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check serialization
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('uuid', $content);
        static::assertArrayHasKey('creation_date', $content);
        static::assertArrayHasKey('_links', $content);
        static::assertArrayHasKey('_embedded', $content);
        static::assertArrayHasKey('partner', $content['_embedded']);
        static::assertArrayHasKey('phone', $content['_embedded']);
    }

    /**
     * Test that an admin can get all existing partner associated offers.
     *
     * @return void
     */
    public function testAdminCanListAllPartnerOffers(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/offers');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated admin (partner) can list exactly 2 offers associated to selected partner!
        static::assertArrayHasKey('offers', $content['_embedded']);
        static::assertCount(2, $content['_embedded']['offers']);
        // Check serialization on first result
        static::assertArrayHasKey('uuid', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('creation_date', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('_links', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('_embedded', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('partner', $content['_embedded']['offers'][0]['_embedded']);
        static::assertArrayHasKey('phone', $content['_embedded']['offers'][0]['_embedded']);
    }

    /**
     * Test that an admin can get all existing partner associated offers with a coherent pagination.
     *
     * @return void
     */
    public function testAdminCanListAllPartnerOffersWithCorrectPaginationData(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request(
            'GET',
            '/admin/partners/' . self::DEFAULT_DATA['consumer']['uuid'] . '/offers?page=2&per_page=1'
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
     * Test that an admin can get all existing phone associated offers.
     *
     * @return void
     */
    public function testAdminCanListAllPhoneOffers(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request('GET', '/admin/phones/' . self::DEFAULT_DATA['consumer']['phone_uuid'] . '/offers');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated admin (partner) can list exactly 3 offers associated to selected phone!
        static::assertArrayHasKey('offers', $content['_embedded']);
        static::assertCount(3, $content['_embedded']['offers']);
        // Check serialization on first result
        static::assertArrayHasKey('uuid', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('creation_date', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('_links', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('_embedded', $content['_embedded']['offers'][0]);
        static::assertArrayHasKey('partner', $content['_embedded']['offers'][0]['_embedded']);
        static::assertArrayHasKey('phone', $content['_embedded']['offers'][0]['_embedded']);
    }

    /**
     * Test that an admin can get all existing phone associated offers with a coherent pagination.
     *
     * @return void
     */
    public function testAdminCanListAllPhoneOffersWithCorrectPaginationData(): void
    {
        // Authenticate an admin client
        $this->initDefaultAuthenticatedClient(true);
        $this->client->request(
            'GET',
            '/admin/phones/' . self::DEFAULT_DATA['consumer']['phone_uuid'] . '/offers?page=2&per_page=1'
        );
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('page', $content);
        static::assertSame(2, $content['page']);
        static::assertArrayHasKey('per_page', $content);
        static::assertSame(1, $content['per_page']);
        static::assertArrayHasKey('pages', $content);
        static::assertSame(3, $content['pages']);
        static::assertArrayHasKey('total', $content);
        static::assertSame(3, $content['total']);
    }
}