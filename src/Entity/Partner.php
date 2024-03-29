<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Partner
 *
 * Define an API consumer which has associated clients.
 * Please note that partner can have access to a particular list of phones
 * event if it is not needed to run application.
 *
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *          "show_partner",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *     )
 * )
 * @Hateoas\Relation(
 *      "offers",
 *      href=@Hateoas\Route(
 *          "list_offers_per_partner",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *     ),
 *    exclusion=@Hateoas\Exclusion(excludeIf="expr(not is_granted('ROLE_API_ADMIN'))")
 * )
 * @Hateoas\Relation(
 *      "clients",
 *      href=@Hateoas\Route(
 *          "list_clients_per_partner",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *     ),
 *     exclusion=@Hateoas\Exclusion(excludeIf="expr(not is_granted('ROLE_API_ADMIN'))")
 * )
 * @Hateoas\Relation(
 *      "clients",
 *      href=@Hateoas\Route(
 *          "list_clients",
 *          absolute=true
 *     ),
 *     exclusion=@Hateoas\Exclusion(excludeIf="expr(is_granted('ROLE_API_ADMIN'))")
 * )
 *
 * @ORM\Entity(repositoryClass=PartnerRepository::class)
 * @ORM\Table(name="partners")
 */
class Partner implements UserInterface, JWTUserInterface
{
    /**
     * Define an APi admin role (associated to a partner special account).
     */
    const API_ADMIN_ROLE = 'ROLE_API_ADMIN';

    /**
     * Define a partner default role.
     */
    const DEFAULT_PARTNER_ROLE = 'ROLE_API_CONSUMER';

    /**
     * Define a set of partner roles.
     */
    const PARTNER_ROLES = [
        self::API_ADMIN_ROLE,
        self::DEFAULT_PARTNER_ROLE
    ];

    /**
     * Define a set of partner status.
     */
    const PARTNER_TYPES = [
        'Magasin',
        'Spécialiste téléphonie',
        'Vente en ligne'
    ];

    /**
     * @var UuidInterface
     *
     * A universal unique identifier to differentiate a result
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     *
     * @Serializer\Groups({"Partner_list", "Partner_detail"})
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
     * @Serializer\Groups({"Partner_detail"})
     */
    private $type;

    /**
     * @var string|null
     *
     * A particular name (not unique so not used as identifier in app)
     *
     * @ORM\Column(type="string", length=45)
     *
     * @Serializer\Groups({"Partner_list", "Partner_detail"})
     */
    private $username;

    /**
     * @var string|null
     *
     * A unique email used for account
     *
     * @ORM\Column(type="string", length=320, unique=true)
     *
     * @Serializer\Groups({"Partner_list", "Partner_detail"})
     */
    private $email;

    /**
     * @var string|null
     *
     * A hashed password
     *
     * @ORM\Column(type="string", length=98, unique=true)
     *
     * @Serializer\Exclude
     */
    private $password;

    /**
     * @var string|null an unmapped property
     */
    private $plainPassword;

    /**
     * @var array
     *
     * A list of permissions
     *
     * @ORM\Column(type="array")
     *
     * @Serializer\Groups({"Partner_detail"})
     * @Serializer\Type("array<string>")
     */
    private $roles;

    /**
     * @var \DateTimeImmutable
     *
     * A date of creation
     *
     * @ORM\Column(type="datetime_immutable")
     *
     * @Serializer\Groups({"Partner_list", "Partner_detail"})
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
     * @Serializer\Groups({"Partner_detail"})
     * @Serializer\Type("DateTimeImmutable")
     */
    private $updateDate;

    /**
     * @var Collection|Offer[]
     *
     * @ORM\OneToMany(targetEntity=Offer::class, mappedBy="partner", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Serializer\Exclude
     */
    private $offers;

    /**
     * @var Collection|Client[]
     *
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="partner", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude
     */
    private $clients;

    /**
     * Partner constructor.
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
        $this->offers = new ArrayCollection();
        $this->clients = new ArrayCollection();
    }

    /**
     * Creates a new instance from a given JWT payload
     * with Lexik JWTUserInterface implementation.
     *
     * {@inheritdoc}
     *
     * @see https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/8-jwt-user-provider.md
     */
    public static function createFromPayload($username, array $payload): JWTUserInterface
    {
        return (new self()) // Uuid is provided by constructor!
            ->setEmail($payload['email'])
            ->setRoles($payload['roles']);
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
        ->addPropertyConstraint('username',
            new Assert\NotBlank()
        )
        ->addPropertyConstraint('email',
            new Assert\Email())
        ->addPropertyConstraints('plainPassword', [
            new Assert\NotBlank(),
            new Assert\Regex([
                'pattern' => '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W]).{8,}$/'
            ])
        ])
        ->addPropertyConstraint('type',
            new Assert\Choice([
                'choices' => self::PARTNER_TYPES
        ]))
        ->addPropertyConstraint('roles',
            new Assert\Choice([
                'multiple' => true,
                'choices'  => self::PARTNER_ROLES
        ]));
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
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setUsername(string $name): self
    {
        $this->username = $name;

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
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword|null
     *
     * @return $this
     */
    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        // Guarantee at least one role for every userS
        if (empty($this->roles)) {
            $this->roles[] = Partner::DEFAULT_PARTNER_ROLE;
        }

        return array_unique($this->roles);
    }

    /**
     * @param array $roles
     *
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection|Offer[]
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    /**
     * @param Offer $offer
     *
     * @return $this
     */
    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers[] = $offer;
            $offer->setPartner($this);
        }

        return $this;
    }

    /**
     * @param Offer $offer
     *
     * @return $this
     */
    public function removeOffer(Offer $offer): self
    {
        if ($this->offers->contains($offer)) {
            $this->offers->removeElement($offer);
            // set the owning side to null (unless already changed)
            if ($offer->getPartner() === $this) {
                $offer->setPartner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    /**
     * @param array|Client[] $clients
     *
     * @return $this
     */
    public function setClients(array $clients): self
    {
        foreach ($clients as $client) {
            $this->addClient($client);
            $client->setPartner($this);
        }

        return $this;
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
            $client->setPartner($this);
        }

        return $this;
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    public function removeClient(Client $client): self
    {
        if ($this->clients->contains($client)) {
            $this->clients->removeElement($client);
            // set the owning side to null (unless already changed)
            if ($client->getPartner() === $this) {
                $client->setPartner(null);
            }
        }

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

    /**
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }
}