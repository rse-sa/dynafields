<?php

namespace RSE\DynaFields\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected function casts(): array
    {
        return [
            'name'         => 'json',
            'description'  => 'json',
            'data'         => 'json',
            'metadata'     => 'json',
            'order'        => 'integer',
            'is_mandatory' => 'boolean',
            'is_unique'    => 'boolean',
            'is_printable' => 'boolean',
            'is_fixed'     => 'boolean',
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

    /** Global fields (no owner) */
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

    public function typeLabel(): ?string
    {
        $types = array_flip(config('dynafields.field_types', []));

        return match ($this->type) {
            'text'     => __('dynafields::dynafields.field_type_text'),
            'textarea' => __('dynafields::dynafields.field_type_textarea'),
            'date'     => __('dynafields::dynafields.field_type_date'),
            'select'   => __('dynafields::dynafields.field_type_select'),
            'boolean'  => __('dynafields::dynafields.field_type_boolean'),
            default    => $this->type,
        };
    }
}
