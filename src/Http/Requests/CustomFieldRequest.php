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
            'name'                       => ['array', 'min:1'],
            'name.*'                     => ['required', 'string'],
            'description'                => ['array'],
            'description.*'              => ['nullable', 'string'],
            'type'                       => ['required', "in:{$types}"],
            'scope'                      => ['nullable', 'string', 'max:100'],
            'owner_type'                 => ['nullable', 'string'],
            'owner_id'                   => ['nullable', 'string'],
            'order'                      => ['nullable', 'integer'],
            'default_value'              => ['nullable', 'string'],
            'options'                    => ['required_if:type,select', 'exclude_unless:type,select', 'array'],
            'options.*'                  => ['nullable', 'string'],
            'max_chars'                  => ['nullable', 'integer'],
            'active'                     => ['boolean'],
            'searchable'                 => ['boolean'],
            'is_unique'                  => ['boolean'],
            'is_mandatory'               => ['boolean'],
            'is_printable'               => ['boolean'],
            'is_fixed'                   => ['boolean'],
            'data'                       => ['nullable', 'array'],
            'data.multiple'              => ['nullable', 'boolean'],
            'data.max_files'             => ['nullable', 'integer', 'min:1', 'max:100'],
            'depends_on_field_id'        => ['nullable', 'string'],
            'depends_on_condition'       => ['nullable', 'required_with:depends_on_field_id', 'array'],
            'depends_on_condition.operator' => ['nullable', 'required_with:depends_on_field_id', 'string',
                'in:equals,not_equals,contains,not_contains,greater_than,less_than,greater_or_equal,less_or_equal,after,before,is_empty,is_not_empty,checked,unchecked'],
            'depends_on_condition.value' => ['nullable', 'string'],
            'metadata'                   => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_value' => $this->input('default_value_' . $this->input('type', 'text')),
            'active'        => $this->boolean('active', true),
            'searchable'    => $this->boolean('searchable', false),
            'is_mandatory'  => $this->boolean('is_mandatory', false),
            'is_unique'     => $this->boolean('is_unique', false),
            'is_printable'  => $this->boolean('is_printable', true),
            'is_fixed'      => $this->boolean('is_fixed', false),
        ]);
    }
}
