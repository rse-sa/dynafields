<?php

namespace RSE\DynaFields\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RSE\DynaFields\Concerns\HasUlidPrimaryKey;

class CustomFieldValue extends Model
{
    use HasFactory;
    use HasUlidPrimaryKey;

    protected $table = 'custom_field_values';

    protected $guarded = [];

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    public function field(): BelongsTo
    {
        $fieldModel = config('dynafields.models.custom_field', CustomField::class);

        return $this->belongsTo($fieldModel, 'custom_field_id');
    }
}
