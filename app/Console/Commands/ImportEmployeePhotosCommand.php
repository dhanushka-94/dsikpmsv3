<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportEmployeePhotosCommand extends Command
{
    protected $signature = 'users:import-photos
                            {path? : Absolute path to the employee photos folder}
                            {--dry-run : Report matches without copying or updating}';

    protected $description = 'Map employee photo filenames (EPF) to users and copy into storage/app/public/profiles';

    public function handle(): int
    {
        $source = $this->argument('path')
            ?: 'C:\\Users\\Dhanushka\\Downloads\\DSI Employee Photos\\DSI Employee Photos';

        if (! is_dir($source)) {
            $this->error("Folder not found: {$source}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $files = collect(File::files($source))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), $allowed, true))
            ->values();

        $matched = 0;
        $updated = 0;
        $failures = [];
        $seenEpfs = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $epf = $this->extractEpf($file->getFilenameWithoutExtension());

            if ($epf === null) {
                $failures[] = ['file' => $filename, 'reason' => 'Could not parse EPF from filename'];
                continue;
            }

            if ($file->getSize() <= 0) {
                $failures[] = ['file' => $filename, 'epf' => $epf, 'reason' => 'Empty file (0 bytes)'];
                continue;
            }

            if (isset($seenEpfs[$epf])) {
                $failures[] = [
                    'file' => $filename,
                    'epf' => $epf,
                    'reason' => 'Duplicate photo for same EPF (kept first: '.$seenEpfs[$epf].')',
                ];
                continue;
            }

            $user = User::query()->where('epf_number', $epf)->first();

            if (! $user) {
                $failures[] = ['file' => $filename, 'epf' => $epf, 'reason' => 'No user with this EPF in database'];
                continue;
            }

            $seenEpfs[$epf] = $filename;
            $matched++;

            if ($dryRun) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $relativePath = 'profiles/'.$epf.'_'.Str::lower(Str::random(12)).'.'.$extension;

            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            Storage::disk('public')->put($relativePath, File::get($file->getPathname()));

            $user->update(['profile_picture' => $relativePath]);
            $updated++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Photos found: {$files->count()}");
        $this->info("Matched to users: {$matched}");
        if (! $dryRun) {
            $this->info("Updated profile pictures: {$updated}");
        }
        $this->info('Failures: '.count($failures));

        if ($failures !== []) {
            $this->newLine();
            $this->warn('Failing items:');
            $this->table(['EPF', 'File', 'Reason'], collect($failures)->map(fn ($row) => [
                $row['epf'] ?? '—',
                $row['file'],
                $row['reason'],
            ])->all());
        }

        return self::SUCCESS;
    }

    private function extractEpf(string $basename): ?string
    {
        $basename = trim($basename);

        // e.g. "9858 1", "12942-", "13775--", "5538-"
        if (preg_match('/^(\d+)/', $basename, $matches) !== 1) {
            return null;
        }

        return (string) ((int) $matches[1]);
    }
}
