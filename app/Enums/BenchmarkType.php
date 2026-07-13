<?php

namespace App\Enums;

enum BenchmarkType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';

    public function label(): string
    {
        return match ($this) {
            self::Increase => 'Increase',
            self::Decrease => 'Decrease',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Increase => 'bg-emerald-50 text-emerald-700',
            self::Decrease => 'bg-amber-50 text-amber-700',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
