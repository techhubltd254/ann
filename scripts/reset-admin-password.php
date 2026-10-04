<?php

/**
 * Reset the KICC Mother Admin password to a known value.
 * Called from deploy.sh after migrations.
 */

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'admin@kicc.go.ke';
$plain = 'KICC@Admin2026';

try {
    $user = App\Models\User::where('email', $email)->first();
    if ($user) {
        $user->password = bcrypt($plain);
        $user->passwordHash = bcrypt($plain);
        $user->save();
        echo "  ✓ {$email} password reset to: {$plain}\n";
    } else {
        echo "  ⚠ No user found with email: {$email}\n";
    }
} catch (\Throwable $e) {
    echo "  ⚠ Password reset error: " . $e->getMessage() . "\n";
}
