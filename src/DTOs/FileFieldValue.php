<?php

namespace RSE\DynaFields\DTOs;

use Carbon\Carbon;

final class FileFieldValue
{
    public function __construct(
        public readonly string  $name,
        public readonly ?int    $size         = null,
        public readonly ?string $extension    = null,
        public readonly ?string $downloadLink = null,
        public readonly ?Carbon $date         = null,
        public readonly mixed   $model        = null,
    ) {}

    public static function fromExtra(array $extra, ?string $downloadLink = null): self
    {
        $name = $extra['original_name'] ?? $extra['path'] ?? 'file';

        return new self(
            name:         $name,
            size:         isset($extra['size']) ? (int) $extra['size'] : null,
            extension:    ($ext = pathinfo($name, PATHINFO_EXTENSION)) !== '' ? $ext : null,
            downloadLink: $downloadLink,
        );
    }

    public function formattedSize(): string
    {
        if ($this->size === null) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $n     = (float) $this->size;
        $i     = 0;

        while ($n >= 1024 && $i < 3) {
            $n /= 1024;
            $i++;
        }

        return round($n, 1) . ' ' . $units[$i];
    }
}
