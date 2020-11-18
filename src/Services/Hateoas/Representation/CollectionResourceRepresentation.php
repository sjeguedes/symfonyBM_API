<?php

declare(strict_types=1);

namespace App\Services\Hateoas\Representation;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class CollectionResourceRepresentation
 *
 * Adapt hateoas collection representation to API needs.
 */
class CollectionResourceRepresentation extends CollectionRepresentation
{
    /**
     * @var bool
     *
     * @Serializer\Exclude
     */
    private $isItemUnique;

    /**
     * @var string
     *
     * @Serializer\Exclude
     */
    private $resourceClassName;

    /**
     * CustomCollectionRepresentation constructor.
     *
     * @param \IteratorAggregate|Paginator $resources
     * @param string                       $resourceClassName
     */
    public function __construct(\IteratorAggregate $resources, string $resourceClassName)
    {
        parent::__construct($resources);
        $this->resourceClassName = $resourceClassName;
        $this->isItemUnique = $resources->count() > 1 ? false : true;
    }

    public function getItemsLabel(): string
    {
        $plural = $this->isItemUnique ? '' : 's';
        sprintf('%1$s%2$s', $this->resourceClassName, $plural);
        return $this->resourceClassName;
    }
}
