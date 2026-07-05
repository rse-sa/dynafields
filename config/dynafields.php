<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Field Types
    |--------------------------------------------------------------------------
    | Available field types. Add or remove types as needed.
    */
    'field_types' => ['text', 'textarea', 'date', 'select', 'boolean', 'number', 'checkbox', 'file'],

    /*
    |--------------------------------------------------------------------------
    | Translatable Field Names
    |--------------------------------------------------------------------------
    | When enabled, field name and description are stored as JSON and support
    | multiple locales via spatie/laravel-translatable.
    */
    'translatable' => true,

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Override these to use your own extended models.
    */
    'models' => [
        'custom_field'       => \RSE\DynaFields\Models\CustomField::class,
        'custom_field_value' => \RSE\DynaFields\Models\CustomFieldValue::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'dynafields',
        'name'       => 'dynafields.',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    | The event name that the Livewire form component listens to when the
    | owner changes (e.g. user picks a different group for the asset).
    */
    'livewire' => [
        'enabled'            => true,
        'owner_change_event' => 'dynafields:owner-change',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Preview View
    |--------------------------------------------------------------------------
    | View used to display an existing file when editing a record. When null,
    | the built-in card (icon + name + size + date + download button) is used.
    |
    | Custom views receive:
    |   $field        — CustomField model
    |   $fileValue    — FileFieldValue DTO (name, size, extension, downloadLink, date, model)
    |   $storedValue  — raw stored string (backward compat)
    |   $isDisabled   — bool
    |
    | The DTO is populated by your retrieveFileUsing() handler. Returning a
    | FileFieldValue from the handler gives you full control over all fields.
    | Returning a plain URL string builds a minimal DTO from stored metadata.
    |
    | Example: 'components.dynafields-file-preview'
    */
    'file_preview_view' => null,

];
