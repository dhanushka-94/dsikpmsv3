<?php

namespace App\Enums;

enum UserTitle: string
{
    case Mr = 'Mr';
    case Mrs = 'Mrs';
    case Miss = 'Miss';
    case Ms = 'Ms';
    case Dr = 'Dr';
    case Eng = 'Eng';
    case Prof = 'Prof';

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $title) => [$title->value => $title->value])
            ->all();
    }
}
