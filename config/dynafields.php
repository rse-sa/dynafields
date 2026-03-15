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
    | View used to display an existing file value (download link, preview, etc.)
    | in the Livewire form component when editing a record that already has a
    | file uploaded. The view receives $field, $value (CustomFieldValue), and
    | $isDisabled variables.
    |
    | Set to null to show only the stored filename as plain text.
    |
    | Example: 'components.dynafields-file-preview'
    */
    'file_preview_view' => null,

];
