<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserTitle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\KpiCategory;
use App\Models\Plant;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ExcelOrgMasterSeeder::class);

        $company = Company::query()->where('code', 'DSI')->firstOrFail();

        $plantMain = Plant::query()
            ->where('company_id', $company->id)
            ->where('code', 'MP')
            ->first()
            ?? Plant::query()->where('company_id', $company->id)->ordered()->firstOrFail();

        $department = Department::query()->ordered()->firstOrFail();
        $designation = Designation::query()->ordered()->firstOrFail();

        collect([
            ['name' => 'Strategic Initiatives', 'code' => 'SI', 'sort_order' => 1],
            ['name' => 'Operations Improvement', 'code' => 'OI', 'sort_order' => 2],
            ['name' => 'Digital Transformation', 'code' => 'DT', 'sort_order' => 3],
            ['name' => 'Compliance & Quality', 'code' => 'CQ', 'sort_order' => 4],
        ])->each(fn (array $data) => ProjectCategory::updateOrCreate(
            ['code' => $data['code']],
            $data + [
                'description' => $data['name'].' projects',
                'is_active' => true,
            ]
        ));

        collect([
            ['name' => 'Financial', 'code' => 'FIN', 'sort_order' => 1],
            ['name' => 'Operational', 'code' => 'OPS', 'sort_order' => 2],
            ['name' => 'Quality', 'code' => 'QLT', 'sort_order' => 3],
            ['name' => 'People & Culture', 'code' => 'PPL', 'sort_order' => 4],
            ['name' => 'Customer', 'code' => 'CUS', 'sort_order' => 5],
        ])->each(fn (array $data) => KpiCategory::updateOrCreate(
            ['code' => $data['code']],
            $data + [
                'description' => $data['name'].' KPIs',
                'is_active' => true,
            ]
        ));

        User::updateOrCreate(
            ['email' => 'olexto@gmail.com'],
            [
                'title' => UserTitle::Mr,
                'calling_name' => 'Dhanushka',
                'middle_initials' => null,
                'last_name' => 'Bandara',
                'name' => User::composeFullName('Dhanushka', null, 'Bandara'),
                'epf_number' => 'SA0001',
                'joined_date' => '2020-01-01',
                'company_id' => $company->id,
                'plant_id' => $plantMain->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'role' => UserRole::SuperAdmin,
                'parent_user_id' => null,
                'is_active' => true,
                'must_change_password' => false,
                'password' => Hash::make('Dhanushka13228'),
                'email_verified_at' => now(),
            ]
        );

        $this->call(ExcelEmployeeSeeder::class);

        User::query()
            ->where('epf_number', '6459')
            ->update(['role' => UserRole::SuperAdmin]);
    }
}
