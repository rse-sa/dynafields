<?php

use Illuminate\Support\Str;
use RSE\DynaFields\Models\CustomField;
use RSE\DynaFields\Tests\Category;
use RSE\DynaFields\Tests\Document;
use RSE\DynaFields\Tests\Folder;
use RSE\DynaFields\Tests\Post;
use RSE\DynaFields\Tests\Product;

// ---------------------------------------------------------------------------
// Global fields
// ---------------------------------------------------------------------------

it('resolves global fields for any subject type', function () {
    $globalField = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Notes'],
        'type'       => 'textarea',
        'owner_type' => null,
        'owner_id'   => null,
    ]);

    $post    = Post::create(['id' => Str::ulid()]);
    $product = Product::create(['id' => Str::ulid()]);

    $postFields    = CustomField::forSubject(Post::class)->get();
    $productFields = CustomField::forSubject(Product::class)->get();

    expect($postFields->contains($globalField->id))->toBeTrue()
        ->and($productFields->contains($globalField->id))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Type-scoped fields
// ---------------------------------------------------------------------------

it('resolves type-scoped fields only for the target subject type', function () {
    $postField = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Slug'],
        'type'       => 'text',
        'owner_type' => Post::class,
        'owner_id'   => null,
    ]);

    $productField = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'SKU'],
        'type'       => 'text',
        'owner_type' => Product::class,
        'owner_id'   => null,
    ]);

    $postFields    = CustomField::forSubject(Post::class)->get();
    $productFields = CustomField::forSubject(Product::class)->get();

    expect($postFields->contains($postField->id))->toBeTrue()
        ->and($postFields->contains($productField->id))->toBeFalse()
        ->and($productFields->contains($productField->id))->toBeTrue()
        ->and($productFields->contains($postField->id))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Instance-scoped fields (owner)
// ---------------------------------------------------------------------------

it('resolves instance-scoped fields for a specific owner', function () {
    $catA = Category::create(['id' => Str::ulid()]);
    $catB = Category::create(['id' => Str::ulid()]);

    $fieldForA = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Warranty'],
        'type'       => 'date',
        'owner_type' => Category::class,
        'owner_id'   => $catA->id,
    ]);

    $fieldsWithCatA = CustomField::forSubject(Product::class, $catA)->get();
    $fieldsWithCatB = CustomField::forSubject(Product::class, $catB)->get();

    expect($fieldsWithCatA->contains($fieldForA->id))->toBeTrue()
        ->and($fieldsWithCatB->contains($fieldForA->id))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Inheritance
// ---------------------------------------------------------------------------

it('inherits fields from ancestor owner instances', function () {
    $parent = Folder::create(['id' => Str::ulid()]);
    $child  = Folder::create(['id' => Str::ulid()]);
    $child->ancestorIds = [$parent->id];

    $parentField = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Author'],
        'type'       => 'text',
        'owner_type' => Folder::class,
        'owner_id'   => $parent->id,
    ]);

    $childField = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Version'],
        'type'       => 'text',
        'owner_type' => Folder::class,
        'owner_id'   => $child->id,
    ]);

    $fieldsForChild = CustomField::forSubject(Document::class, $child)->get();

    expect($fieldsForChild->contains($parentField->id))->toBeTrue()
        ->and($fieldsForChild->contains($childField->id))->toBeTrue();
});

it('does not inherit fields when owner does not implement SupportsFieldInheritance', function () {
    $catParent = Category::create(['id' => Str::ulid()]);
    $catChild  = Category::create(['id' => Str::ulid()]);

    $parentField = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Origin'],
        'type'       => 'text',
        'owner_type' => Category::class,
        'owner_id'   => $catParent->id,
    ]);

    $fieldsForChild = CustomField::forSubject(Product::class, $catChild)->get();

    expect($fieldsForChild->contains($parentField->id))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Combined scoping
// ---------------------------------------------------------------------------

it('resolves global + type-scoped + instance-scoped fields together', function () {
    $cat = Category::create(['id' => Str::ulid()]);

    $global = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Global Field'],
        'type'       => 'text',
        'owner_type' => null,
        'owner_id'   => null,
    ]);

    $typeScoped = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Product Field'],
        'type'       => 'text',
        'owner_type' => Product::class,
        'owner_id'   => null,
    ]);

    $instanceScoped = CustomField::create([
        'id'         => Str::ulid(),
        'name'       => ['en' => 'Category Field'],
        'type'       => 'text',
        'owner_type' => Category::class,
        'owner_id'   => $cat->id,
    ]);

    $fields = CustomField::forSubject(Product::class, $cat)->get();

    expect($fields->contains($global->id))->toBeTrue()
        ->and($fields->contains($typeScoped->id))->toBeTrue()
        ->and($fields->contains($instanceScoped->id))->toBeTrue();
});
