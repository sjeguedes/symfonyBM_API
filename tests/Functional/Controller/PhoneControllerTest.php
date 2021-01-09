<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

/**
 * Class PhoneControllerTest
 *
 * Manage PhoneController test suite.
 */
class PhoneControllerTest extends AbstractControllerTest
{
    /**
     * Define API default phones data for all common tests.
     */
    private const DEFAULT_DATA = [
        'consumer' => ['phone_uuid' => 'ff3988e0-8fc3-3ec0-b4d1-57051b4c6cab']
    ];

    /**
     * Provide controller URIs with corresponding HTTP method.
     *
     * @return array
     */
    public function provideCorrectURIs(): array
    {
        $phoneUuid = self::DEFAULT_DATA['consumer']['phone_uuid'];
        return [
            'List phones' => ['GET', '/phones'],
            'Show phone'  => ['GET', '/phones/' . $phoneUuid]
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
     * Test that a consumer can get his phone list.
     *
     * @return void
     */
    public function testConsumerCanListHisPhones(): void
    {
        $this->client->request('GET', '/phones');
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated consumer (partner) has exactly 1 phone!
        $content = json_decode($this->client->getResponse()->getContent(), true);
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
     * Test that a consumer can get all existing phones (catalog).
     *
     * @return void
     */
    public function testConsumerCanListAllPhones(): void
    {
        $this->client->request('GET', '/phones?full_list');
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check that default authenticated consumer (partner) can list exactly 40 phones (database catalog)!
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('phones', $content['_embedded']);
        static::assertCount(40, $content['_embedded']['phones']);
    }

    /**
     * Test that a consumer can get all existing phones with a coherent pagination.
     *
     * @return void
     */
    public function testConsumerCanListAllPhonesWithCorrectPaginationData(): void
    {
        $this->client->request('GET', '/phones?full_list&page=10&per_page=1');
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('page', $content);
        static::assertSame(10, $content['page']);
        static::assertArrayHasKey('per_page', $content);
        static::assertSame(1, $content['per_page']);
        static::assertArrayHasKey('pages', $content);
        static::assertSame(40, $content['pages']);
        static::assertArrayHasKey('total', $content);
        static::assertSame(40, $content['total']);
    }

    /**
     * Test that a consumer can show one of his phones.
     *
     * @return void
     */
    public function testConsumerCanShowOneOfHisPhones(): void
    {
        $this->client->request('GET', '/phones/' . self::DEFAULT_DATA['consumer']['phone_uuid']);
        // Response with status code "200" assertion
        static::assertTrue($this->client->getResponse()->isSuccessful());
        // Check HAL HATEOAS response content type
        static::assertResponseHeaderSame('Content-Type', 'application/hal+json');
        // Check serialization
        $content = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('uuid', $content);
        static::assertArrayHasKey('type', $content);
        static::assertArrayHasKey('brand', $content);
        static::assertArrayHasKey('model', $content);
        static::assertArrayHasKey('color', $content);
        static::assertArrayHasKey('description', $content);
        static::assertArrayHasKey('price', $content);
        static::assertArrayHasKey('creation_date', $content);
        static::assertArrayHasKey('update_date', $content);
        static::assertArrayHasKey('_links', $content);
    }
}