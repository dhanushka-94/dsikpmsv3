<?php

namespace App\Services;

use InvalidArgumentException;

class FormulaEvaluator
{
    /**
     * Safely evaluate a formula preset.
     * Placeholders use {Value Name} syntax.
     * "%" is treated as "percent of": A % B => (A/100)*B.
     *
     * @param  array<string, float|int|string>  $values  keyed by value name
     */
    public static function evaluate(string $expression, array $values = []): float
    {
        $expression = self::normalize($expression);

        if ($expression === '') {
            throw new InvalidArgumentException('Formula is empty.');
        }

        $variables = self::variables($expression);

        foreach ($variables as $variable) {
            if (! array_key_exists($variable, $values)) {
                throw new InvalidArgumentException("Missing value for \"{$variable}\".");
            }

            if (! is_numeric($values[$variable])) {
                throw new InvalidArgumentException("\"{$variable}\" must be a number.");
            }
        }

        $resolved = self::substitute($expression, $values);

        return self::evaluateNumeric($resolved);
    }

    /**
     * @param  list<array{name: string}>|list<string>  $fields
     */
    public static function validateTemplate(string $expression, array $fields = []): void
    {
        $expression = self::normalize($expression);

        if ($expression === '') {
            throw new InvalidArgumentException('Formula is empty.');
        }

        if (! self::balanced($expression)) {
            throw new InvalidArgumentException('Formula parentheses are unbalanced.');
        }

        $fieldNames = self::normalizeFieldNames($fields);

        if ($fieldNames === []) {
            throw new InvalidArgumentException('Add at least one value name for the formula preset.');
        }

        $used = self::variables($expression);

        if ($used === []) {
            throw new InvalidArgumentException('Add at least one value name into the formula.');
        }

        foreach ($used as $name) {
            if ($name === '' || str_contains($name, '{') || str_contains($name, '}')) {
                throw new InvalidArgumentException('Formula contains invalid characters.');
            }

            if (! in_array($name, $fieldNames, true)) {
                throw new InvalidArgumentException("\"{$name}\" is not in your value names list.");
            }
        }

        // Outside placeholders, only operators / grouping / spaces are allowed.
        // Value names inside {…} may contain spaces, underscores, punctuation, etc.
        $withoutPlaceholders = preg_replace('/\{[^{}]+\}/', '', $expression) ?? $expression;
        if (preg_match('/\d/', $withoutPlaceholders)) {
            throw new InvalidArgumentException('Formula presets cannot include fixed numbers. Use value names only.');
        }

        if (! preg_match('/^[+\-*\/%().\s]*$/', $withoutPlaceholders)) {
            throw new InvalidArgumentException('Formula contains invalid characters.');
        }

        if (preg_match('/\{[^{}]*\{|\}[^{}]*\}/', $expression) || substr_count($expression, '{') !== substr_count($expression, '}')) {
            throw new InvalidArgumentException('Formula contains invalid characters.');
        }

        $sample = collect($used)->mapWithKeys(fn (string $name) => [$name => 1])->all();
        self::evaluate($expression, $sample);
    }

    /**
     * Normalize whitespace (incl. NBSP / unicode spaces) so pasted names validate cleanly.
     */
    public static function normalize(string $expression): string
    {
        $expression = preg_replace('/[\x{00A0}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $expression) ?? $expression;

        return trim(preg_replace('/\s+/u', ' ', $expression) ?? $expression);
    }

    /**
     * @return list<string>
     */
    public static function variables(string $expression): array
    {
        preg_match_all('/\{([^{}]+)\}/', self::normalize($expression), $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<array{name?: string}|string>  $fields
     * @return list<string>
     */
    public static function normalizeFieldNames(array $fields): array
    {
        return collect($fields)
            ->map(function ($field) {
                if (is_string($field)) {
                    return self::normalize($field);
                }

                return self::normalize((string) ($field['name'] ?? ''));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Human-readable formula using value names.
     */
    public static function display(string $expression): string
    {
        return trim($expression);
    }

    public static function tryEvaluate(?string $expression, array $values = []): ?float
    {
        if ($expression === null || trim($expression) === '') {
            return null;
        }

        try {
            return self::evaluate($expression, $values);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, float|int|string>  $values
     */
    private static function substitute(string $expression, array $values): string
    {
        return preg_replace_callback(
            '/\{([^{}]+)\}/',
            function (array $match) use ($values) {
                $name = trim($match[1]);

                if (! array_key_exists($name, $values) || ! is_numeric($values[$name])) {
                    throw new InvalidArgumentException("Missing value for \"{$name}\".");
                }

                return (string) (0 + (float) $values[$name]);
            },
            $expression
        ) ?? $expression;
    }

    private static function evaluateNumeric(string $expression): float
    {
        if (! preg_match('/^[0-9+\-*\/%().\s]+$/', $expression)) {
            throw new InvalidArgumentException('Formula contains invalid characters after substituting values.');
        }

        $normalized = preg_replace_callback(
            '/(\d+(?:\.\d+)?)\s*%\s*(\d+(?:\.\d+)?)/',
            fn (array $m) => '((('.$m[1].'/100)*'.$m[2].'))',
            $expression
        );

        $normalized = preg_replace('/(\d+(?:\.\d+)?)\s*%/', '($1/100)', $normalized);

        if ($normalized === null || preg_match('/[^0-9+\-*\/().\s]/', $normalized)) {
            throw new InvalidArgumentException('Unable to normalize formula.');
        }

        if (! self::balanced($normalized)) {
            throw new InvalidArgumentException('Formula parentheses are unbalanced.');
        }

        try {
            $result = null;
            // phpcs:ignore
            eval('$result = ('.$normalized.');');
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Formula could not be evaluated.');
        }

        if (! is_numeric($result) || ! is_finite((float) $result)) {
            throw new InvalidArgumentException('Formula result is not a valid number.');
        }

        return round((float) $result, 4);
    }

    private static function balanced(string $expression): bool
    {
        $depth = 0;

        foreach (str_split($expression) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth < 0) {
                    return false;
                }
            }
        }

        return $depth === 0;
    }
}
