# DynaFields : Dynamic Custom Fields For Laravel

Add flexible, admin-managed custom fields to any model in minutes, with support for field inheritance, multi-locale names, and a ready-to-use Livewire form component.

---

## Installation

```bash
composer require rse/dynafields
php artisan dynafields:install
```

This publishes the config file and runs the migrations (`custom_fields` and `custom_field_values` tables).

---

## Quick Start

### 1. Add the trait to your model

```php
use RSE\DynaFields\Traits\HasCustomFields;

class Invoice extends Model
{
    use HasCustomFields;
}
```

### 2. Render custom fields in a form

```blade
@livewire('dynafields::form', ['subject' => $invoice, 'action' => 'create'])
```

### 3. Save field values in your controller

```php
$invoice->syncCustomFields($request->validated('fields', []));
```

---

## Field Scoping (3 tiers)

DynaFields uses a 3-tier scoping model on the `custom_fields` table:

| `owner_type`            | `owner_id` | Scope                                                         |
|-------------------------|------------|---------------------------------------------------------------|
| `null`                  | `null`     | **Global** — shown on all models                              |
| `'App\Models\Invoice'`  | `null`     | **Type-scoped** — shown on all Invoice records                |
| `'App\Models\Category'` | `'abc...'` | **Instance-scoped** — shown on records owned by that Category |

---

## Owner Model (Optional)

If your subjects are grouped by an owner (like Categories grouping Products), override `customFieldOwner()`:

```php
class Product extends Model
{
    use HasCustomFields;

    public function customFieldOwner(): ?Model
    {
        return $this->category;
    }
}
```

Add `DefinesCustomFields` to the owner model:

```php
use RSE\DynaFields\Traits\DefinesCustomFields;

class Category extends Model
{
    use DefinesCustomFields;
}
```

---

## Field Inheritance

If your owner model has a hierarchy (parent → child), implement `SupportsFieldInheritance`:

```php
use RSE\DynaFields\Contracts\SupportsFieldInheritance;
use RSE\DynaFields\Traits\DefinesCustomFields;

class Group extends Model implements SupportsFieldInheritance
{
    use DefinesCustomFields;

    public function getAncestorOwnerIds(): array
    {
        // Return all ancestor IDs from root → self (inclusive)
        return $this->getAncestorIds();
    }
}
```

Fields defined on parent Groups automatically cascade to their children.

---

## Livewire Form Component

The `dynafields::form` component renders field inputs in any form.

```blade
{{-- Create --}}
@livewire('dynafields::form', [
    'subject' => $product,
    'action'  => 'create',
    'owner'   => $category,   // optional: pre-select owner
])

{{-- Edit --}}
@livewire('dynafields::form', [
    'subject' => $product,
    'action'  => 'edit',
])
```

### Dynamic owner changes

When the owner selection changes (e.g. user picks a different category), dispatch:

```js
$dispatch('dynafields:owner-change', { ownerType: 'App\\Models\\Category', ownerKey: '...' })
```

---

## API

```php
// Sync field values (field_id => value map)
$model->syncCustomFields($request->validated('fields', []));

// Get a single field value
$model->getCustomFieldValue($fieldId);

// Set a single field value
$model->setCustomFieldValue($fieldId, 'value');

// Get all field values (MorphMany)
$model->customFieldValues()->with('field')->get();

// Resolve all applicable fields for this model
$model->resolveCustomFields();
```

---

## Configuration

Publish the config: `php artisan vendor:publish --tag=dynafields-config`

```php
// config/dynafields.php
return [
    'field_types'  => ['text', 'textarea', 'date', 'select', 'boolean'],
    'translatable' => true,
    'models' => [
        'custom_field'       => \RSE\DynaFields\Models\CustomField::class,
        'custom_field_value' => \RSE\DynaFields\Models\CustomFieldValue::class,
    ],
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'dynafields',
        'middleware' => ['web', 'auth'],
    ],
    'livewire' => [
        'enabled'            => true,
        'owner_change_event' => 'dynafields:owner-change',
    ],
];
```

### Custom models

Extend the package models to add app-specific relationships:

```php
// app/Models/CustomField.php
class CustomField extends \RSE\DynaFields\Models\CustomField
{
    public function notifications(): HasMany
    {
        return $this->hasMany(FieldNotification::class, 'field_id');
    }
}
```

Then register your extended model in config:

```php
'models' => [
    'custom_field' => App\Models\CustomField::class,
],
```

---

## Field Types

| Type       | Input rendered                              |
|------------|---------------------------------------------|
| `text`     | `<input type="text">`                       |
| `textarea` | `<textarea>`                                |
| `date`     | `<input type="date">`                       |
| `select`   | `<select>` with options from `data.options` |
| `boolean`  | `<input type="checkbox">`                   |

---

## Tests

```bash
composer install
./vendor/bin/pest
```
