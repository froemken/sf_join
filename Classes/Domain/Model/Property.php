<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sf-join.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\SfJoin\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model for a property
 */
class Property extends AbstractEntity
{
    /**
     * @Extbase\Validate("NotEmpty")
     */
    protected string $name = '';

    /**
     * @Extbase\Validate("NotEmpty")
     */
    protected string $value = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
