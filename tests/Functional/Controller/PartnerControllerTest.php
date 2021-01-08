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
        'consumer' => ['uuid' => 'ad7da746-2553-4ea4-a3f2-a1fd962a09ef']
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
}