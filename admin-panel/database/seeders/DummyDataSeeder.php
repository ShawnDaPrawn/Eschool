<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Mediums;
use App\Models\Parents;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
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
        $medium = [
            ['id' => 1, 'name' => 'Afrikaans'],
            ['id' => 2, 'name' => 'English'],
            ['id' => 3, 'name' => 'Sepedi'],
        ];
        Mediums::upsert($medium, ['id'], ['name']);

        $sections = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
        ];
        Section::upsert($sections, ['id'], ['name']);

        $classes = [
            ['id' => 1, 'name' => '9', 'medium_id' => 2],
            ['id' => 2, 'name' => '10', 'medium_id' => 2],
        ];
        ClassSchool::upsert($classes, ['id'], ['name', 'medium_id']);

        $class_sections = [
            ['id' => 1, 'class_id' => 1, 'section_id' => 1],
            ['id' => 2, 'class_id' => 1, 'section_id' => 2],
            ['id' => 3, 'class_id' => 2, 'section_id' => 1],
            ['id' => 4, 'class_id' => 2, 'section_id' => 2],
            ['id' => 5, 'class_id' => 2, 'section_id' => 3],
        ];
        ClassSection::upsert($class_sections, ['id'], ['class_id', 'section_id']);

        $subjects = [
            ['id' => 1, 'name' => 'Maths', 'code' => 'MA', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Practical'],
            ['id' => 2, 'name' => 'Science', 'code' => 'SC', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Practical'],
            ['id' => 3, 'name' => 'English', 'code' => 'EN', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Theory'],
            ['id' => 4, 'name' => 'Afrikaans', 'code' => 'AF', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Theory'],
            ['id' => 5, 'name' => 'Life Orientation', 'code' => 'LO', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Theory'],
            ['id' => 6, 'name' => 'Geography', 'code' => 'GE', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Theory'],
            ['id' => 7, 'name' => 'Computer', 'code' => 'CMP', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Practical'],
            ['id' => 8, 'name' => 'Physical Education', 'code' => 'PE', 'bg_color' => '#5031f7', 'image' => 'subject.png', 'medium_id' => 2, 'type' => 'Practical'],

        ];
        Subject::upsert($subjects, ['id'], ['name', 'code', 'bg_color', 'image', 'medium_id', 'type']);

        $session_years = [
            ['id' => 1, 'name' => '2022', 'default' => 1, 'start_date' => Carbon::create('2022', '01', '15'), 'end_date' => Carbon::create('2022', '12', '09')],
            ['id' => 2, 'name' => '2023', 'default' => 0, 'start_date' => Carbon::create('2023', '01', '15'), 'end_date' => Carbon::create('2023', '12', '09')],
            ['id' => 3, 'name' => '2024', 'default' => 0, 'start_date' => Carbon::create('2024', '01', '15'), 'end_date' => Carbon::create('2024', '12', '09')],
            ['id' => 4, 'name' => '2025', 'default' => 0, 'start_date' => Carbon::create('2025', '01', '15'), 'end_date' => Carbon::create('2025', '12', '09')],
        ];
        SessionYear::upsert($session_years, ['id'], ['name', 'default', 'start_date', 'end_date']);

        $session_year_settings = [
            ['id' => 9, 'type' => 'session_year', 'message' => 1],
        ];
        Settings::upsert($session_year_settings, ['id'], ['type', 'message']);

        $student_categories = [
            ['id' => 1, 'name' => 'General', 'status' => 1],
            ['id' => 2, 'name' => 'Boarding', 'status' => 1],
            ['id' => 3, 'name' => 'Day scholar', 'status' => 1],
            ['id' => 4, 'name' => 'Bursary', 'status' => 1],
        ];
        Category::upsert($student_categories, ['id'], ['name', 'status']);

        // Users
        $user = [
            [
                'id' => 2,
                'image' => 'parents/user.png',
                'password' => Hash::make('01011999'),
                'first_name' => 'Michael',
                'last_name' => 'Stevens',
                'email' => 'father@gmail.com',
                'mobile' => 1234567890,
                'gender' => 'Male',
                'current_address' => 'Pretoria',
                'permanent_address' => 'Pretoria',
            ],
            [
                'id' => 3,
                'image' => 'parents/user.png',
                'password' => Hash::make('01011999'),
                'first_name' => 'Sarah',
                'last_name' => 'Stevens',
                'email' => 'mother@gmail.com',
                'mobile' => 1234567890,
                'gender' => 'Female',
                'current_address' => 'Pretoria',
                'permanent_address' => 'Pretoria',
            ],
            [
                'id' => 4,
                'image' => 'students/user.png',
                'password' => Hash::make('01011999'),
                'first_name' => 'Liam',
                'last_name' => 'Stevens',
                'email' => 'student@gmail.com',
                'mobile' => 1234567890,
                'gender' => 'Male',
                'current_address' => 'Pretoria',
                'permanent_address' => 'Pretoria',
            ],
        ];

        User::upsert($user, ['id'], ['image', 'password', 'first_name', 'last_name', 'email', 'mobile', 'current_address', 'permanent_address']);

        // Parents
        $parent = [
            [
                'id' => 1,
                'user_id' => 2,
                'first_name' => 'Michael',
                'last_name' => 'Stevens',
                'image' => 'parents/user.png',
                'occupation' => 'Engineer',
                'email' => 'father@gmail.com',
                'mobile' => 1234567890,
                'gender' => 'Male',
            ],
            [
                'id' => 2,
                'user_id' => 3,
                'first_name' => 'Sarah',
                'last_name' => 'Stevens',
                'image' => 'parents/user.png',
                'occupation' => 'Teacher',
                'email' => 'mother@gmail.com',
                'mobile' => 1234567890,
                'gender' => 'Female',

            ],
            [
                'id' => 2,
                'user_id' => 3,
                'first_name' => 'Andrew',
                'last_name' => 'Stevens',
                'image' => 'parents/user.png',
                'occupation' => 'Accountant',
                'email' => 'guardian@gmail.com',
                'mobile' => 1234567890,
                'gender' => 'Male',

            ],
        ];
        Parents::upsert($parent, ['id'], ['user_id', 'first_name', 'last_name', 'image', 'occupation', 'email', 'mobile', 'dob', 'gender']);

        // Student
        $student = [
            'id' => 1,
            'user_id' => 4,
            'class_section_id' => 3,
            'category_id' => 1,
            'admission_no' => 12345667,
            'roll_number' => 1,
            'caste' => '',
            'religion' => 'Christian',
            'blood_group' => 'B+',
            'height' => '5.5',
            'weight' => '59',
            'father_id' => 1,
            'mother_id' => 2,
            'guardian_id' => 3,
            'admission_date' => Carbon::create('2022', '04', '01'),
        ];
        Students::upsert($student, ['id'], ['user_id', 'class_section_id', 'category_id', 'admission_no', 'roll_number', 'caste', 'religion', 'admission_date', 'blood_group', 'height', 'weight', 'father_id', 'mother_id', 'guardian_id']);
    }
}
