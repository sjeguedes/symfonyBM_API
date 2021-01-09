<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityControllerTest
 *
 * Please note that these tests are not necessary but explain security functionality!
 *
 * Manage SecurityController test suite.
 */
class SecurityControllerTest extends AbstractControllerTest
{
    /**
     * Provide controller URIs with corresponding HTTP method.
     *
     * @return array
     */
    public function provideCorrectURIs(): array
    {
        return [
            // "/login/check" request is also used to check custom error 500
            'Login with credentials to get a JWT and refresh token'  => ['POST', '/login/check'], // status code "500" or other one
            'Get a JWT with a valid refresh token'                   => ['POST', '/token/refresh'] // status code "401"
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
    public function testControllerActionsWithoutRequestBodyAreFilteredByListeners(string $method, string $uri): void
    {
        // Cancel authentication: no default authentication is needed before since it is the purpose!
        $this->client->setServerParameter('HTTP_Authorization', '');
        // Wrong or unexpected requests (without expected body content)!
        $this->client->request($method, $uri);
        static::assertJson($this->client->getResponse()->getContent());
        // Check correct content type header
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        // Check internal error or unauthorized error response
        $statusCode = $this->client->getResponse()->getStatusCode();
        static::assertTrue(\in_array($statusCode, [401, 500]));
        // check custom message in case of error 500 (e.g. controller code for "login check" must not be reached!)
        if (Response::HTTP_INTERNAL_SERVER_ERROR === $statusCode) {
            $content = json_decode($this->client->getResponse()->getContent(), true);
            static::assertSame('Technical error: please contact us if necessary!', $content['message']);
        }
    }

    /**
     * Provide wrong request body partner credentials data keys and values to check JWT authentication failure.
     *
     * @return \Generator
     */
    public function provideWrongCredentialsRequestBodyData(): \Generator
    {
        yield 'Wrong data key "email' => [ // status code 400
            'data' => [
                'api_account' => [
                    'username' => self::DEFAULT_CREDENTIALS['consumer']['email'],
                    'password' => self::DEFAULT_CREDENTIALS['consumer']['password']
                ]
            ]
        ];
        yield 'Wrong data key "password' => [ // status code 400
            'data' => [
                'api_account' => [
                    'email' => self::DEFAULT_CREDENTIALS['consumer']['email'],
                    'pwd'   => self::DEFAULT_CREDENTIALS['consumer']['password']
                ]
            ]
        ];
        yield 'Missing main expected key "api_account"' => [ // status code 400
            'data' => [
                'email'    => self::DEFAULT_CREDENTIALS['consumer']['email'],
                'password' => self::DEFAULT_CREDENTIALS['consumer']['password']
            ]
        ];
        yield 'Missing expected key "email"' => [ // status code 400
            'data' => [
                'api_account' => [
                    'password' => self::DEFAULT_CREDENTIALS['consumer']['password']
                ]
            ]
        ];
        yield 'Missing expected key "password"' => [ // status code 400
            'data' => [
                'api_account' => [
                    'email' => self::DEFAULT_CREDENTIALS['consumer']['email']
                ]
            ]
        ];
        yield 'Wrong email value' => [ // status code 401
            'data' => [
                'api_account' => [
                    'email'    => 'business-19d2@legros-sasu.com',
                    'password' => 'pass_12'
                ]
            ]
        ];
        yield 'Wrong password value' => [ // status code 401
            'data' => [
                'api_account' => [
                    'email'    => 'business-19d2@legros-sasu.fr',
                    'password' => 'pass_13'
                ]
            ]
        ];
    }

    /**
     * Test that consumer must format request body as expected, with correct credentials keys and values.
     *
     * @dataProvider provideWrongCredentialsRequestBodyData
     *
     * @param array $data
     *
     * @return void
     */
    public function testConsumerCannotAuthenticateWithWrongCredentials(array $data): void
    {
        // Request with client body content
        $this->client->request(
            'POST',
            '/login/check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([$data]) // New client expected data
        );
        static::assertJson($this->client->getResponse()->getContent());
        // Check correct content type header
        static::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        // Check bad request or unauthorized JSON error response
        static::assertTrue(\in_array($this->client->getResponse()->getStatusCode(),[400, 401]));
    }
}