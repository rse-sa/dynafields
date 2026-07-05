---
name: dynafields
description: >
  The dynafields package (rse-sa/dynafields) adds flexible, admin-managed custom fields to any
  Eloquent model in Laravel. Always activate this skill when the user mentions: custom fields,
  dynamic fields, HasCustomFields, DefinesCustomFields, CustomField model, CustomFieldValue,
  the Livewire dynafields form component, field scoping (global/type/instance/section scope),
  SupportsFieldInheritance, field dependencies (depends_on_field_id, isDependencyMet),
  file upload/retrieve handlers (uploadFileUsing/retrieveFileUsing), the `extra` column on
  field values, or the `scope`/`active`/`searchable` columns. Also trigger when the user is
  saving custom field values to a polymorphic subject, syncing fields on a model, building an
  admin UI for custom field CRUD, or asking how to add user-defined attributes to any model.
---

# DynaFields — Laravel Custom Fields Package

## Install

```bash
composer require rse/dynafields
php artisan dynafields:install   # publishes config + runs migrations
```

Publish selectively: `--tag=dynafields-config|dynafields-migrations|dynafields-views|dynafields-lang`

**Requirements:** PHP ^8.2, Laravel ^11|^12, Livewire ^4, spatie/laravel-translatable ^6

---

## Traits & Contracts

| Class | Add to | Purpose |
|---|---|---|
| `HasCustomFields` | Subject model (e.g. `Invoice`) | Stores/retrieves field values |
| `DefinesCustomFields` | Owner model (e.g. `Category`) | Owns field definitions for subjects |
| `SupportsFieldInheritance` (interface) | Hierarchical owner | Cascades ancestor fields |

### HasCustomFields — key methods

```php
$model->customFieldValues()              // MorphMany → CustomFieldValue
$model->resolveCustomFields()            // Collection of applicable CustomField
$model->syncCustomFields(['field_id' => 'value', ...])
$model->getCustomFieldValue($fieldId)    // string|null
$model->setCustomFieldValue($fieldId, $value)
$model->customFieldOwner()              // override → return owner instance
$model->customFieldScope()              // override → return section scope string (see below)
```

### DefinesCustomFields — key methods

```php
$owner->customFields()                          // HasMany of owned fields
$owner->allCustomFieldsForSubject($subjectType) // resolved set for a type
```

### SupportsFieldInheritance

```php
public function getAncestorOwnerIds(): array  // [root_id, ..., self_id]
```

---

## Field Scoping

### 3-tier owner scope (polymorphic)

| Scope | `owner_type` | `owner_id` | Applies to |
|---|---|---|---|
| Global | null | null | every model |
| Type | `App\Models\Invoice` | null | all instances of that type |
| Instance | `App\Models\Category` | `<id>` | subjects owned by that instance |

Scopes stack: a subject sees global + its type fields + owner-instance fields (+ ancestor-inherited if owner implements `SupportsFieldInheritance`).

**Query scopes on `CustomField`:**
```php
CustomField::global()
CustomField::forSubjectType(Invoice::class)
CustomField::forSubject(Invoice::class, $owner)  // all applicable, with inheritance
```

### Section scope (string)

The `scope` column is a nullable string that partitions fields into named sections:

| `scope` value | Meaning |
|---|---|
| `null` | Applies to every section (truly global) |
| `'contracts'` | Only returned when context is "contracts" |
| `'hr'` / `'sales'` / … | Other named sections |

**Query scope:**
```php
CustomField::forScope('contracts')  // null rows + 'contracts' rows
```

**Hook in subject model** — override `customFieldScope()` so `resolveCustomFields()` auto-filters:
```php
class Document extends Model {
    use HasCustomFields;

    public function customFieldScope(): ?string
    {
        return $this->type;  // e.g. 'contracts', 'taameed', 'documents'
    }
}
```

---

## Field Types

| Type | Notes |
|---|---|
| `text` | Single-line string |
| `textarea` | Multi-line string |
| `date` | ISO date string |
| `select` | One of a predefined key list; options stored in `data['options']` |
| `boolean` | `true`/`false` stored as string `'1'`/`'0'` |
| `number` | Numeric; `data` may hold `min`, `max`, `step` |
| `checkbox` | Alias for boolean; rendered as a checkbox |
| `file` | Upload; `value` = stored path/identifier; `extra` JSON = path/size/mime/original_name. Multiple-file inputs rendered as Alpine.js dynamic slots when `data['multiple'] = true`. |

---

## CustomField model

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | primary |
| `owner_type` / `owner_id` | string\|null | polymorphic scope tier |
| `scope` | string\|null | section scope; null = all sections |
| `name` | json | translatable |
| `description` | json\|null | translatable |
| `type` | string | see Field Types above |
| `data` | json\|null | type-specific config (select options, file multi/max, number range) |
| `metadata` | json\|null | developer-defined extras |
| `order` | uint\|null | display order; global scope adds `ORDER BY order, created_at` |
| `default_value` | string\|null | |
| `active` | bool | default true; **global scope** excludes inactive fields automatically |
| `searchable` | bool | default false |
| `is_mandatory` | bool | alias: `required` cast in app subclass |
| `is_unique` | bool | |
| `is_printable` | bool | include in exports |
| `is_fixed` | bool | locked after creation |
| `depends_on_field_id` | ulid\|null | FK → `custom_fields.id` (nullOnDelete) |
| `depends_on_condition` | json\|null | `{operator, value}` |
| `created_by` / `updated_by` | string\|null | user ID strings, no FK constraint |

Soft-deleted. Relationships: `owner()` (morphTo), `values()` (hasMany CustomFieldValue),
`dependsOnField()` (belongsTo self), `dependentFields()` (hasMany self).

**Create programmatically:**
```php
// Text / textarea / date field
CustomField::create([
    'name'       => ['en' => 'Notes', 'ar' => 'ملاحظات'],
    'type'       => 'textarea',
    'scope'      => 'contracts',     // section scope; null = applies everywhere
    'owner_type' => Category::class, // instance scope (optional)
    'owner_id'   => $category->id,
    'active'     => true,
    'searchable' => false,
]);

// Select field — options go in 'data'
CustomField::create([
    'name' => ['en' => 'Status', 'ar' => 'الحالة'],
    'type' => 'select',
    'data' => ['options' => [
        ['key' => 'draft',  'label' => ['en' => 'Draft',  'ar' => 'مسودة']],
        ['key' => 'active', 'label' => ['en' => 'Active', 'ar' => 'نشط']],
    ]],
]);

// File field — multiple uploads allowed
CustomField::create([
    'name' => ['en' => 'Attachments', 'ar' => 'المرفقات'],
    'type' => 'file',
    'data' => ['multiple' => true, 'max_files' => 3],
]);

// Dependent field — only shown when parent equals 'active'
CustomField::create([
    'name'                 => ['en' => 'Expiry Date', 'ar' => 'تاريخ الانتهاء'],
    'type'                 => 'date',
    'depends_on_field_id'  => $parentField->id,
    'depends_on_condition' => ['operator' => 'equals', 'value' => 'active'],
]);
```

---

## Field Dependencies

A field may be conditionally shown/required based on the value of another field.

```php
$field->hasDependency(): bool
$field->getDependencyConfig(): array   // ['field_id', 'operator', 'value']
$field->isDependencyMet(mixed $parentValue): bool
```

**Supported operators:**

| Operator | Meaning |
|---|---|
| `equals` / `not_equals` | String equality |
| `contains` / `not_contains` | Substring check |
| `greater_than` / `less_than` | Numeric comparison |
| `greater_or_equal` / `less_or_equal` | Numeric comparison |
| `after` / `before` | Date comparison |
| `is_empty` / `is_not_empty` | Presence check |
| `checked` / `unchecked` | Boolean field state |

---

## CustomFieldValue model

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | primary |
| `subject_type` | string | morph type |
| `subject_id` | string | morph id |
| `custom_field_id` | ulid | FK → custom_fields |
| `value` | text\|null | plain string value (all types) |
| `extra` | json\|null | file metadata: `path`, `original_name`, `size`, `mime` |

Unique constraint: `(subject_type, subject_id, custom_field_id)`.

**File-specific helpers:**
```php
$cfv->getFilePath()                   // extra['path']
$cfv->getFileName()                   // extra['original_name']
$cfv->getFileSize()                   // extra['size'] as int
$cfv->getFileUrl()                    // calls registered retrieveFileUsing handler (throws if unregistered)
$cfv->retrieveFileValue(?$subject)    // safe wrapper → always returns FileFieldValue DTO (never throws)
```

---

## FileFieldValue DTO

`RSE\DynaFields\DTOs\FileFieldValue` is the structured return type for `retrieveFileUsing` handlers.

```php
new FileFieldValue(
    name:         'invoice.pdf',          // display name (required)
    size:         204800,                 // bytes (null = unknown)
    extension:    'pdf',                  // null = unknown
    downloadLink: 'https://...',          // null = no link shown
    date:         Carbon::now(),          // null = not shown
    model:        $attachmentModel,       // optional — your underlying model
);

// Convenience factory from CustomFieldValue::$extra:
FileFieldValue::fromExtra($cfv->extra, $downloadUrl);

// Helpers:
$fv->formattedSize()   // e.g. "1.2 MB", "" if size is null
```

The Livewire form passes `FileFieldValue` to the file preview view. The built-in default card shows icon + name (linked if `downloadLink` set) + size + date + download button.

---

## File Upload Handlers

File handling is app-specific. Register closures in a service provider:

```php
use RSE\DynaFields\DTOs\FileFieldValue;
use RSE\DynaFields\Models\CustomFieldValue;

// In AppServiceProvider::boot():
CustomFieldValue::uploadFileUsing(function (CustomField $field, mixed $rawValue, Model $subject): string {
    // $rawValue is the UploadedFile instance
    // Return stored path / identifier (saved to value + extra['path'])
    return $rawValue->store('custom-fields', 'local');
});

// Preferred: return FileFieldValue for rich built-in preview
CustomFieldValue::retrieveFileUsing(function (CustomField $field, string $storedValue, Model $subject): FileFieldValue {
    return new FileFieldValue(
        name:         basename($storedValue),
        size:         Storage::size($storedValue),
        extension:    pathinfo($storedValue, PATHINFO_EXTENSION),
        downloadLink: Storage::url($storedValue),
        date:         Carbon::now(),
    );
});

// Backward compat: returning a plain string URL still works
CustomFieldValue::retrieveFileUsing(function ($field, $storedValue, $subject): string {
    return Storage::url($storedValue);   // wrapped in FileFieldValue::fromExtra() automatically
});
```

`retrieveFileValue()` on `CustomFieldValue` is the safe wrapper used by the form — it never throws. `getFileUrl()` / `retrieveFile()` call the handler directly and throw `RuntimeException` when unregistered.

---

## Livewire Form Component

```blade
@livewire('dynafields::form', [
    'subject' => $model,          {{-- the record being edited/created --}}
    'action'  => 'create',        {{-- 'create' | 'edit' --}}
    'owner'   => $ownerModel,     {{-- optional: pre-select owner --}}
])
```

**Dynamic owner change** (e.g. category select changes):
```js
$dispatch('dynafields:owner-change', {
    ownerType: 'App\\Models\\Category',
    ownerKey: categoryId
})
```
The component re-resolves fields and resets values automatically.

Field state flags on each resolved field: `is_global`, `is_type_scoped`, `is_inherited`, `is_readonly`.
Fixed fields (`is_fixed`) render as disabled in edit mode.

---

## Admin Routes (CRUD)

Enabled by default at `/dynafields` (middleware: `web`, `auth`):

```
GET    /dynafields/create
POST   /dynafields
GET    /dynafields/{customField}/edit
PUT    /dynafields/{customField}
DELETE /dynafields/{customField}
```

Disable if the host app provides its own routes: `'routes' => ['enabled' => false]`.

---

## Config (`config/dynafields.php`)

```php
'field_types' => ['text', 'textarea', 'date', 'select', 'boolean', 'number', 'checkbox', 'file'],
'translatable' => true,
'models' => [
    'custom_field'       => \RSE\DynaFields\Models\CustomField::class,
    'custom_field_value' => \RSE\DynaFields\Models\CustomFieldValue::class,
],
'routes' => [
    'enabled'    => true,
    'prefix'     => 'dynafields',
    'name'       => 'dynafields.',
    'middleware' => ['web', 'auth'],
],
'livewire' => [
    'enabled'            => true,
    'owner_change_event' => 'dynafields:owner-change',
],
// null = built-in card (icon + name + size + date + download button)
// Set to a view path to use a custom preview view instead
'file_preview_view' => null,
```

**Custom `file_preview_view`** receives:
```php
$field        // CustomField model
$fileValue    // FileFieldValue DTO
$storedValue  // raw stored string (backward compat)
$isDisabled   // bool
```

**Override models** to extend with app-specific logic:
```php
'models' => [
    'custom_field'       => \App\Models\CustomField::class,
    'custom_field_value' => \App\Models\CustomFieldValue::class,
],
```

---

## Typical Setup Pattern

```php
// Subject model
use RSE\DynaFields\Traits\HasCustomFields;
class Invoice extends Model {
    use HasCustomFields;

    // optional: link to owner for instance-scoped fields
    public function customFieldOwner(): ?Model { return $this->client; }

    // optional: section scope (null = all sections apply)
    public function customFieldScope(): ?string { return 'invoices'; }
}

// Owner model
use RSE\DynaFields\Traits\DefinesCustomFields;
class Client extends Model {
    use DefinesCustomFields;
}

// Hierarchical owner
use RSE\DynaFields\Contracts\SupportsFieldInheritance;
class Department extends Model implements SupportsFieldInheritance {
    use DefinesCustomFields;
    public function getAncestorOwnerIds(): array { /* root → self */ }
}

// Controller: save submitted values
$invoice->syncCustomFields($request->validated('fields', []));

// Blade template
@livewire('dynafields::form', ['subject' => $invoice, 'action' => 'edit'])
```
