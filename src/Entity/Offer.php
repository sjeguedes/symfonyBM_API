<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Offer
 *
 * Define which phones would be associated to partners
 * even if it is not specified in application requirements.
 *
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *          "show_offer",
 *          parameters={"uuid"="expr(object.getUuid().toString())"},
 *          absolute=true
 *     )
 * )
 * @Hateoas\Relation(
 *      "partner",
 *      href=@Hateoas\Route(
 *          "show_partner",
 *          parameters={"uuid"="expr(object.getPartner().getUuid().toString())"},
 *          absolute=true
 *      ),
 *     embedded="expr(object.getPartner())"
 * )
 * @Hateoas\Relation(
 *      "phone",
 *      href=@Hateoas\Route(
 *          "show_phone",
 *          parameters={"uuid"="expr(object.getPhone().getUuid().toString())"},
 *          absolute=true
 *     ),
 *     embedded="expr(object.getPhone())"
 * )
 *
 * @ORM\Entity(repositoryClass=OfferRepository::class)
 * @ORM\Table(name="offers")
 */
class Offer
{
    /**
     * @var UuidInterface
     *
     * A universal unique identifier to differentiate a result
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     *
     * @Serializer\Groups({"Offer_list", "Offer_detail"})
     * @Serializer\Type("string")
     */
    private $uuid;

    /**
     * @var Partner|null
     *
     * @ORM\ManyToOne(targetEntity=Partner::class, inversedBy="offers")
     * @ORM\JoinColumn(name="partner_uuid", referencedColumnName="uuid")
     *
     * @Serializer\Exclude
     */
    private $partner;

    /**
     * @var Phone|null
     *
     * @ORM\ManyToOne(targetEntity=Phone::class, inversedBy="offers")
     * @ORM\JoinColumn(name="phone_uuid", referencedColumnName="uuid")
     *
     * @Serializer\Exclude
     */
    private $phone;

    /**
     * @var \DateTimeImmutable
     *
     * A date of creation
     *
     * @ORM\Column(type="datetime_immutable")
     *
     * @Serializer\Groups({"Offer_list", "Offer_detail"})
     * @Serializer\Type("DateTimeImmutable")
     */
    private $creationDate;

    /**
     * Offer constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->creationDate = new \DateTimeImmutable();
        // No need to have an update date since an offer deletion must be chosen if necessary!
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
        $metadata->addPropertyConstraint('partner',
            new Assert\Valid()
        )
        ->addPropertyConstraint('phone',
            new Assert\Valid()
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
     * @return Phone|null
     */
    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    /**
     * @param Phone|null $phone
     *
     * @return $this
     */
    public function setPhone(?Phone $phone): self
    {
        $this->phone = $phone;

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
}
