<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! Role::query()->where('name', 'Super Admin')->exists()) {
    foreach ([
        'Database\\Seeders\\InstallationSeeder',
        'Database\\Seeders\\AddSuperAdminSeeder',
        'Database\\Seeders\\emailseeder',
    ] as $seeder) {
        $exit = Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
        echo Artisan::output();
        if ($exit !== 0) {
            fwrite(STDERR, "Seeder {$seeder} failed\n");
            exit(1);
        }
    }
}

$email = getenv('ADMIN_EMAIL') ?: 'info@tokensoft.tech';
$password = (string) getenv('ADMIN_PASSWORD');
$marker = storage_path('app/.admin-provisioned');

$user = User::query()->where('email', $email)->first()
    ?? User::query()->where('email', 'superadmin@gmail.com')->first();

if (! $user) {
    fwrite(STDERR, "Admin user was not created\n");
    exit(1);
}

if (! is_file($marker)) {
    if ($password === '') {
        fwrite(STDERR, "ADMIN_PASSWORD is empty\n");
        exit(1);
    }
    $user->email = $email;
    $user->password = $password;
    $user->save();
    file_put_contents($marker, gmdate('c')."\n");
    echo "Provisioned admin {$email}\n";
} else {
    echo "Admin already provisioned\n";
}

file_put_contents(storage_path('installed'), json_encode([
    'url' => config('app.url'),
    'date' => gmdate('Y/m/d H:i:s'),
]));

echo "Installed\n";
