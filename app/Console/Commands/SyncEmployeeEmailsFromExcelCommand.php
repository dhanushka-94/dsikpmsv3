<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class SyncEmployeeEmailsFromExcelCommand extends Command
{
    protected $signature = 'users:sync-emails-from-excel
                            {path? : Path to DSI Employee List.xlsx}
                            {--dry-run : Show changes without updating}';

    protected $description = 'Sync user emails from Excel by matching EPF number';

    public function handle(): int
    {
        $path = $this->argument('path')
            ?: database_path('data/dsi-employee-list.xlsx');

        if (! is_file($path)) {
            $this->error("Excel file not found: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readEmailsByEpf($path);
        $dryRun = (bool) $this->option('dry-run');

        $updated = 0;
        $unchanged = 0;
        $skippedNoEmail = 0;
        $skippedNoUser = 0;
        $skippedSame = 0;
        $conflicts = [];
        $changes = [];

        foreach ($rows as $epf => $email) {
            if ($email === '') {
                $skippedNoEmail++;
                continue;
            }

            $user = User::query()->where('epf_number', $epf)->first();

            if (! $user) {
                $skippedNoUser++;
                continue;
            }

            if (strcasecmp($user->email, $email) === 0) {
                $skippedSame++;
                $unchanged++;
                continue;
            }

            $owner = User::query()
                ->where('email', $email)
                ->where('id', '!=', $user->id)
                ->first();

            if ($owner) {
                $conflicts[] = [
                    'epf' => $epf,
                    'email' => $email,
                    'reason' => "Email already used by EPF {$owner->epf_number} ({$owner->displayName()})",
                ];
                continue;
            }

            $changes[] = [
                'epf' => $epf,
                'name' => $user->displayName(),
                'from' => $user->email,
                'to' => $email,
            ];

            if (! $dryRun) {
                $user->update(['email' => $email]);
            }

            $updated++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Email sync by EPF complete.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Excel rows with EPF', count($rows)],
                ['Updated', $updated],
                ['Already correct', $skippedSame],
                ['Excel has no email (kept DB)', $skippedNoEmail],
                ['EPF not in database', $skippedNoUser],
                ['Conflicts (skipped)', count($conflicts)],
            ]
        );

        if ($changes !== []) {
            $this->newLine();
            $this->info('Changes:');
            $this->table(['EPF', 'Name', 'Old email', 'New email'], $changes);
        }

        if ($conflicts !== []) {
            $this->newLine();
            $this->warn('Conflicts:');
            $this->table(['EPF', 'Email', 'Reason'], $conflicts);
        }

        if ($skippedNoUser > 0) {
            $this->newLine();
            $this->line('EPFs in Excel but not in DB: run employee import for missing users first.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function readEmailsByEpf(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new RuntimeException('Excel file is empty.');
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) ($value ?? '')), array_shift($rows));

        $epfIndex = $this->findColumn($header, ['epf no', 'epf']);
        $emailIndex = $this->findColumn($header, ['email address', 'email']);

        if ($epfIndex === null || $emailIndex === null) {
            throw new RuntimeException('Excel missing EPF or Email Address column.');
        }

        $emails = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }

            $epf = $this->normalizeEpf($row[$epfIndex] ?? null);
            if ($epf === '') {
                continue;
            }

            $email = strtolower($this->clean((string) ($row[$emailIndex] ?? '')));
            $emails[$epf] = $email;
        }

        return $emails;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $candidates
     */
    private function findColumn(array $header, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $header, true);
            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $value)) ?? ''));
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function normalizeEpf(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_float($value) || is_int($value)) {
            return (string) (int) $value;
        }

        $value = $this->clean((string) $value);
        if (is_numeric($value)) {
            return (string) (int) $value;
        }

        return $value;
    }
}
