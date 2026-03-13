<?php

namespace RSE\DynaFields\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use RSE\DynaFields\Models\CustomField;
use RSE\DynaFields\Models\CustomFieldValue;

trait HasCustomFields
{
    public function customFieldValues(): MorphMany
    {
        $valueModel = config('dynafields.models.custom_field_value', CustomFieldValue::class);

        return $this->morphMany($valueModel, 'subject', 'subject_type', 'subject_id');
    }

    /**
     * Override to return the owner instance for instance-scoped field resolution.
     * Return null to only show global and type-scoped fields.
     *
     * Example:
     *   public function customFieldOwner(): ?Model { return $this->group; }
     */
    public function customFieldOwner(): ?Model
    {
        return null;
    }

    /**
     * Resolve all applicable custom fields for this model instance.
     * Includes: global, type-scoped, and owner instance-scoped (with inheritance).
     */
    public function resolveCustomFields(): Collection
    {
        $fieldModel = config('dynafields.models.custom_field', CustomField::class);

        return $fieldModel::forSubject(static::class, $this->customFieldOwner())
            ->orderBy('order')
            ->get();
    }

    /**
     * Sync custom field values from a key-value array (field_id => value).
     * Creates new values and updates existing ones.
     */
    public function syncCustomFields(array $fields): void
    {
        foreach ($fields as $fieldId => $value) {
            $this->customFieldValues()->updateOrCreate(
                ['custom_field_id' => (string) $fieldId],
                ['value' => (string) $value]
            );
        }
    }

    /**
     * Get the stored value for a single custom field.
     */
    public function getCustomFieldValue(string $fieldId): ?string
    {
        return $this->customFieldValues
            ->firstWhere('custom_field_id', $fieldId)
            ?->value;
    }

    /**
     * Set a single custom field value.
     */
    public function setCustomFieldValue(string $fieldId, string $value): void
    {
        $this->customFieldValues()->updateOrCreate(
            ['custom_field_id' => $fieldId],
            ['value' => $value]
        );
    }
}
