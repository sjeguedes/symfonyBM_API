<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\AbstractRefreshToken;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class JWTRefreshToken
 *
 * Override Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken to have another table name and particular properties.
 *
 * @ORM\Entity(repositoryClass=RefreshTokenRepository::class)
 * @ORM\Table(name="jwt_refresh_tokens")
 */
class JWTRefreshToken extends AbstractRefreshToken
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    private $uuid;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $creationDate;

    /**
     * JWTRefreshToken constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->creationDate = new \DateTimeImmutable();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->uuid->toString();
    }
}