<?php

declare(strict_types=1);

namespace App\Services\API\ParamConverter;

use App\Entity\HTTPCache;
use App\Entity\Partner;
use App\Repository\HTTPCacheRepository;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use http\Exception\RuntimeException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\UserNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class HTTPCacheConverter
 *
 * Manage a custom converter to retrieve an entity instance and cache this data.
 *
 * @see https://symfony.com/doc/current/http_cache.html
 */
final class HTTPCacheConverter implements ParamConverterInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AuthorizationHeaderTokenExtractor
     */
    private $tokenExtractor;

    /**
     * @var JWTEncoderInterface
     */
    private $jwtEncoder;

    /**
     * HTTPCacheConverter constructor.
     *
     * @param EntityManagerInterface            $entityManager
     * @param AuthorizationHeaderTokenExtractor $tokenExtractor
     * @param JWTEncoderInterface               $jwtEncoder
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationHeaderTokenExtractor $tokenExtractor,
        JWTEncoderInterface $jwtEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->tokenExtractor = $tokenExtractor;
        $this->jwtEncoder = $jwtEncoder;
    }

    /**
     * Retrieve an instance depending on request and controller method argument name.
     *
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $authenticatedPartner = $this->getUserFromJWT($request);
        $isCollection = $this->isCollection($request);
        $requestURI = $request->getRequestUri();
        $routeName = $request->get('_route');
        $entityAttributeName = $configuration->getName();
        // No result was found, so create a HTTPCache instance which corresponds to this request!
        // TODO: this is a wrong approach since authorization is not called at this level!
        // TODO: move HTTPCache instance persistence in ResponseBuilder method to be sure it is useful to get a new one!
        if (\is_null($result = $this->queryHTTPCache($requestURI, $authenticatedPartner))) {
            // Get Necessary Data
            $type = $isCollection ? HTTPCache::RESOURCE_TYPES['list'] : HTTPCache::RESOURCE_TYPES['unique'];
            preg_match('/\\\(\w+)$/', $request->get('entityType'), $matches);
            $classShortName = $matches[1];
            $result = (new HTTPCache())
                ->setPartner($authenticatedPartner)
                ->setRouteName($routeName)
                ->setRequestURI($requestURI)
                ->setType($type)
                ->setClassShortName($classShortName);
            $this->entityManager->persist($result);
            $this->entityManager->flush();
        }
        // Set requested http cache attribute $configuration->getName() with corresponding hydrated entity instance
        $request->attributes->set($entityAttributeName, $result);
        // Return success state: instance was retrieved!
        return true;
    }

    /**
     * Get user data from JWT provider by request header.
     *
     * @param Request $request
     *
     * @return Partner
     *
     * @see https://symfonycasts.com/screencast/symfony-rest4/jwt-guard-authenticator
     */
    private function getUserFromJWT(Request $request): Partner
    {
        // Get JWT
        $token = $this->tokenExtractor->extract($request);
        if (!$token) {
            throw new AccessDeniedHttpException('No Authorization Bearer Token found');
        }
        // Get authenticated Partner with uuid from JWT payload (cache also the query thanks to DQL)
        try {
            $data = $this->jwtEncoder->decode($token);
            /** @var PartnerRepository $partnerRepository */
            $partnerRepository = $this->entityManager->getRepository(Partner::class);
            /** @var Partner|JWTUser $partner */
            // TODO: use query builder in repository class to cache query!
            if (\is_null($partner = $partnerRepository->find($data['uuid']))) {
                throw new UserNotFoundException(
                    'email',
                    $data['email']
                );
            }
        } catch (JWTDecodeFailureException $exception) {
            throw new UnauthorizedHttpException('Invalid JWT Token');
        }
        // Return Partner retrieved from query due to necessary association with HTTPCache instance
        return $partner;
    }

    /**
     * Check if request URI is associated to a collection result.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isCollection(Request $request): bool
    {
        $isCollection = (bool) $request->get('isCollection');
        // Get requested entity uuid which will be used
        $isEntityUuid = preg_match('/\/([\w-]{36})(\?[\w-&=]+)?$/', $request->getRequestUri(), $matches);
        // Check correct config for single entity (Client, Partner, Phone, Offer) instance GET request
        if (\is_null($isCollection) || !$isCollection && !$isEntityUuid) {
            throw new RuntimeException('Request "isCollection" attribute null or wrong value');
        }
        return $isCollection;
    }

    /**
     * Get request corresponding HTTPCache instance if it exists.
     *
     * @param string  $requestURI           a particular unique request URI.
     * @param Partner $authenticatedPartner
     *
     * @return HTTPCache|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function queryHTTPCache(string $requestURI, Partner $authenticatedPartner): ?HTTPCache
    {
        /** @var HTTPCacheRepository $repository */
        $repository = $this->entityManager->getRepository(HTTPCache::class);
        // Find data and get entity instance (HTTPCache metadata and query with DQL are cached!)
        return $repository->findOneByPartnerAndRequestURI($authenticatedPartner, $requestURI);
    }

    /**
     * Check if this custom converter must be called depending on configuration.
     *
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        // Check correct entity argument name
        return $configuration->getClass() === HTTPCache::class;
    }
}