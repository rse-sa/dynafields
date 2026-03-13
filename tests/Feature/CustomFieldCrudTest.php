<?php

use Illuminate\Support\Str;
use RSE\DynaFields\Models\CustomField;

it('creates a custom field with translatable name', function () {
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Serial Number', 'ar' => 'الرقم التسلسلي'],
        'type' => 'text',
    ]);

    expect($field->getTranslation('name', 'en'))->toBe('Serial Number')
        ->and($field->getTranslation('name', 'ar'))->toBe('الرقم التسلسلي');
});

it('stores select options in the data column', function () {
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Status'],
        'type' => 'select',
        'data' => ['options' => ['Active', 'Inactive', 'Pending']],
    ]);

    expect($field->data['options'])->toBe(['Active', 'Inactive', 'Pending']);
});

it('stores developer metadata in the metadata column', function () {
    $field = CustomField::create([
        'id'       => Str::ulid(),
        'name'     => ['en' => 'Priority'],
        'type'     => 'select',
        'data'     => ['options' => ['High', 'Medium', 'Low']],
        'metadata' => ['icon' => 'flag', 'color' => 'red'],
    ]);

    expect($field->metadata['icon'])->toBe('flag')
        ->and($field->metadata['color'])->toBe('red');
});

it('soft-deletes a custom field', function () {
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Temp Field'],
        'type' => 'text',
    ]);

    $field->delete();

    expect(CustomField::count())->toBe(0)
        ->and(CustomField::withTrashed()->count())->toBe(1);
});

it('restores a soft-deleted custom field', function () {
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Recoverable'],
        'type' => 'boolean',
    ]);

    $field->delete();
    $field->restore();

    expect(CustomField::count())->toBe(1);
});

it('returns the correct type label', function () {
    app()->setLocale('en');

    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Due Date'],
        'type' => 'date',
    ]);

    expect($field->typeLabel())->toContain('Date');
});

it('scopes global fields correctly', function () {
    CustomField::create(['id' => Str::ulid(), 'name' => ['en' => 'Global'], 'type' => 'text']);
    CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Scoped'],
        'type'       => 'text',
        'owner_type' => 'App\Models\Something',
        'owner_id'   => Str::ulid(),
    ]);

    expect(CustomField::global()->count())->toBe(1);
});
