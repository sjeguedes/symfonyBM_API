<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Partner.
 *
 * @ORM\Entity(repositoryClass=PartnerRepository::class)
 * @ORM\Table(name="partners")
 */
class Partner
{
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
     * @ORM\Column(type="string", length=320)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=60)
     */
    private $encodedPassword;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $roles;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $creationDate;

    /**
     * @var \DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $updateDate;

    /**
     * @var Collection|Offer[]
     *
     * @ORM\OneToMany(targetEntity=Offer::class, mappedBy="partner", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $offers;

    /**
     *  @var Collection|Client[]
     *
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="partner", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $clients;

    /**
     * Partner constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->offers = new ArrayCollection();
        $this->clients = new ArrayCollection();
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
     * @return string|null
     */
    public function getEncodedPassword(): ?string
    {
        return $this->encodedPassword;
    }

    /**
     * @param string $encodedPassword
     *
     * @return $this
     */
    public function setEncodedPassword(string $encodedPassword): self
    {
        $this->encodedPassword = $encodedPassword;

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
     * @param string $plainPassword
     *
     * @return $this
     */
    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
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
