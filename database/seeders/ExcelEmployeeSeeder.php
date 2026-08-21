<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Plant;
use App\Models\User;
use App\Services\TemporaryPasswordService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class ExcelEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/dsi-employee-list.xlsx');

        if (! is_file($path)) {
            throw new RuntimeException('Missing Excel file at database/data/dsi-employee-list.xlsx');
        }

        $company = Company::query()->where('code', 'DSI')->firstOrFail();
        $plants = Plant::query()->where('company_id', $company->id)->get()->keyBy(fn (Plant $p) => strtoupper((string) $p->code));
        $departments = Department::query()->get()->keyBy(fn (Department $d) => (string) $d->code);
        $designations = Designation::query()->get()->keyBy(fn (Designation $d) => mb_strtolower(trim($d->name)));

        $rows = $this->readEmployeeRows($path);
        $epfCounts = [];
        foreach ($rows as $row) {
            $epfCounts[$row['epf']] = ($epfCounts[$row['epf']] ?? 0) + 1;
        }

        $duplicateEpfs = collect($epfCounts)
            ->filter(fn (int $count) => $count > 1)
            ->keys()
            ->map(fn ($epf) => (string) $epf)
            ->all();
        $duplicateEpfLookup = array_fill_keys($duplicateEpfs, true);
        $passwordService = app(TemporaryPasswordService::class);

        $created = 0;
        $skippedDuplicates = 0;
        $skippedMissing = 0;
        $skippedExisting = 0;
        $epfToUserId = [];
        $pendingParents = [];

        foreach ($rows as $row) {
            if (isset($duplicateEpfLookup[$row['epf']])) {
                $skippedDuplicates++;
                continue;
            }

            $plant = $plants->get(strtoupper($row['plant_code']));
            $department = $departments->get($row['department_code']);
            $designation = $designations->get(mb_strtolower($row['designation']));

            if (! $plant || ! $department || ! $designation) {
                $skippedMissing++;
                $this->command?->warn("Skipped EPF {$row['epf']}: missing plant/department/designation mapping.");
                continue;
            }

            if (User::query()->where('epf_number', $row['epf'])->exists()) {
                $user = User::query()->where('epf_number', $row['epf'])->first();
                $epfToUserId[$row['epf']] = $user->id;
                if ($row['supervisor_epf'] !== '') {
                    $pendingParents[$user->id] = $row['supervisor_epf'];
                }
                $skippedExisting++;
                continue;
            }

            $email = $row['epf'].'@dsifootwear.com';
            if (User::query()->where('email', $email)->exists()) {
                $skippedExisting++;
                $this->command?->warn("Skipped EPF {$row['epf']}: email {$email} already exists.");
                continue;
            }

            $user = User::query()->create([
                'title' => null,
                'calling_name' => $row['calling_name'],
                'middle_initials' => $row['middle_initials'] !== '' ? $row['middle_initials'] : null,
                'last_name' => $row['last_name'],
                'name' => User::composeFullName(
                    $row['calling_name'],
                    $row['middle_initials'] !== '' ? $row['middle_initials'] : null,
                    $row['last_name'],
                ),
                'email' => $email,
                'epf_number' => $row['epf'],
                'joined_date' => $row['joined_date'],
                'company_id' => $company->id,
                'plant_id' => $plant->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'role' => UserRole::User,
                'parent_user_id' => null,
                'is_active' => true,
                'must_change_password' => true,
                'password' => Hash::make($passwordService->generate()),
                'email_verified_at' => now(),
            ]);

            $epfToUserId[$row['epf']] = $user->id;
            if ($row['supervisor_epf'] !== '') {
                $pendingParents[$user->id] = $row['supervisor_epf'];
            }
            $created++;
        }

        $linkedParents = 0;
        foreach ($pendingParents as $userId => $supervisorEpf) {
            $parentId = $epfToUserId[$supervisorEpf] ?? User::query()->where('epf_number', $supervisorEpf)->value('id');
            if (! $parentId || (int) $parentId === (int) $userId) {
                continue;
            }

            User::query()->whereKey($userId)->update(['parent_user_id' => $parentId]);
            $linkedParents++;
        }

        $this->command?->info(sprintf(
            'Excel employees imported: created %d, skipped duplicates %d, skipped existing %d, skipped missing refs %d, parents linked %d. Duplicate EPFs skipped: %s',
            $created,
            $skippedDuplicates,
            $skippedExisting,
            $skippedMissing,
            $linkedParents,
            $duplicateEpfs === [] ? 'none' : implode(', ', $duplicateEpfs),
        ));
    }

    /**
     * @return list<array{
     *   epf: string,
     *   calling_name: string,
     *   middle_initials: string,
     *   last_name: string,
     *   department_code: string,
     *   plant_code: string,
     *   designation: string,
     *   joined_date: ?string,
     *   supervisor_epf: string
     * }>
     */
    private function readEmployeeRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new RuntimeException('Excel file is empty.');
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) ($value ?? '')), array_shift($rows));

        $indexes = [
            'epf' => $this->findColumn($header, ['epf no', 'epf']),
            'calling_name' => $this->findColumn($header, ['calling name']),
            'middle_initials' => $this->findColumn($header, ['middle initials']),
            'last_name' => $this->findColumn($header, ['last name', 'name']),
            'department_code' => $this->findColumn($header, ['department code']),
            'plant_code' => $this->findColumn($header, ['plant code']),
            'designation' => $this->findColumn($header, ['designation']),
            'joined_date' => $this->findColumn($header, ['joined date']),
            'supervisor_epf' => $this->findColumn($header, [
                'functional supervisor - employee number',
                'functional supervisor employee number',
            ]),
        ];

        foreach (['epf', 'calling_name', 'last_name', 'department_code', 'plant_code', 'designation'] as $required) {
            if ($indexes[$required] === null) {
                throw new RuntimeException("Excel missing required column for {$required}.");
            }
        }

        // Prefer true "Last Name" column over generic "Name" if both exist.
        $lastNameExact = array_search('last name', $header, true);
        if ($lastNameExact !== false) {
            $indexes['last_name'] = (int) $lastNameExact;
        }

        $employees = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }

            $epf = $this->normalizeEpf($row[$indexes['epf']] ?? null);
            if ($epf === '') {
                continue;
            }

            $calling = $this->clean((string) ($row[$indexes['calling_name']] ?? ''));
            $lastName = $this->clean((string) ($row[$indexes['last_name']] ?? ''));
            if ($calling === '' || $lastName === '') {
                continue;
            }

            $employees[] = [
                'epf' => $epf,
                'calling_name' => $calling,
                'middle_initials' => $this->clean((string) ($row[$indexes['middle_initials']] ?? '')),
                'last_name' => $lastName,
                'department_code' => $this->clean((string) ($row[$indexes['department_code']] ?? '')),
                'plant_code' => $this->clean((string) ($row[$indexes['plant_code']] ?? '')),
                'designation' => $this->clean((string) ($row[$indexes['designation']] ?? '')),
                'joined_date' => $this->parseDate($row[$indexes['joined_date']] ?? null),
                'supervisor_epf' => $indexes['supervisor_epf'] === null
                    ? ''
                    : $this->normalizeEpf($row[$indexes['supervisor_epf']] ?? null),
            ];
        }

        return $employees;
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
}
