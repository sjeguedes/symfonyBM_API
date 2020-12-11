<?php

declare(strict_types=1);

namespace App\Entity;

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
     * Define resource distinction.
     */
    const RESOURCE_TYPES = [
        'Collection',
        'Entity'
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
     * @ORM\Column(type="string", length=45, unique=true)
     */
    private $routeName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=15)
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=15)
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
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->creationDate = new \DateTimeImmutable();
        $this->updateDate = new \DateTimeImmutable();
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
     * @param UuidInterface $uuid
     *
     * @return $this
     */
    public function setEtagToken(UuidInterface $uuid): self
    {
        $this->etagToken = md5(uniqId($uuid->toString()));

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
