<?php

namespace App\Enums;

enum FieldType: string
{
    case Text = 'text';
    case SecretText = 'secret_text';
    case Textarea = 'textarea';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Checkbox = 'checkbox';
    case DateSlots = 'date_slots';
    case ImageUpload = 'image_upload';
    case FileUpload = 'file_upload';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::SecretText => 'Secret Text',
            self::Textarea => 'Text Area',
            self::Select => 'Single Select',
            self::MultiSelect => 'Multi Select',
            self::Checkbox => 'Checkbox',
            self::DateSlots => 'Date/Time Slots',
            self::ImageUpload => 'Image Upload',
            self::FileUpload => 'File Upload',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text => 'Aa',
            self::SecretText => '&#128274;',
            self::Textarea => '&#182;',
            self::Select => '&#9660;',
            self::MultiSelect => '&#9745;',
            self::Checkbox => '&#9744;',
            self::DateSlots => '&#128197;',
            self::ImageUpload => '&#128247;',
            self::FileUpload => '&#128206;',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect, self::DateSlots]);
    }
}
