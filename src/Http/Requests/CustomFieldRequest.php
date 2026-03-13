<?php

namespace RSE\DynaFields\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use RSE\DynaFields\Enums\FieldType;

class CustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $types = implode(',', config('dynafields.field_types', FieldType::values()));

        return [
            'name'          => ['array', 'min:1'],
            'name.*'        => ['required', 'string'],
            'description'   => ['array'],
            'description.*' => ['nullable', 'string'],
            'type'          => ['required', "in:{$types}"],
            'owner_type'    => ['nullable', 'string'],
            'owner_id'      => ['nullable', 'string'],
            'order'         => ['nullable', 'integer'],
            'default_value' => ['nullable', 'string'],
            'options'       => ['required_if:type,select', 'exclude_unless:type,select', 'array'],
            'options.*'     => ['nullable', 'string'],
            'max_chars'     => ['nullable', 'integer'],
            'is_unique'     => ['boolean'],
            'is_mandatory'  => ['boolean'],
            'is_printable'  => ['boolean'],
            'is_fixed'      => ['boolean'],
            'metadata'      => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_value' => $this->input('default_value_' . $this->input('type', 'text')),
            'is_mandatory'  => $this->boolean('is_mandatory', false),
            'is_unique'     => $this->boolean('is_unique', false),
            'is_printable'  => $this->boolean('is_printable', false),
            'is_fixed'      => $this->boolean('is_fixed', false),
        ]);
    }
}
