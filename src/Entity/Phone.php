<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PhoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Phone
 *
 * Define a particular phone with some features to distinguish it (e.g. brand, model, color, etc...).
 *
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *          "show_phone",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *     )
 * )
 * @Hateoas\Relation(
 *      "offers",
 *      href=@Hateoas\Route(
 *          "list_offers_per_phone",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *     ),
 *    exclusion=@Hateoas\Exclusion(excludeIf="expr(not is_granted('ROLE_API_ADMIN'))")
 * )
 *
 * @ORM\Entity(repositoryClass=PhoneRepository::class)
 * @ORM\Table(name="phones")
 */
class Phone
{
    /**
     * Define a set of phones categories.
     */
    const PHONE_TYPES = [
        'Premium',
        'Exclusivité',
        'Reconditionné',
        'Bon plan',
        'Petit prix'
    ];

    /**
     * @var UuidInterface
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     *
     * @Serializer\Groups({"Phone_list", "Phone_detail"})
     * @Serializer\Type("string")
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45)
     *
     * @Serializer\Groups({"Phone_detail", "Phone_detail"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45)
     *
     * @Serializer\Groups({"Phone_list", "Phone_detail"})
     */
    private $brand;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45, unique=true)
     *
     * @Serializer\Groups({"Phone_list", "Phone_detail"})
     */
    private $model;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45)
     *
     * @Serializer\Groups({"Phone_detail"})
     */
    private $color;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @Serializer\Groups({"Phone_detail"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=6, scale=2)
     *
     * @Serializer\Groups({"Phone_list", "Phone_detail"})
     * @Serializer\Type("double")
     */
    private $price;

    /**
     * @var Collection|Offer[]
     *
     * @ORM\OneToMany(targetEntity=Offer::class, mappedBy="phone", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Serializer\Exclude
     */
    private $offers;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     *
     * @Serializer\Groups({"Phone_list", "Phone_detail"})
     */
    private $creationDate;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Serializer\Groups({"Phone_detail"})
     */
    private $updateDate;

    /**
     * Phone constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->creationDate = new \DateTimeImmutable();
        $this->updateDate = new \DateTimeImmutable();
        $this->offers = new ArrayCollection();
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
                'fields' => ['brand', 'model']
        ]))
        ->addPropertyConstraint('brand',
            new Assert\NotBlank()
        )
        ->addPropertyConstraint('model',
            new Assert\NotBlank()
        )
        ->addPropertyConstraint('color',
            new Assert\NotBlank()
        )
        ->addPropertyConstraint('description',
            new Assert\NotBlank()
        )
        ->addPropertyConstraints('price', [
            new Assert\Type([
                'type' => 'integer'
            ]),
            new Assert\Range([
                'min' => 100,
                'max' => 1200,
            ])
        ])
        ->addPropertyConstraint('type',
            new Assert\Choice([
                'choices' => self::PHONE_TYPES
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
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     *
     * @return $this
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * @param string $model
     *
     * @return $this
     */
    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string $color
     *
     * @return $this
     */
    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * @param string $price
     *
     * @return $this
     */
    public function setPrice(string $price): self
    {
        $this->price = $price;

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
            $offer->setPhone($this);
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
            if ($offer->getPhone() === $this) {
                $offer->setPhone(null);
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
}
