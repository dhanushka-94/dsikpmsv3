<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To Do',
            self::InProgress => 'In Progress',
            self::Review => 'Review',
            self::Done => 'Done',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Todo => 'bg-slate-100 text-slate-600',
            self::InProgress => 'bg-sky-50 text-sky-700',
            self::Review => 'bg-violet-50 text-violet-700',
            self::Done => 'bg-emerald-50 text-emerald-700',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }

    public static function boardColumns(): array
    {
        return self::cases();
    }
}
