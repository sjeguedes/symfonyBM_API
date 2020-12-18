<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AbstractAPIRepository;
use App\Repository\HTTPCacheRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class HTTPCache
 *
 * Define main HTTP cache memory entries for GET requests.
 *
 * @ORM\Entity(repositoryClass=HTTPCacheRepository::class)
 * @ORM\Table(name="http_caches")
 *
 * @see https://symfony.com/doc/current/http_cache.html
 */
class HTTPCache
{
    /**
     * Define which kind of HTTP cache is used for a particular response.
     */
    const NONE = 0;
    const PROXY_CACHE = 1;
    const PRIVATE_CACHE = 2;

    /**
     * Define a resource distinction.
     */
    const RESOURCE_TYPES = [
        'list'   => 'collection',
        'unique' => 'entity'
    ];

    /**
     * @var UuidInterface
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    private $uuid;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    private $routeName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    private $requestURI;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    private $classShortName;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     */
    private $ttlExpiration;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $etagToken;

    /**
     * @var Partner|null
     *
     * @ORM\ManyToOne(targetEntity=Partner::class)
     * @ORM\JoinColumn(name="partner_uuid", referencedColumnName="uuid", nullable=false)
     */
    private $partner;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $creationDate;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $updateDate;

    /**
     * HTTPCache constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->creationDate = new \DateTimeImmutable();
        // Last-Modified (to use with date format specification)
        $this->updateDate = new \DateTimeImmutable();
        // Etag
        $this->etagToken = $this->generateUniqueEtag($this->updateDate);
        // Cache-Control max age or Expires (ttl to use with date format specification)
        $this->ttlExpiration = AbstractAPIRepository::DEFAULT_CACHE_TTL;
    }

    /**
     * Create a custom Etag token.
     *
     * @param \DateTimeImmutable $updateDate
     *
     * @return string
     */
    private function generateUniqueEtag(\DateTimeImmutable $updateDate): string
    {
        return md5(
            uniqId($this->uuid->toString() . $updateDate->getTimestamp())
        );
    }

    /**
     * Generate an expiration date.
     *
     * @return \DateTimeInterface
     *
     * @see Response::setExpires() method
     *
     * @throws \Exception
     */
    public function generateExpirationDate(): \DateTimeInterface
    {
        return new \DateTime("+{$this->ttlExpiration} seconds");
    }

    /**
     * Generate a compatible datetime for "strtotime" php function.
     *
     * @return \DateTimeInterface
     *
     * @see https://www.php.net/manual/en/function.strtotime.php
     *
     * @throws \Exception
     */
    public function generateExpirationTime(): string
    {
        return "+{$this->ttlExpiration} seconds";
    }

    /**
     * Generate an array of options to be used in Response::setCache() method.
     *
     * @param bool $isSensioCacheUsed a Sensio framework bundle compatible token must be defined
     *
     * @return array
     */
    public function generateHeadersOptions($isSensioCacheUsed = true): array
    {
        // Format "Etag" as expected to also work with Sensio framework bundle cache annotation
        !$isSensioCacheUsed ?: $this->setEtagTokenToCompareWithSensioCache($this->getEtagToken());
        // Define options as expected in Response::setCache() method
        $options = [
            'etag'          => $this->getEtagToken(),
            'last_modified' => $this->getUpdateDate(),
            'max_age'       => $this->getTtlExpiration(),
            'public'        => true, // By default if not precised
        ];
        // Check if null value(s) exist(s) to avoid issue
        if (!empty($results = array_filter($options, function ($value) { return null === $value; }))) {
            throw new \RuntimeException('HTTP cache Header(s) "null" value(s) found');
        }
        return $options;
    }

    /**
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * Get route name which corresponds to GET request.
     *
     * @return string|null
     */
    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     *
     * @return $this
     */
    public function setRouteName(string $routeName): self
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestURI(): ?string
    {
        return $this->requestURI;
    }

    /**
     * @param string $requestURI
     *
     * @return $this
     */
    public function setRequestURI(string $requestURI): self
    {
        $this->requestURI = $requestURI;

        return $this;
    }

    /**
     * @return string|null a resource type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): self
    {
         $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getClassShortName(): ?string
    {
        return $this->classShortName;
    }

    /**
     * Get entity short name.
     *
     * @param string $classShortName
     *
     * @return $this
     */
    public function setClassShortName(string $classShortName): self
    {
        $this->classShortName = $classShortName;

        return $this;
    }

    /**
     * Get HTTP cache time to live which can be used for "Cache-Control max-age" or "Expires" headers.
     * After this duration in seconds, this cache entry will expire.
     *
     * @return int|null
     */
    public function getTtlExpiration(): ?int
    {
        return $this->ttlExpiration;
    }

    /**
     * @param int $ttlExpiration a duration in seconds
     *
     * @return $this
     */
    public function setTtlExpiration(int $ttlExpiration): self
    {
        $this->ttlExpiration = $ttlExpiration;

        return $this;
    }

    /**
     * Get token value to use for "Etag" header.
     *
     * @return string|null
     */
    public function getEtagToken(): ?string
    {
        return $this->etagToken;
    }

    /**
     * @param \DateTimeImmutable $updateDate
     *
     * @return $this
     */
    public function setEtagToken(\DateTimeImmutable $updateDate): self
    {
        // Quotation marks are necessary to respect Etag format
        $this->etagToken = $this->generateUniqueEtag($updateDate) . '"';

        return $this;
    }

    /**
     * set "Etag" header as expected for Sensio framework bundle when using "Cache" Annotation.
     *
     * @param string $etagToken
     *
     * @return $this
     */
    public function setEtagTokenToCompareWithSensioCache(string $etagToken): self
    {
        // Use hash() function to format token as it is in HTTPCacheListener
        // to make Response:isNotModified() work.
        $this->etagToken = hash('sha256', $etagToken);

        return $this;
    }

    /**
     * @return Partner|null
     */
    public function getPartner(): Partner
    {
        return $this->partner;
    }

    /**
     * @param Partner|null $partner
     *
     * @return $this
     */
    public function setPartner(?Partner $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTimeImmutable $creationDate
     *
     * @return $this
     */
    public function setCreationDate(\DateTimeImmutable $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get an update date value which can be used for "Last-Modified" header.
     *
     * @return \DateTimeImmutable
     */
    public function getUpdateDate(): \DateTimeImmutable
    {
        return $this->updateDate;
    }

    /**
     * @param \DateTimeImmutable $updateDate
     *
     * @return $this
     */
    public function setUpdateDate(\DateTimeImmutable $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }
}
