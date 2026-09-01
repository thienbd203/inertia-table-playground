<?php

namespace App;

enum ProductStatus: string
{
    case Active = 'active';
    case Draft = 'draft';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang bán',
            self::Draft => 'Bản nháp',
            self::Archived => 'Lưu trữ',
        };
    }
}
