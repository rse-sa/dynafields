<?php

use Illuminate\Support\Str;
use RSE\DynaFields\Models\CustomField;
use RSE\DynaFields\Models\CustomFieldValue;
use RSE\DynaFields\Tests\Post;

it('syncs custom field values on a subject', function () {
    $post  = Post::create(['id' => Str::ulid()]);
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Color'],
        'type' => 'text',
    ]);

    $post->syncCustomFields([$field->id => 'red']);

    expect(CustomFieldValue::count())->toBe(1)
        ->and($post->customFieldValues->first()->value)->toBe('red');
});

it('updates an existing value on re-sync', function () {
    $post  = Post::create(['id' => Str::ulid()]);
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Color'],
        'type' => 'text',
    ]);

    $post->syncCustomFields([$field->id => 'red']);
    $post->syncCustomFields([$field->id => 'blue']);

    expect(CustomFieldValue::count())->toBe(1)
        ->and($post->fresh()->customFieldValues->first()->value)->toBe('blue');
});

it('gets a specific custom field value', function () {
    $post  = Post::create(['id' => Str::ulid()]);
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Size'],
        'type' => 'text',
    ]);

    $post->syncCustomFields([$field->id => 'XL']);
    $post->load('customFieldValues');

    expect($post->getCustomFieldValue($field->id))->toBe('XL');
});

it('returns null for a field with no stored value', function () {
    $post  = Post::create(['id' => Str::ulid()]);
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Weight'],
        'type' => 'text',
    ]);

    $post->load('customFieldValues');

    expect($post->getCustomFieldValue($field->id))->toBeNull();
});

it('sets a single custom field value', function () {
    $post  = Post::create(['id' => Str::ulid()]);
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Brand'],
        'type' => 'text',
    ]);

    $post->setCustomFieldValue($field->id, 'Nike');

    expect($post->customFieldValues()->first()->value)->toBe('Nike');
});

it('deletes values when a custom field is soft-deleted', function () {
    $post  = Post::create(['id' => Str::ulid()]);
    $field = CustomField::create([
        'id'   => Str::ulid(),
        'name' => ['en' => 'Tag'],
        'type' => 'text',
    ]);

    $post->syncCustomFields([$field->id => 'sale']);

    $field->delete(); // soft delete

    // Value still exists (cascade only on hard delete via FK)
    expect(CustomFieldValue::count())->toBe(1);
});
