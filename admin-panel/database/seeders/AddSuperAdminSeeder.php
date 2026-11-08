<?php

namespace Database\Seeders;

use App\Models\Platform\Role;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

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
        $user = User::find(1);
        if ($user === null) {
            $credentials = (array) config('installer.admin_area.user');
            Validator::make($credentials, [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', Password::min(10)->letters()->numbers()],
            ])->validate();
            $user = User::create([
                'id' => 1,
                'first_name' => 'super',
                'last_name' => 'admin',
                'email' => $credentials['email'],
                'password' => Hash::make($credentials['password']),
                'gender' => 'Male',
                'image' => 'logo.svg',
                'mobile' => '',
            ]);
        }
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
