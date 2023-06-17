<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sf-join.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\SfJoin\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Model for a product
 */
class Product extends AbstractEntity
{
    protected string $title = '';

    protected int $maxTimestamp = 0;

    /**
     * @var ObjectStorage<Category>|null
     */
    protected $categories = null;

    /**
     * @var ObjectStorage<Property>|null
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $properties = null;

    public function __construct()
    {
        $this->categories = new ObjectStorage();
        $this->properties = new ObjectStorage();
    }

    /**
     * Called again with initialize object, as fetching an entity from the DB does not use the constructor
     */
    public function initializeObject()
    {
        $this->categories = new ObjectStorage();
        $this->properties = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getMaxTimestamp(): int
    {
        return $this->maxTimestamp;
    }

    public function setMaxTimestamp(int $maxTimestamp): void
    {
        $this->maxTimestamp = $maxTimestamp;
    }

    /**
     * @return ObjectStorage|Category[]
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return ObjectStorage|Property[]
     */
    public function getProperties(): ObjectStorage
    {
        return $this->properties;
    }

    public function setProperties(ObjectStorage $properties): void
    {
        $this->properties = $properties;
    }
}
