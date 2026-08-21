<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Plant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ExcelOrgMasterSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/dsi-employee-list.xlsx');

        if (! is_file($path)) {
            throw new RuntimeException('Missing Excel file at database/data/dsi-employee-list.xlsx');
        }

        $company = Company::updateOrCreate(
            ['code' => 'DSI'],
            [
                'name' => 'DSI Footwear',
                'description' => 'Default company',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        [$plants, $departments, $designations] = $this->extractFromExcel($path);

        $sort = 1;
        foreach ($plants as $code => $name) {
            Plant::updateOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                [
                    'name' => $name,
                    'description' => 'Imported from DSI Employee List',
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]
            );
        }

        $sort = 1;
        foreach ($departments as $code => $name) {
            Department::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => 'Imported from DSI Employee List',
                    'is_active' => true,
                    'sort_order' => $sort++,
                    'parent_id' => null,
                ]
            );
        }

        $sort = 1;
        $usedCodes = [];
        foreach ($designations as $name) {
            $code = $this->uniqueDesignationCode($name, $usedCodes);
            $usedCodes[$code] = true;

            Designation::updateOrCreate(
                ['name' => $name],
                [
                    'code' => $code,
                    'description' => 'Imported from DSI Employee List',
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]
            );
        }

        $this->command?->info(sprintf(
            'Excel org master seeded: %d plants, %d departments, %d designations',
            count($plants),
            count($departments),
            count($designations),
        ));
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, string>, 2: list<string>}
     */
    private function extractFromExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new RuntimeException('Excel file is empty.');
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) ($value ?? '')), array_shift($rows));

        $indexes = [
            'department_code' => $this->findColumn($header, ['department code']),
            'department' => $this->findColumn($header, ['department']),
            'plant_code' => $this->findColumn($header, ['plant code']),
            'plant' => $this->findColumn($header, ['plant / location', 'plant']),
            'designation' => $this->findColumn($header, ['designation']),
        ];

        foreach ($indexes as $key => $index) {
            if ($index === null) {
                throw new RuntimeException("Excel missing required column for {$key}.");
            }
        }

        $plants = [];
        $departments = [];
        $designations = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }

            $plantCode = $this->clean((string) ($row[$indexes['plant_code']] ?? ''));
            $plantName = $this->clean((string) ($row[$indexes['plant']] ?? ''));
            $deptCode = $this->clean((string) ($row[$indexes['department_code']] ?? ''));
            $deptName = $this->clean((string) ($row[$indexes['department']] ?? ''));
            $designation = $this->clean((string) ($row[$indexes['designation']] ?? ''));

            if ($plantCode !== '') {
                $plants[$plantCode] = $plantName !== '' ? $plantName : $plantCode;
            }

            if ($deptCode !== '') {
                $departments[$deptCode] = $deptName !== '' ? $deptName : $deptCode;
            }

            if ($designation !== '') {
                $designations[$designation] = $designation;
            }
        }

        return [$plants, $departments, array_values($designations)];
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
        $value = strtolower(trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $value)) ?? ''));

        return $value;
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * @param  array<string, bool>  $usedCodes
     */
    private function uniqueDesignationCode(string $name, array $usedCodes): string
    {
        $base = strtoupper(Str::ascii($name));
        $base = trim(preg_replace('/[^A-Z0-9]+/', ' ', $base) ?? '');
        $words = array_values(array_filter(explode(' ', $base)));
        $code = '';

        foreach ($words as $word) {
            $code .= substr($word, 0, 1);
            if (strlen($code) >= 8) {
                break;
            }
        }

        if ($code === '') {
            $code = 'DES';
        }

        $candidate = $code;
        $i = 2;
        while (isset($usedCodes[$candidate]) || Designation::where('code', $candidate)->exists()) {
            $candidate = $code.$i;
            $i++;
        }

        return substr($candidate, 0, 50);
    }
}
