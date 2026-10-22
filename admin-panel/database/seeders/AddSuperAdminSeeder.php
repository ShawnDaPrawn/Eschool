<?php

namespace Database\Seeders;

use App\Models\Platform\Role;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AddSuperAdminSeeder extends Seeder
{
    /** In the school already chosen, or else the default school (a single-school install). */
    public function run(): void
    {
        app(CurrentSchool::class)->runInSchoolOrDefault(fn () => $this->seedSchool());
    }

    /**
     * Run the database seeds.
     */
    private function seedSchool(): void
    {
        // Add Super Admin User
        $super_admin_role = Role::where('name', 'Super Admin')->first();
        $user = User::updateOrCreate(['id' => 1], [
            'first_name' => 'super',
            'last_name' => 'admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('superadmin'),
            'gender' => 'Male',
            'image' => 'logo.svg',
            'mobile' => '',
        ]);
        $user->assignRole([$super_admin_role->id]);

        SessionYear::updateOrCreate(['id' => 1], [
            'name' => '2022-23',
            'default' => 1,
            'start_date' => '2022-06-01',
            'end_date' => '2023-04-30',
        ]);

        // add session year in setting table
        $session_year = new Settings;
        $session_year->type = 'session_year';
        $session_year->message = 1;
        $session_year->save();
    }
}
