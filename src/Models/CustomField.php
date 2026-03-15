<?php

namespace RSE\DynaFields\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RSE\DynaFields\Concerns\HasUlidPrimaryKey;
use RSE\DynaFields\Contracts\SupportsFieldInheritance;
use Spatie\Translatable\HasTranslations;

class CustomField extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasUlidPrimaryKey;
    use SoftDeletes;

    protected $table = 'custom_fields';

    protected $guarded = [];

    public array $translatable = ['name', 'description'];

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn (Builder $q) => $q->where('active', true));
        static::addGlobalScope('order', fn (Builder $q) => $q->orderBy('order')->orderBy('created_at'));
    }

    protected function casts(): array
    {
        return [
            'name'                 => 'json',
            'description'          => 'json',
            'data'                 => 'json',
            'metadata'             => 'json',
            'order'                => 'integer',
            'is_mandatory'         => 'boolean',
            'is_unique'            => 'boolean',
            'is_printable'         => 'boolean',
            'is_fixed'             => 'boolean',
            'active'               => 'boolean',
            'searchable'           => 'boolean',
            'depends_on_condition' => 'json',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo('owner', 'owner_type', 'owner_id');
    }

    public function values(): HasMany
    {
        $valueModel = config('dynafields.models.custom_field_value', CustomFieldValue::class);

        return $this->hasMany($valueModel, 'custom_field_id');
    }

    public function dependsOnField(): BelongsTo
    {
        return $this->belongsTo(static::class, 'depends_on_field_id');
    }

    public function dependentFields(): HasMany
    {
        return $this->hasMany(static::class, 'depends_on_field_id');
    }

    /** Global fields (no owner, no scope) */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('owner_type')->whereNull('owner_id');
    }

    /** Fields scoped to a specific subject type (no owner instance needed) */
    public function scopeForSubjectType(Builder $query, string $subjectType): Builder
    {
        return $query->where('owner_type', $subjectType)->whereNull('owner_id');
    }

    /**
     * Filter by section scope. Includes fields with scope=null (truly global)
     * and fields matching the given scope string.
     */
    public function scopeForScope(Builder $query, string $scope): Builder
    {
        return $query->where(function (Builder $q) use ($scope) {
            $q->whereNull('scope')->orWhere('scope', $scope);
        });
    }

    /**
     * All fields applicable for a given subject type + optional owner instance.
     * Resolves: global + type-scoped + owner instance-scoped (+ inherited if owner supports it).
     */
    public function scopeForSubject(Builder $query, string $subjectType, ?Model $owner = null): Builder
    {
        return $query->where(function (Builder $q) use ($subjectType, $owner) {
            // 1. Global fields
            $q->where(function (Builder $inner) {
                $inner->whereNull('owner_type')->whereNull('owner_id');
            });

            // 2. Type-scoped (owner_type = subjectType, no owner_id)
            $q->orWhere(function (Builder $inner) use ($subjectType) {
                $inner->where('owner_type', $subjectType)->whereNull('owner_id');
            });

            // 3. Instance-scoped from owner + optional inheritance
            if ($owner !== null) {
                $ownerClass = get_class($owner);

                $q->orWhere(function (Builder $inner) use ($ownerClass, $owner) {
                    $inner->where('owner_type', $ownerClass)
                        ->where('owner_id', $owner->getKey());
                });

                if ($owner instanceof SupportsFieldInheritance) {
                    $ancestorIds = $owner->getAncestorOwnerIds();
                    $ancestorIds = array_filter($ancestorIds, fn ($id) => $id !== $owner->getKey());

                    if (! empty($ancestorIds)) {
                        $q->orWhere(function (Builder $inner) use ($ownerClass, $ancestorIds) {
                            $inner->where('owner_type', $ownerClass)
                                ->whereIn('owner_id', array_values($ancestorIds));
                        });
                    }
                }
            }
        });
    }

    /**
     * Get file field options from data column.
     *
     * @return array{multiple: bool, max_files: int}
     */
    public function getFileOptions(): array
    {
        return [
            'multiple'  => (bool) ($this->data['multiple'] ?? false),
            'max_files' => (int) ($this->data['max_files'] ?? 1),
        ];
    }

    public function allowsMultipleFiles(): bool
    {
        return (bool) ($this->data['multiple'] ?? false);
    }

    public function maxFiles(): int
    {
        return max(1, (int) ($this->data['max_files'] ?? 1));
    }

    public function hasDependency(): bool
    {
        return $this->depends_on_field_id !== null && $this->depends_on_condition !== null;
    }

    public function getDependencyConfig(): array
    {
        return [
            'field_id' => $this->depends_on_field_id,
            'operator' => $this->depends_on_condition['operator'] ?? null,
            'value'    => $this->depends_on_condition['value'] ?? null,
        ];
    }

    public function isDependencyMet(mixed $parentValue): bool
    {
        if (! $this->hasDependency()) {
            return true;
        }

        $operator    = $this->depends_on_condition['operator'] ?? null;
        $targetValue = $this->depends_on_condition['value'] ?? null;

        return match ($operator) {
            'equals'           => (string) $parentValue === (string) $targetValue,
            'not_equals'       => (string) $parentValue !== (string) $targetValue,
            'contains'         => str_contains((string) $parentValue, (string) $targetValue),
            'not_contains'     => ! str_contains((string) $parentValue, (string) $targetValue),
            'greater_than'     => (float) $parentValue > (float) $targetValue,
            'less_than'        => (float) $parentValue < (float) $targetValue,
            'greater_or_equal' => (float) $parentValue >= (float) $targetValue,
            'less_or_equal'    => (float) $parentValue <= (float) $targetValue,
            'after'            => strtotime((string) $parentValue) > strtotime((string) $targetValue),
            'before'           => strtotime((string) $parentValue) < strtotime((string) $targetValue),
            'is_empty'         => $parentValue === null || $parentValue === '',
            'is_not_empty'     => $parentValue !== null && $parentValue !== '',
            'checked'          => (bool) $parentValue === true || $parentValue === '1' || $parentValue === 1,
            'unchecked'        => (bool) $parentValue === false || $parentValue === '0' || $parentValue === 0 || $parentValue === '',
            default            => true,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'text'     => __('dynafields::dynafields.field_type_text'),
            'textarea' => __('dynafields::dynafields.field_type_textarea'),
            'date'     => __('dynafields::dynafields.field_type_date'),
            'select'   => __('dynafields::dynafields.field_type_select'),
            'boolean'  => __('dynafields::dynafields.field_type_boolean'),
            'number'   => __('dynafields::dynafields.field_type_number'),
            'checkbox' => __('dynafields::dynafields.field_type_checkbox'),
            'file'     => __('dynafields::dynafields.field_type_file'),
            default    => $this->type,
        };
    }
}
