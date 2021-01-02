<?php

declare(strict_types=1);

namespace App\Services\API\Cache;

/**
 * Class DoctrineCacheResultListIterator
 *
 * Manage API paginated collection for cached data.
 */
final class DoctrineCacheResultListIterator implements \IteratorAggregate
{
    /**
     * @var int
     */
    private $itemsTotalCount;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var array
     */
    private $selectedItems;

    /**
     * DoctrineCacheResultListIterator constructor.
     *
     * @param int      $itemsTotalCount
     * @param int|null $firstResult
     * @param int|null $maxResults
     * @param array    $selectedItems
     */
    public function __construct(
        int $itemsTotalCount,
        ?int $firstResult,
        ?int $maxResults,
        array $selectedItems
    ) {
        $this->itemsTotalCount = $itemsTotalCount;
        $this->offset = $firstResult;
        $this->limit = $maxResults;
        $this->selectedItems = $selectedItems;
    }

    /**
     * Get all existing items total count.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->itemsTotalCount;
    }

    /**
     * Get offset parameter.
     *
     * @return int|null
     */
    public function getFirstResult(): ?int
    {
        return $this->offset ?? 0;
    }

    /**
     * Retrieve selected items iterator to handle list.
     *
     * Please note that \ArrayIterator::count() returns selected items total count.
     * The same result can be get with "iterator_count($this->getIterator());".
     *
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->selectedItems);
    }

    /**
     * Get limit parameter.
     *
     * @return int|null
     */
    public function getMaxResults(): ?int
    {
        return $this->limit ?? $this->count();
    }
}