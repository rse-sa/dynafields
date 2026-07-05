# DynaFields : Dynamic Custom Fields For Laravel

Add flexible, admin-managed custom fields to any model in minutes, with support for field scoping by section, owner-based inheritance, conditional field dependencies, file upload delegation, multi-locale names, and a ready-to-use Livewire form component.


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

## Field Types

| Type       | Input rendered                              |
|------------|---------------------------------------------|
| `text`     | `<input type="text">`                       |
| `textarea` | `<textarea>`                                |
| `date`     | `<input type="date">`                       |
| `number`   | `<input type="number">`                     |
| `select`   | `<select>` with options from `data.options` |
| `boolean`  | `<input type="checkbox">` (yes/no)          |
| `checkbox` | `<input type="checkbox">`                   |
| `file`     | `<input type="file">` with custom handler   |

---

## Field Scoping (3 tiers + section scope)

DynaFields uses a 3-tier owner model on the `custom_fields` table, combined with an optional `scope` string for section-based filtering:

| `owner_type`            | `owner_id` | `scope`      | Applies to                                    |
|-------------------------|------------|--------------|-----------------------------------------------|
| `null`                  | `null`     | `null`       | **Truly global** — every model, every section |
| `null`                  | `null`     | `'contracts'`| **Section-global** — all models in that section |
| `'App\\Models\\Invoice'`| `null`     | any          | **Type-scoped** — all Invoice records         |
| `'App\\Models\\Category'`| `'abc..'` | any          | **Instance-scoped** — records under that Category |

Fields with `scope = null` always appear regardless of the active section. Fields with a specific `scope` only appear when the subject reports the same scope via `customFieldScope()`.

---

## Section Scoping

Override `customFieldScope()` in your model to tell DynaFields which section the model belongs to:

```php
class Document extends Model
{
    use HasCustomFields;

    public function customFieldScope(): ?string
    {
        return $this->type; // e.g. 'contracts', 'hr', 'sales'
    }
}
```

When resolving fields, DynaFields automatically includes:
- Fields with `scope = null` (truly global)
- Fields whose `scope` matches what `customFieldScope()` returns

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

## Field Dependencies (Conditional Fields)

Fields can be conditionally shown based on another field's value. Configure via `depends_on_field_id` and `depends_on_condition`:

```php
$field->depends_on_field_id  // ULID of the parent field
$field->depends_on_condition // ['operator' => 'equals', 'value' => 'yes']
```

Check in PHP whether a dependency is met:

```php
$parentValue = $parentFieldValue->value;

if ($field->isDependencyMet($parentValue)) {
    // show this field
}
```

Available operators:

| Operator          | Description                        |
|-------------------|------------------------------------|
| `equals`          | Exact match                        |
| `not_equals`      | Not equal                          |
| `contains`        | String contains                    |
| `not_contains`    | String does not contain            |
| `greater_than`    | Numeric greater than               |
| `less_than`       | Numeric less than                  |
| `greater_or_equal`| Numeric >=                         |
| `less_or_equal`   | Numeric <=                         |
| `after`           | Date after                         |
| `before`          | Date before                        |
| `is_empty`        | Value is null or empty string      |
| `is_not_empty`    | Value is not null or empty         |
| `checked`         | Checkbox/boolean is true           |
| `unchecked`       | Checkbox/boolean is false          |

---

## File Upload Delegation

The `file` field type delegates upload and retrieval to app-defined handlers. Register them once in a service provider:

```php
use RSE\DynaFields\Models\CustomFieldValue;
use Illuminate\Support\Facades\Storage;

// In AppServiceProvider::boot()

CustomFieldValue::uploadFileUsing(
    function (CustomField $field, mixed $rawValue, Model $subject): string {
        return Storage::putFile('custom-fields/' . $subject->getKey(), $rawValue);
    }
);

CustomFieldValue::retrieveFileUsing(
    function (CustomField $field, string $storedValue, Model $subject): string {
        return Storage::url($storedValue);
    }
);
```

Both closures receive:
- `$field` — the `CustomField` model instance (access type-specific config via `$field->metadata`)
- `$rawValue` / `$storedValue` — the uploaded file object or stored identifier
- `$subject` — the model instance that owns the value

If a `file` field is used without registering handlers, a `RuntimeException` is thrown.

File metadata is stored automatically in the `extra` JSON column:

```php
$value->getFilePath();    // stored path/identifier
$value->getFileName();    // original filename
$value->getFileSize();    // file size in bytes
$value->getFileUrl();     // calls retrieveFileUsing handler
```

### Multiple File Uploads

Enable multiple files by setting `data.multiple` and optionally `data.max_files` when creating or updating a `file` field:

```php
$field->data = [
    'multiple'  => true,
    'max_files' => 5,
];
```

Helper methods:

```php
$field->allowsMultipleFiles();  // bool
$field->maxFiles();             // int, always >= 1
$field->getFileOptions();       // ['multiple' => bool, 'max_files' => int]
```

When `multiple=true`, the upload handler receives an array of `UploadedFile` objects. Store each file and return the appropriate stored value (e.g. a JSON string of paths). The `extra` column can hold an array of metadata objects.

---

### Customizing the File Preview (in forms)

When editing a record that already has a file uploaded, the form component shows the stored filename by default. To customize this (e.g. show a download link or thumbnail), register a view in the config:

```php
// config/dynafields.php
'file_preview_view' => 'components.my-file-preview',
```

Your preview view receives:

| Variable       | Type          | Description                                |
|----------------|---------------|--------------------------------------------|
| `$field`       | `CustomField` | The field model                            |
| `$storedValue` | `string`      | The stored value (path/identifier)         |
| `$isDisabled`  | `bool`        | Whether editing is disabled for this field |

Example:

```blade
{{-- resources/views/components/my-file-preview.blade.php --}}
<a href="{{ route('files.download', $storedValue) }}" class="text-blue-600 underline text-sm">
    Download current file
</a>
```

---

## Field Flags

| Column         | Default | Description                                      |
|----------------|---------|--------------------------------------------------|
| `active`       | `true`  | Inactive fields are hidden via global scope      |
| `searchable`   | `false` | Hint to include this field in search indexing    |
| `is_mandatory` | `false` | Field is required                                |
| `is_unique`    | `false` | Value must be unique per subject                 |
| `is_printable` | `true`  | Include in printable views                       |
| `is_fixed`     | `false` | Value cannot be changed after creation           |

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

// Check field dependency
$field->hasDependency();
$field->getDependencyConfig();   // ['field_id', 'operator', 'value']
$field->isDependencyMet($parentValue);

// Field type label (localized)
$field->typeLabel();
```

---

## Configuration

Publish the config: `php artisan vendor:publish --tag=dynafields-config`

```php
// config/dynafields.php
return [
    'field_types'  => ['text', 'textarea', 'date', 'number', 'select', 'boolean', 'checkbox', 'file'],
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

## Query Scopes

```php
// Only active fields (applied as global scope automatically)
CustomField::withoutGlobalScope('active')->get(); // include inactive

// Filter by section
CustomField::forScope('contracts')->get();

// Filter by owner type
CustomField::forSubjectType(Invoice::class)->get();

// All fields for a subject (global + type + owner-scoped + inherited)
CustomField::forSubject(Invoice::class, $owner)->get();

// Global fields only (no owner)
CustomField::global()->get();
```

---

## Tests

```bash
composer install
./vendor/bin/pest
```
