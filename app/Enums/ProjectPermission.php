<?php

namespace App\Enums;

enum ProjectPermission: string
{
    case Viewer = 'viewer';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Viewer => 'Viewer',
            self::Editor => 'Editor',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $permission) => [$permission->value => $permission->label()])
            ->all();
    }
}
