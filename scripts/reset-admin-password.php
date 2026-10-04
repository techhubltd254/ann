<?php

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'admin@kicc.go.ke';
$plain = 'KICC@Admin2026';

try {
    $user = App\Models\User::where('email', $email)->first();
    if (!$user) {
        // Try without any email filter — just get any user
        $any = App\Models\User::first();
        echo "  ⚠ {$email} not found. First user email: " . ($any ? $any->email ?? 'null' : 'none') . "\n";
        // Maybe the email column is differently named — try all users
        $count = App\Models\User::count();
        echo "  Total users in DB: {$count}\n";
        if ($count > 0) {
            App\Models\User::chunk(100, function($users) {
                foreach ($users as $u) {
                    $e = $u->email ?? '(null)';
                    if (strpos($e, 'admin') !== false || strpos($e, 'kicc') !== false) {
                        echo "  Found: {$e}\n";
                    }
                }
            });
        }
        exit(1);
    }

    $user->password = bcrypt($plain);
    $user->passwordHash = bcrypt($plain);
    $user->save();
    echo "  ✓ {$email} password reset to: {$plain}\n";

} catch (\Throwable $e) {
    echo "  ⚠ Password reset error: " . $e->getMessage() . "\n";
    echo "  at: " . $e->getTraceAsString() . "\n";
}