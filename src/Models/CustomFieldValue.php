<?php

namespace RSE\DynaFields\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RSE\DynaFields\Concerns\HasUlidPrimaryKey;
use RuntimeException;

class CustomFieldValue extends Model
{
    use HasFactory;
    use HasUlidPrimaryKey;

    protected $table = 'custom_field_values';

    protected $guarded = [];

    protected static ?Closure $uploadHandler = null;

    protected static ?Closure $retrieveHandler = null;

    protected function casts(): array
    {
        return [
            'extra' => 'json',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    public function field(): BelongsTo
    {
        $fieldModel = config('dynafields.models.custom_field', CustomField::class);

        return $this->belongsTo($fieldModel, 'custom_field_id');
    }

    /**
     * Register a handler for uploading files for the 'file' field type.
     * The closure receives (CustomField $field, mixed $rawValue, Model $subject)
     * and must return the stored value (string path/identifier).
     */
    public static function uploadFileUsing(Closure $handler): void
    {
        static::$uploadHandler = $handler;
    }

    /**
     * Register a handler for retrieving files for the 'file' field type.
     * The closure receives (CustomField $field, string $storedValue, Model $subject)
     * and may return a URL, stream, or any representation.
     */
    public static function retrieveFileUsing(Closure $handler): void
    {
        static::$retrieveHandler = $handler;
    }

    /**
     * Upload a file using the registered handler.
     * Stores the result in `value` and populates `extra` with file metadata.
     */
    public function uploadFile(CustomField $field, mixed $rawValue): void
    {
        if (static::$uploadHandler === null) {
            throw new RuntimeException(
                'No file upload handler registered. Call CustomFieldValue::uploadFileUsing() in a service provider.'
            );
        }

        $subject = $this->subject;

        $storedValue = (static::$uploadHandler)($field, $rawValue, $subject);

        $this->value = $storedValue;
        $this->extra = [
            'original_name' => method_exists($rawValue, 'getClientOriginalName')
                ? $rawValue->getClientOriginalName()
                : basename((string) $storedValue),
            'size'          => method_exists($rawValue, 'getSize') ? $rawValue->getSize() : null,
            'mime'          => method_exists($rawValue, 'getMimeType') ? $rawValue->getMimeType() : null,
            'path'          => $storedValue,
        ];
    }

    /**
     * Retrieve the file using the registered handler.
     */
    public function retrieveFile(): mixed
    {
        if (static::$retrieveHandler === null) {
            throw new RuntimeException(
                'No file retrieve handler registered. Call CustomFieldValue::retrieveFileUsing() in a service provider.'
            );
        }

        return (static::$retrieveHandler)($this->field, $this->value, $this->subject);
    }

    public function getFilePath(): ?string
    {
        return $this->extra['path'] ?? null;
    }

    public function getFileName(): ?string
    {
        return $this->extra['original_name'] ?? null;
    }

    public function getFileSize(): ?int
    {
        return isset($this->extra['size']) ? (int) $this->extra['size'] : null;
    }

    public function getFileUrl(): mixed
    {
        return $this->retrieveFile();
    }
}
