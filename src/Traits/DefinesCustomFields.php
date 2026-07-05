<?php

namespace RSE\DynaFields\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use RSE\DynaFields\Models\CustomField;

trait DefinesCustomFields
{
    /**
     * Fields directly owned by this model instance.
     */
    public function customFields(): HasMany
    {
        $fieldModel = config('dynafields.models.custom_field', CustomField::class);

        return $this->hasMany($fieldModel, 'owner_id')
            ->where('owner_type', $this->getMorphClass());
    }

    /**
     * All fields visible for subjects of the given type when owned by this instance.
     * Includes global, type-scoped, and this owner's fields (with inheritance if supported).
     */
    public function allCustomFieldsForSubject(string $subjectType): Collection
    {
        $fieldModel = config('dynafields.models.custom_field', CustomField::class);

        return $fieldModel::forSubject($subjectType, $this)
            ->orderBy('order')
            ->get();
    }
}
