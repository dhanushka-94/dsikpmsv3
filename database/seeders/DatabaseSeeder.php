<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserTitle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Plant;
use App\Models\ProjectCategory;
use App\Models\KpiCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['code' => 'DSI'],
            [
                'name' => 'DSI Footwear',
                'description' => 'Default company',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $plantMain = Plant::updateOrCreate(
            ['company_id' => $company->id, 'code' => 'MAIN'],
            [
                'name' => 'Main Plant',
                'description' => 'Primary plant',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Plant::updateOrCreate(
            ['company_id' => $company->id, 'code' => 'NORTH'],
            [
                'name' => 'North Plant',
                'description' => 'North plant',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $departments = collect([
            ['name' => 'Information Technology', 'code' => 'IT', 'sort_order' => 1],
            ['name' => 'Human Resources', 'code' => 'HR', 'sort_order' => 2],
            ['name' => 'Operations', 'code' => 'OPS', 'sort_order' => 3],
            ['name' => 'Finance', 'code' => 'FIN', 'sort_order' => 4],
        ])->map(fn (array $data) => Department::updateOrCreate(
            ['code' => $data['code']],
            $data + [
                'description' => $data['name'].' department',
                'is_active' => true,
            ]
        ));

        $designations = collect([
            ['name' => 'Managing Director', 'code' => 'MD', 'sort_order' => 1],
            ['name' => 'General Manager', 'code' => 'GM', 'sort_order' => 2],
            ['name' => 'Manager', 'code' => 'MGR', 'sort_order' => 3],
            ['name' => 'Executive', 'code' => 'EXE', 'sort_order' => 4],
            ['name' => 'Officer', 'code' => 'OFF', 'sort_order' => 5],
        ])->map(fn (array $data) => Designation::updateOrCreate(
            ['code' => $data['code']],
            $data + [
                'description' => $data['name'].' designation',
                'is_active' => true,
            ]
        ));

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

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@dsi.lk'],
            [
                'title' => UserTitle::Mr,
                'name' => 'System Super Admin',
                'epf_number' => 'EPF0001',
                'company_id' => $company->id,
                'plant_id' => $plantMain->id,
                'department_id' => $departments[0]->id,
                'designation_id' => $designations[0]->id,
                'role' => UserRole::SuperAdmin,
                'parent_user_id' => null,
                'is_active' => true,
                'must_change_password' => false,
                'password' => Hash::make('SuperAdmin@123'),
                'email_verified_at' => now(),
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@dsi.lk'],
            [
                'title' => UserTitle::Miss,
                'name' => 'System Admin',
                'epf_number' => 'EPF0002',
                'company_id' => $company->id,
                'plant_id' => $plantMain->id,
                'department_id' => $departments[1]->id,
                'designation_id' => $designations[2]->id,
                'role' => UserRole::Admin,
                'parent_user_id' => $superAdmin->id,
                'is_active' => true,
                'must_change_password' => false,
                'password' => Hash::make('Admin@123'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@dsi.lk'],
            [
                'title' => UserTitle::Mr,
                'name' => 'Sample User',
                'epf_number' => 'EPF0003',
                'company_id' => $company->id,
                'plant_id' => $plantMain->id,
                'department_id' => $departments[2]->id,
                'designation_id' => $designations[3]->id,
                'role' => UserRole::User,
                'parent_user_id' => $admin->id,
                'is_active' => true,
                'must_change_password' => true,
                'password' => Hash::make('User@123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
