<?php

namespace RSE\DynaFields\Contracts;

/**
 * Implement this interface on owner models that support hierarchical
 * field inheritance (e.g. a parent Group whose fields cascade to children).
 */
interface SupportsFieldInheritance
{
    /**
     * Return all ancestor IDs (including self) from root → self.
     * Fields defined for any of these owner IDs will be shown on the subject.
     *
     * @return array<string|int>
     */
    public function getAncestorOwnerIds(): array;
}
