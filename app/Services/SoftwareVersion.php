<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use RuntimeException;

class SoftwareVersion
{
    public function path(): string
    {
        return base_path('version.json');
    }

    /**
     * @return array{version: string, released_at: string|null, releases: list<array{version: string, date: string, title: string, changes: list<string>}>}
     */
    public function data(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return [
                'version' => '2.0.0',
                'released_at' => null,
                'releases' => [],
            ];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [
                'version' => '2.0.0',
                'released_at' => null,
                'releases' => [],
            ];
        }

        return [
            'version' => (string) ($decoded['version'] ?? '2.0.0'),
            'released_at' => $decoded['released_at'] ?? null,
            'releases' => array_values($decoded['releases'] ?? []),
        ];
    }

    public function current(): string
    {
        return $this->data()['version'];
    }

    /**
     * @return list<array{version: string, date: string, title: string, changes: list<string>}>
     */
    public function releases(): array
    {
        return $this->data()['releases'];
    }

    /**
     * @param  list<string>  $changes
     * @return array{version: string, released_at: string, releases: list<array{version: string, date: string, title: string, changes: list<string>}>}
     */
    public function bump(string $level = 'patch', string $title = '', array $changes = []): array
    {
        $level = strtolower($level);

        if (! in_array($level, ['major', 'minor', 'patch'], true)) {
            throw new RuntimeException('Version level must be major, minor, or patch.');
        }

        $data = $this->data();
        $next = $this->increment($data['version'], $level);
        $today = Carbon::now('Asia/Colombo')->toDateString();

        $changes = collect($changes)
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();

        if ($changes === []) {
            $changes = ['General updates and improvements.'];
        }

        $title = trim($title) !== '' ? trim($title) : 'Release '.$next;

        array_unshift($data['releases'], [
            'version' => $next,
            'date' => $today,
            'title' => $title,
            'changes' => $changes,
        ]);

        $data['version'] = $next;
        $data['released_at'] = $today;

        $this->write($data);

        return $data;
    }

    private function increment(string $version, string $level): string
    {
        $parts = array_map('intval', explode('.', $version));
        while (count($parts) < 3) {
            $parts[] = 0;
        }

        [$major, $minor, $patch] = $parts;

        if ($level === 'major') {
            $major++;
            $minor = 0;
            $patch = 0;
        } elseif ($level === 'minor') {
            $minor++;
            $patch = 0;
        } else {
            $patch++;
        }

        return "{$major}.{$minor}.{$patch}";
    }

    /**
     * @param  array{version: string, released_at: string|null, releases: list<array{version: string, date: string, title: string, changes: list<string>}>}  $data
     */
    private function write(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Unable to encode version.json.');
        }

        if (file_put_contents($this->path(), $json.PHP_EOL) === false) {
            throw new RuntimeException('Unable to write version.json.');
        }
    }
}
