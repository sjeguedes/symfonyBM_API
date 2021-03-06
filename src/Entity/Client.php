<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Client
 *
 * Define an individual or professional client associated to a partner.
 *
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *          "show_client",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *      )
 * )
 * @Hateoas\Relation(
 *      "create",
 *      href=@Hateoas\Route(
 *          "create_client",
 *          absolute=true
 *      )
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href=@Hateoas\Route(
 *          "delete_client",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *      )
 * )
 * @Hateoas\Relation(
 *      "partner",
 *      href=@Hateoas\Route(
 *          "show_partner",
 *          parameters={"uuid"="expr(object.getPartner().getUuid().toString())"},
 *          absolute=true
 *     )
 * )
 *
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 * @ORM\Table(name="clients")
 */
class Client
{
    /**
     * Define an individual type label.
     */
    const INDIVIDUAL_TYPE = 'Particulier';

    /**
     * Define a professional type label.
     */
    const PROFESSIONAL_TYPE = 'Professionnel';

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
     * A universal unique identifier to differentiate a result
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     *
     * @Serializer\Groups({"Client_list", "Client_detail"})
     * @Serializer\Type("string")
     */
    private $uuid;

    /**
     * @var string|null
     *
     * A category
     *
     * @ORM\Column(type="string", length=45)
     *
     * @Serializer\Groups({"Client_detail"})
     */
    private $type;

    /**
     * @var string|null
     *
     * A particular name (not unique so not used as identifier in app)
     *
     * @ORM\Column(type="string", length=45)
     *
     * @Serializer\Groups({"Client_list", "Client_detail"})
     */
    private $name;

    /**
     * @var string|null
     *
     * A unique email to distinguish client
     *
     * @ORM\Column(type="string", length=320, unique=true)
     *
     * @Serializer\Groups({"Client_list", "Client_detail"})
     */
    private $email;

    /**
     * @var Partner|null
     *
     * @ORM\ManyToOne(targetEntity=Partner::class, cascade={"persist"}, inversedBy="clients")
     * @ORM\JoinColumn(name="partner_uuid", referencedColumnName="uuid", nullable=false)
     *
     * @Serializer\Exclude
     */
    private $partner;

    /**
     * @var \DateTimeImmutable
     *
     * A date of creation
     *
     * @ORM\Column(type="datetime_immutable")
     *
     * @Serializer\Groups({"Client_list", "Client_detail"})
     * @Serializer\Type("DateTimeImmutable")
     */
    private $creationDate;

    /**
     * @var \DateTimeImmutable
     *
     * A date of last update (the same as date of creation by default)
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Serializer\Groups({"Client_detail"})
     * @Serializer\Type("DateTimeImmutable")
     */
    private $updateDate;

    /**
     * Client constructor.
     *
     * @param UuidInterface|null $uuid
     *
     * @throws \Exception
     */
    public function __construct(UuidInterface $uuid = null)
    {
        $this->uuid = $uuid ?? Uuid::uuid4();
        $this->creationDate = new \DateTimeImmutable();
        $this->updateDate = new \DateTimeImmutable();
    }

    /**
     * Load validation constraints automatically when this entity is validated.
     *
     * @param ClassMetadata $metadata
     *
     * @return void
     *
     * @see Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(
            new UniqueEntity([
                'fields' => 'email'
        ]))
        ->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\NotNull()
        ])
        ->addPropertyConstraints('email', [
            new Assert\Email(),
            new Assert\NotNull()
        ])
        ->addPropertyConstraints('type', [
            new Assert\Choice([
                'choices' => self::CLIENT_TYPES
            ]),
            new Assert\NotNull()
        ])
        ->addPropertyConstraint('partner',
            new  Assert\Valid()
        );
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
