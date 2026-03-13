<?php

namespace RSE\DynaFields\Enums;

enum FieldType: string
{
    case Text     = 'text';
    case Textarea = 'textarea';
    case Date     = 'date';
    case Select   = 'select';
    case Boolean  = 'boolean';

    public function label(): string
    {
        return match ($this) {
            self::Text     => __('dynafields::dynafields.field_type_text'),
            self::Textarea => __('dynafields::dynafields.field_type_textarea'),
            self::Date     => __('dynafields::dynafields.field_type_date'),
            self::Select   => __('dynafields::dynafields.field_type_select'),
            self::Boolean  => __('dynafields::dynafields.field_type_boolean'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
