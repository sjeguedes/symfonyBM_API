<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class AbstractControllerTest
 *
 * Centralize common operations for API test suite.
 *
 * @see https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/3-functional-testing.md
 * @see https://symfony.com/doc/current/testing.html
 */
abstract class AbstractControllerTest extends WebTestCase
{
    /**
     * Define API default credentials for all common tests.
     */
    protected const DEFAULT_CREDENTIALS = [
        'admin'    => ['email' => 'business-4h96@roussel-sasu.fr', 'password' => 'pass_11'],
        'consumer' => ['email' => 'business-19d2@legros-sasu.fr', 'password'  => 'pass_12'],
    ];

    /**
     * @var string
     */
    protected $apiPathPrefix;

    /**
     * @var KernelBrowser
     */
    protected $client;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Initialize necessary data before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setup();
        $this->client = static::createClient();
        $this->apiPathPrefix = $this->client->getContainer()->getParameter('api_and_version_path_prefix');
        $this->client->setServerParameter('HTTP_HOST', 'localhost/' . $this->apiPathPrefix);
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager->beginTransaction();
        // Initialize a client with default authenticated consumer
        $this->initDefaultAuthenticatedClient();
    }

    /**
     * Create an authenticated client with a default Authorization header Bearer value
     * thanks to a JWT.
     *
     * @param string $username
     * @param string $password
     *
     * @return KernelBrowser
     */
    protected function createAuthenticatedClient(string $username, string $password): KernelBrowser
    {
        // Request with credentials as client body content
        $this->client->request(
            'POST',
            '/login/check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'api_account' => [
                    'email'    => $username,
                    'password' => $password,
                ]
            ])
        );
        // Get a JWT (and also a refresh token)
        $data = json_decode($this->client->getResponse()->getContent(), true);
        // Pass the JWT to header to be used for following requests
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));
        // Return HTTP client
        return $this->client;
    }


    /**
     * Initialize a authenticated client with admin  or consumer default credentials.
     *
     * @param bool $isAdmin
     *
     * @return KernelBrowser
     */
    protected function initDefaultAuthenticatedClient(bool $isAdmin = false): KernelBrowser
    {
        // Check admin case
        $partnerCredentials = self::DEFAULT_CREDENTIALS[$isAdmin ? 'admin' : 'consumer'];
        // Return corresponding client
        return $this->createAuthenticatedClient(
            $partnerCredentials['email'],
            $partnerCredentials['password']
        );
    }

    /**
     * Reset necessary data after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Avoid error with database transactions
        if (null !== $dbConnection = $this->entityManager->getConnection()) {
            if ($dbConnection->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $dbConnection->close();
        }
        $this->entityManager = null;
        $this->client = null;
        parent::tearDown();
    }
}