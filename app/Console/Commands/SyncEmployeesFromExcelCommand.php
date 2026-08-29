<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserTitle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Plant;
use App\Models\User;
use App\Services\TemporaryPasswordService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class SyncEmployeesFromExcelCommand extends Command
{
    protected $signature = 'users:sync-excel {path?}';

    protected $description = 'Update users from the DSI Employee List Excel (title, email, names, org, missing people)';

    /** @var list<array{epf:string,reason:string,detail:string}> */
    private array $report = [];

    public function handle(): int
    {
        $path = $this->argument('path') ?: database_path('data/dsi-employee-list.xlsx');

        if (! is_file($path)) {
            $this->error("Excel not found: {$path}");

            return self::FAILURE;
        }

        $company = Company::query()->where('code', 'DSI')->firstOrFail();
        $rows = $this->readEmployeeRows($path);

        $this->syncPlantsAndDepartments($company, $rows);

        $plants = Plant::query()->where('company_id', $company->id)->get()->keyBy(fn (Plant $p) => strtoupper((string) $p->code));
        $departments = Department::query()->get()->keyBy(fn (Department $d) => (string) $d->code);
        $designations = Designation::query()->get()->keyBy(fn (Designation $d) => mb_strtolower(trim($d->name)));

        $emailOwners = [];
        foreach ($rows as $row) {
            if ($row['email'] === '') {
                continue;
            }
            $emailOwners[$row['email']][] = $row['epf'];
        }

        $sharedEmails = collect($emailOwners)->filter(fn (array $epfs) => count($epfs) > 1);

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $failed = 0;
        $passwordService = app(TemporaryPasswordService::class);
        $pendingParents = [];

        $protectedEmails = [
            'olexto@gmail.com',
            'manojk@dsifootwear.com',
        ];

        foreach ($rows as $row) {
            $plant = $plants->get(strtoupper($row['plant_code']));
            $department = $departments->get($row['department_code']);
            $designation = $designations->get(mb_strtolower($row['designation']));

            if (! $plant || ! $department || ! $designation) {
                $failed++;
                $this->report[] = [
                    'epf' => $row['epf'],
                    'reason' => 'missing_org_ref',
                    'detail' => sprintf(
                        'plant=%s dept=%s designation=%s',
                        $plant ? 'ok' : $row['plant_code'],
                        $department ? 'ok' : $row['department_code'],
                        $designation ? 'ok' : $row['designation'],
                    ),
                ];
                continue;
            }

            $email = $this->resolveEmail($row, $emailOwners, $protectedEmails);

            $user = User::query()->where('epf_number', $row['epf'])->first();

            $payload = [
                'title' => $row['title'],
                'calling_name' => $row['calling_name'],
                'middle_initials' => null,
                'last_name' => null,
                'name' => $row['name'],
                'joined_date' => $row['joined_date'],
                'company_id' => $company->id,
                'plant_id' => $plant->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
            ];

            if ($email !== null) {
                $payload['email'] = $email;
            }

            if (! $user) {
                if (! $email) {
                    $email = $row['epf'].'@dsifootwear.com';
                    $payload['email'] = $email;
                }

                if (User::query()->where('email', $payload['email'])->exists()) {
                    $failed++;
                    $this->report[] = [
                        'epf' => $row['epf'],
                        'reason' => 'email_taken_on_create',
                        'detail' => $payload['email'],
                    ];
                    continue;
                }

                $user = User::query()->create($payload + [
                    'role' => UserRole::User,
                    'parent_user_id' => null,
                    'is_active' => true,
                    'must_change_password' => true,
                    'password' => Hash::make($passwordService->generate()),
                    'email_verified_at' => now(),
                ]);

                $created++;
                $this->report[] = [
                    'epf' => $row['epf'],
                    'reason' => 'created',
                    'detail' => $user->email,
                ];
            } else {
                if (in_array(strtolower((string) $user->email), $protectedEmails, true)) {
                    unset($payload['email']);
                    $this->report[] = [
                        'epf' => $row['epf'],
                        'reason' => 'email_kept_manual',
                        'detail' => $user->email,
                    ];
                } elseif (isset($payload['email']) && $payload['email'] !== $user->email) {
                    $taken = User::query()
                        ->where('email', $payload['email'])
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if ($taken) {
                        unset($payload['email']);
                        $this->report[] = [
                            'epf' => $row['epf'],
                            'reason' => 'email_taken_kept_current',
                            'detail' => $user->email.' (wanted '.$email.')',
                        ];
                    }
                }

                $dirty = false;
                foreach ($payload as $key => $value) {
                    $current = $user->{$key};
                    if ($current instanceof \BackedEnum) {
                        $current = $current->value;
                    }
                    if ($current instanceof \DateTimeInterface) {
                        $current = $current->format('Y-m-d');
                    }
                    if ((string) $current !== (string) ($value ?? '')) {
                        $dirty = true;
                        break;
                    }
                }

                if ($dirty) {
                    $user->update($payload);
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

            if ($row['supervisor_epf'] !== '') {
                $pendingParents[$user->id] = $row['supervisor_epf'];
            }
        }

        $linkedParents = 0;
        foreach ($pendingParents as $userId => $supervisorEpf) {
            $parentId = User::query()->where('epf_number', $supervisorEpf)->value('id');
            if (! $parentId || (int) $parentId === (int) $userId) {
                continue;
            }
            $current = User::query()->whereKey($userId)->value('parent_user_id');
            if ((int) $current === (int) $parentId) {
                continue;
            }
            User::query()->whereKey($userId)->update(['parent_user_id' => $parentId]);
            $linkedParents++;
        }

        $this->newLine();
        $this->info('Excel sync complete');
        $this->line("Rows in Excel: {$this->countFormat(count($rows))}");
        $this->line("Created: {$created}");
        $this->line("Updated: {$updated}");
        $this->line("Unchanged: {$unchanged}");
        $this->line("Failed: {$failed}");
        $this->line("Parents newly linked: {$linkedParents}");

        if ($sharedEmails->isNotEmpty()) {
            $this->newLine();
            $this->warn('Shared emails in Excel (first EPF kept the address, others used fallback):');
            foreach ($sharedEmails as $email => $epfs) {
                $this->line('  '.$email.' → EPF '.implode(', ', $epfs));
            }
        }

        $createdRows = array_filter($this->report, fn ($r) => $r['reason'] === 'created');
        $blankEmail = array_filter($this->report, fn ($r) => $r['reason'] === 'email_blank_kept');
        $sharedFallback = array_filter($this->report, fn ($r) => $r['reason'] === 'email_shared_fallback');
        $keptManual = array_filter($this->report, fn ($r) => $r['reason'] === 'email_kept_manual');
        $failedRows = array_filter($this->report, fn ($r) => in_array($r['reason'], ['missing_org_ref', 'email_taken_on_create'], true));

        if ($createdRows) {
            $this->newLine();
            $this->info('Newly created users:');
            $this->table(['EPF', 'Email'], array_map(fn ($r) => [$r['epf'], $r['detail']], $createdRows));
        }

        if ($keptManual) {
            $this->newLine();
            $this->info('Manual emails kept:');
            $this->table(['EPF', 'Email'], array_map(fn ($r) => [$r['epf'], $r['detail']], $keptManual));
        }

        if ($sharedFallback) {
            $this->newLine();
            $this->warn('Shared-email fallbacks:');
            $this->table(['EPF', 'Assigned email'], array_map(fn ($r) => [$r['epf'], $r['detail']], $sharedFallback));
        }

        $this->line('Blank Excel emails left as-is: '.count($blankEmail));

        if ($failedRows) {
            $this->newLine();
            $this->error('Failures:');
            $this->table(['EPF', 'Reason', 'Detail'], array_map(fn ($r) => [$r['epf'], $r['reason'], $r['detail']], $failedRows));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, list<string>>  $emailOwners
     * @param  list<string>  $protectedEmails
     */
    private function resolveEmail(array $row, array $emailOwners, array $protectedEmails): ?string
    {
        $excelEmail = $row['email'];

        if ($excelEmail === '') {
            $this->report[] = [
                'epf' => $row['epf'],
                'reason' => 'email_blank_kept',
                'detail' => '{epf}@ or existing',
            ];

            return null;
        }

        $owners = $emailOwners[$excelEmail] ?? [$row['epf']];
        $isShared = count($owners) > 1;
        $isFirst = $owners[0] === $row['epf'];

        if ($isShared && ! $isFirst) {
            $fallback = $row['epf'].'@dsifootwear.com';
            $this->report[] = [
                'epf' => $row['epf'],
                'reason' => 'email_shared_fallback',
                'detail' => $fallback.' (shared '.$excelEmail.')',
            ];

            return $fallback;
        }

        if (in_array($excelEmail, $protectedEmails, true)) {
            return $excelEmail;
        }

        return $excelEmail;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncPlantsAndDepartments(Company $company, array $rows): void
    {
        $sort = 1;
        $seenPlants = [];
        $seenDepts = [];

        foreach ($rows as $row) {
            $code = strtoupper($row['plant_code']);
            if ($code !== '' && ! isset($seenPlants[$code])) {
                Plant::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $code],
                    [
                        'name' => $row['plant_name'] !== '' ? $row['plant_name'] : $code,
                        'is_active' => true,
                        'sort_order' => $sort++,
                    ]
                );
                $seenPlants[$code] = true;
            }

            $deptCode = $row['department_code'];
            if ($deptCode !== '' && ! isset($seenDepts[$deptCode])) {
                Department::updateOrCreate(
                    ['code' => $deptCode],
                    [
                        'name' => $row['department_name'] !== '' ? $row['department_name'] : $deptCode,
                        'is_active' => true,
                    ]
                );
                $seenDepts[$deptCode] = true;
            }

            if ($row['designation'] !== '') {
                Designation::updateOrCreate(
                    ['name' => $row['designation']],
                    ['is_active' => true]
                );
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readEmployeeRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Sheet1') ?? $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new RuntimeException('Excel file is empty.');
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) ($value ?? '')), array_shift($rows));

        $indexes = [
            'epf' => $this->findColumn($header, ['epf no', 'epf']),
            'title' => $this->findColumn($header, ['title']),
            'calling_name' => $this->findColumn($header, ['calling name']),
            'middle_initials' => $this->findColumn($header, ['middle initials']),
            'department_code' => $this->findColumn($header, ['department code']),
            'department' => $this->findColumn($header, ['department']),
            'plant_code' => $this->findColumn($header, ['plant code']),
            'plant' => $this->findColumn($header, ['plant / location', 'plant']),
            'designation' => $this->findColumn($header, ['designation']),
            'email' => $this->findColumn($header, ['email address', 'email']),
            'joined_date' => $this->findColumn($header, ['joined date']),
            'supervisor_epf' => $this->findColumn($header, [
                'functional supervisor - employee number',
                'functional supervisor employee number',
            ]),
        ];

        foreach (['epf', 'calling_name', 'middle_initials', 'department_code', 'plant_code', 'designation'] as $required) {
            if ($indexes[$required] === null) {
                throw new RuntimeException("Excel missing required column for {$required}.");
            }
        }

        $employees = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }

            $epf = $this->normalizeEpf($row[$indexes['epf']] ?? null);
            $calling = $this->clean((string) ($row[$indexes['calling_name']] ?? ''));
            $name = $this->clean((string) ($row[$indexes['middle_initials']] ?? ''));

            if ($epf === '' || $calling === '' || $name === '') {
                continue;
            }

            $employees[] = [
                'epf' => $epf,
                'title' => $this->normalizeTitle($indexes['title'] !== null ? (string) ($row[$indexes['title']] ?? '') : ''),
                'calling_name' => $calling,
                'name' => $name,
                'department_code' => $this->clean((string) ($row[$indexes['department_code']] ?? '')),
                'department_name' => $indexes['department'] !== null ? $this->clean((string) ($row[$indexes['department']] ?? '')) : '',
                'plant_code' => $this->clean((string) ($row[$indexes['plant_code']] ?? '')),
                'plant_name' => $indexes['plant'] !== null ? $this->clean((string) ($row[$indexes['plant']] ?? '')) : '',
                'designation' => $this->clean((string) ($row[$indexes['designation']] ?? '')),
                'email' => $indexes['email'] !== null ? strtolower($this->clean((string) ($row[$indexes['email']] ?? ''))) : '',
                'joined_date' => $this->parseDate($row[$indexes['joined_date']] ?? null),
                'supervisor_epf' => $indexes['supervisor_epf'] === null
                    ? ''
                    : $this->normalizeEpf($row[$indexes['supervisor_epf']] ?? null),
            ];
        }

        return $employees;
    }

    private function normalizeTitle(string $value): ?string
    {
        $value = $this->clean($value);
        if ($value === '') {
            return null;
        }

        foreach (UserTitle::cases() as $title) {
            if (strcasecmp($title->value, $value) === 0) {
                return $title->value;
            }
        }

        return null;
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
        return trim(preg_replace('/\s+/u', ' ', str_replace("\t", '', $value)) ?? '');
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

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }

            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function countFormat(int $n): string
    {
        return (string) $n;
    }
}
