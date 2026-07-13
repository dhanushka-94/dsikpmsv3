<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserTitle;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect([
            ['name' => 'Information Technology', 'code' => 'IT', 'sort_order' => 1],
            ['name' => 'Human Resources', 'code' => 'HR', 'sort_order' => 2],
            ['name' => 'Operations', 'code' => 'OPS', 'sort_order' => 3],
            ['name' => 'Finance', 'code' => 'FIN', 'sort_order' => 4],
        ])->map(fn (array $data) => Department::create($data + [
            'description' => $data['name'].' department',
            'is_active' => true,
        ]));

        $designations = collect([
            ['name' => 'Managing Director', 'code' => 'MD', 'sort_order' => 1],
            ['name' => 'General Manager', 'code' => 'GM', 'sort_order' => 2],
            ['name' => 'Manager', 'code' => 'MGR', 'sort_order' => 3],
            ['name' => 'Executive', 'code' => 'EXE', 'sort_order' => 4],
            ['name' => 'Officer', 'code' => 'OFF', 'sort_order' => 5],
        ])->map(fn (array $data) => Designation::create($data + [
            'description' => $data['name'].' designation',
            'is_active' => true,
        ]));

        collect([
            ['name' => 'Strategic Initiatives', 'code' => 'SI', 'sort_order' => 1],
            ['name' => 'Operations Improvement', 'code' => 'OI', 'sort_order' => 2],
            ['name' => 'Digital Transformation', 'code' => 'DT', 'sort_order' => 3],
            ['name' => 'Compliance & Quality', 'code' => 'CQ', 'sort_order' => 4],
        ])->each(fn (array $data) => ProjectCategory::create($data + [
            'description' => $data['name'].' projects',
            'is_active' => true,
        ]));

        User::create([
            'title' => UserTitle::Mr,
            'name' => 'System Super Admin',
            'email' => 'superadmin@dsi.lk',
            'epf_number' => 'EPF0001',
            'department_id' => $departments[0]->id,
            'designation_id' => $designations[0]->id,
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
            'must_change_password' => false,
            'password' => Hash::make('SuperAdmin@123'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'title' => UserTitle::Miss,
            'name' => 'System Admin',
            'email' => 'admin@dsi.lk',
            'epf_number' => 'EPF0002',
            'department_id' => $departments[1]->id,
            'designation_id' => $designations[2]->id,
            'role' => UserRole::Admin,
            'parent_user_id' => 1,
            'is_active' => true,
            'must_change_password' => false,
            'password' => Hash::make('Admin@123'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'title' => UserTitle::Mr,
            'name' => 'Sample User',
            'email' => 'user@dsi.lk',
            'epf_number' => 'EPF0003',
            'department_id' => $departments[2]->id,
            'designation_id' => $designations[3]->id,
            'role' => UserRole::User,
            'parent_user_id' => 2,
            'is_active' => true,
            'must_change_password' => true,
            'password' => Hash::make('User@123'),
            'email_verified_at' => now(),
        ]);
    }
}
