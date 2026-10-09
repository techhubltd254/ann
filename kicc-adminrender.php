<?php
/**
 * Render the public experience pages as a signed-in administrator, through the
 * real HTTP kernel, and report what each page actually emits. This is how the
 * admin affordances are proven to reach the markup.
 */
require __DIR__ . '/vendor/autoload.php';
$app    = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$routes = ['/', '/counties', '/marketplace', '/exhibitions', '/venues', '/screens',
           '/streams', '/travel', '/institutions', '/national-government',
           '/national-sector', '/counties/mombasa', '/counties/kwale'];

$users = ['anonymous' => null, 'admin' => 1];

foreach ($users as $label => $uid) {
    echo "\n########## AS {$label}" . ($uid ? " (user #{$uid})" : '') . " ##########\n";
    printf("%-22s %-6s %-10s %-9s %-7s %-8s\n", 'PAGE', 'HTTP', 'EXP-LAYOUT', 'BRIGHT-CSS', 'ADMIN', 'TILES');
    foreach ($routes as $uri) {
        $session = $app['session']->driver();
        $session->start();
        if ($uid) { Auth::loginUsingId($uid); } else { Auth::logout(); }

        $req = Request::create($uri, 'GET');
        $req->setLaravelSession($session);
        $res = $kernel->handle($req);
        $html = $res->getContent();

        printf("%-22s %-6s %-10s %-9s %-7s %-8s\n",
            $uri,
            $res->getStatusCode(),
            substr_count($html, 'experience-bright.css') ? 'yes' : 'no',
            substr_count($html, 'experience-bright.css') ? 'yes' : 'no',
            substr_count($html, 'ex-admin'),
            substr_count($html, 'class="ex-card"')
        );
    }
}
