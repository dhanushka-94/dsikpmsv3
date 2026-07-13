<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public function log(
        string $action,
        string $description,
        ?string $module = null,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
    ): ActivityLog {
        $actor ??= Auth::user();

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $this->sanitize($properties) ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
        ]);
    }

    public function forModel(
        string $action,
        Model $subject,
        string $description,
        string $module,
        array $properties = [],
        ?User $actor = null,
    ): ActivityLog {
        return $this->log($action, $description, $module, $subject, $properties, $actor);
    }

    public function snapshot(Model $model, array $except = ['password', 'remember_token']): array
    {
        return collect($model->attributesToArray())
            ->except($except)
            ->map(fn ($value) => $this->normalizeValue($value))
            ->all();
    }

    public function diff(array $before, array $after, array $except = ['password', 'remember_token', 'updated_at']): array
    {
        $keys = collect(array_unique(array_merge(array_keys($before), array_keys($after))))
            ->reject(fn ($key) => in_array($key, $except, true));

        $changes = [];

        foreach ($keys as $key) {
            $old = $this->normalizeValue($before[$key] ?? null);
            $new = $this->normalizeValue($after[$key] ?? null);

            if ($old !== $new) {
                $changes[$key] = ['old' => $old, 'new' => $new];
            }
        }

        return ['changes' => $changes];
    }

    /**
     * @param  iterable<\Illuminate\Database\Eloquent\Model>  $users
     * @return array<int, array<string, mixed>>
     */
    public function pivotAssignments(iterable $users, array $pivotKeys = ['permission', 'is_enabled']): array
    {
        $map = [];

        foreach ($users as $user) {
            $entry = ['user_id' => $user->id, 'name' => method_exists($user, 'displayName') ? $user->displayName() : $user->name];

            foreach ($pivotKeys as $key) {
                $entry[$key] = $this->normalizeValue($user->pivot->{$key} ?? null);
            }

            $map[(int) $user->id] = $entry;
        }

        ksort($map);

        return $map;
    }

    private function sanitize(array $properties): array
    {
        $hidden = ['password', 'password_confirmation', 'current_password', 'remember_token', 'token'];

        return collect($properties)
            ->map(function ($value, $key) use ($hidden) {
                if (in_array($key, $hidden, true)) {
                    return '[hidden]';
                }

                if (is_array($value)) {
                    return $this->sanitize($value);
                }

                return $this->normalizeValue($value);
            })
            ->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d h:i:s A');
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => $this->normalizeValue($item))
                ->all();
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }
}
