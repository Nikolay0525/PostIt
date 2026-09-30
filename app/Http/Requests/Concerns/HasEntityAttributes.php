<?php

namespace App\Http\Requests\Concerns;

/**
 * Names the request's fields after the entity it works on, from lang/{locale}/attributes.php
 * (e.g. "name" is "назва групи" for a group but "ім’я" for a user). Fields not listed there fall
 * back to the entity-neutral names in validation.attributes.
 *
 * The using class sets `protected string $attributeEntity = 'group';`.
 */
trait HasEntityAttributes
{
    public function attributes(): array
    {
        $attributes = __("attributes.{$this->attributeEntity}");

        // __() returns the key itself when the entity has no entry.
        return is_array($attributes) ? $attributes : [];
    }
}
