<?php

namespace App\Console\Commands;

use App\Services\SoftwareVersion;
use Illuminate\Console\Command;

class BumpSoftwareVersion extends Command
{
    protected $signature = 'software:bump
        {level=patch : major, minor, or patch}
        {--title= : Release title}
        {--notes=* : One or more changelog lines}
        {--message= : Single changelog note (alias for --notes)}';

    protected $description = 'Bump the application software version and append a changelog entry';

    public function handle(SoftwareVersion $softwareVersion): int
    {
        $level = (string) $this->argument('level');
        $title = (string) ($this->option('title') ?: '');
        $notes = collect($this->option('notes') ?: [])
            ->merge($this->option('message') ? [(string) $this->option('message')] : [])
            ->map(fn ($note) => trim((string) $note))
            ->filter()
            ->values()
            ->all();

        try {
            $data = $softwareVersion->bump($level, $title, $notes);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Software version bumped to v'.$data['version']);
        $this->line('Changelog entry added. Commit version.json before pushing to GitHub.');

        return self::SUCCESS;
    }
}
