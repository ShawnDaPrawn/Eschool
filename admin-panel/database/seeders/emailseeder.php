<?php

namespace Database\Seeders;

use App\Models\EmailTempalate;
use Illuminate\Database\Seeder;

class emailseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Uses firstOrCreate, not updateOrCreate: this seeder re-runs on every system
     * update, and admins can edit these templates from the email template settings
     * screen. Overwriting would silently discard their customisations.
     */
    public function run(): void
    {
        $studentTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<h3>Hello {$name},</h3>

<p>
Congratulations! Your child, {$child_name}, has been successfully registered for the {$class_name} class.
Below are their login credentials:
</p>

<h4>Your Login Credentials:</h4>

<p>
<strong>Email:</strong> {$username}<br>
<strong>Password:</strong> {$password}
</p>

<h4>Your Child's Login Credentials:</h4>

<p>
<strong>GR Number:</strong> {$child_grnumber}<br>
<strong>Password:</strong> {$child_password}<br>
<strong>Class Section:</strong> {$class_name}
</p>

<p>
If you encounter any issues, please contact our support team.
</p>

<p>
Thank you for being a part of {$school_name}.
</p>

Regards,<br>
{$school_name}<br>
{$school_contact}<br>
{$school_email}

</body>
</html>
HTML;

        EmailTempalate::firstOrCreate(
            ['type' => 'student_registration'],
            [
                'subject' => 'Student Admission Confirmation',
                'content' => $studentTemplate,
            ]
        );

        $staffTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<h3>Hello {$name},</h3>

<p>
Congratulations! You have been registered as a <strong>{$role}</strong> in
<strong>{$school_name}</strong>.
</p>

<p>Here are your login credentials:</p>

<p>
<strong>Email:</strong> {$email}<br>
<strong>Password:</strong> {$password}
</p>

<p>
Thank you for joining {$school_name}.
</p>

Regards,<br>
{$school_name}<br>
{$school_contact}<br>
{$school_email}

</body>
</html>
HTML;

        EmailTempalate::firstOrCreate(
            ['type' => 'staff_registration'],
            [
                'subject' => 'Staff Registration Confirmation',
                'content' => $staffTemplate,
            ]
        );

        $reject_application = <<<'HTML'
Dear {$name},
<br>
<br>
Thank you for your interest in {$school_name}.
<br>
We have received {$child_name}'s application for Class {$class_name}. We appreciate the time and effort you invested in the application process.
<br>
After careful consideration, we regret to inform you that we are unable to offer {$child_name} admission at this time. This decision was not made lightly, and it reflects the highly competitive nature of our admissions process this year.
<br>
We encourage {$child_name} to continue pursuing their academic goals, and we wish them every success in their future endeavors.
<br>
Thank you once again for considering {$school_name}.
<br>
<br>
<br>
Sincerely,
<br>
{$school_name}
<br>
{$school_contact}
<br>
{$school_email}
HTML;

        EmailTempalate::firstOrCreate(
            ['type' => 'student_application_regect'],
            [
                'subject' => 'student application rejection',
                'content' => $reject_application,
            ]
        );
    }
}
