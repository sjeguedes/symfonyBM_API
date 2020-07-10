<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Client
 *
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 * @ORM\Table(name="clients")
 */
class Client
{
    const INDIVIDUAL_TYPE = 'particulier';

    const PROFESSIONAL_TYPE = 'professionnel';

    /**
     * Define a set of client status.
     */
    const CLIENT_TYPES = [
        self::INDIVIDUAL_TYPE,
        self::PROFESSIONAL_TYPE
    ];

    /**
     * @var UuidInterface
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=320, unique=true)
     */
    private $email;

    /**
     * @var Partner
     *
     * @ORM\ManyToOne(targetEntity=Partner::class, inversedBy="clients")
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
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $updateDate;

    /**
     * Client constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    /**
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @return string|null
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
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Partner|null
     */
    public function getPartner(): ?Partner
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
     * @return \DateTimeImmutable|null
     */
    public function getCreationDate(): ?\DateTimeImmutable
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
     * @return \DateTimeImmutable|null
     */
    public function getUpdateDate(): ?\DateTimeImmutable
    {
        return $this->updateDate;
    }

    /**
     * @param \DateTimeImmutable|null $updateDate
     *
     * @return $this
     */
    public function setUpdateDate(?\DateTimeImmutable $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }
}
