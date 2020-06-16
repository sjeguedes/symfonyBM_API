<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Offer.
 *
 * @ORM\Entity(repositoryClass=OfferRepository::class)
 * @ORM\Table(name="offers")
 */
class Offer
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    private $uuid;

    /**
     * @var Partner
     *
     * @ORM\ManyToOne(targetEntity=Partner::class, inversedBy="offers")
     * @ORM\JoinColumn(name="partner_uuid", referencedColumnName="uuid", unique=true)
     */
    private $partner;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="offers")
     * @ORM\JoinColumn(name="product_uuid", referencedColumnName="uuid", unique=true)
     */
    private $product;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $creationDate;

    /**
     * Offer constructor.
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
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
     *
     * @return $this
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

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
}
