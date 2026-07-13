<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Ongoing = 'ongoing';
    case OnHold = 'on_hold';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Ongoing',
            self::OnHold => 'On Hold',
            self::Completed => 'Completed',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Ongoing => 'bg-sky-50 text-sky-700',
            self::OnHold => 'bg-amber-50 text-amber-700',
            self::Completed => 'bg-emerald-50 text-emerald-700',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
