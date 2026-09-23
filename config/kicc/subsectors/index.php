<?php

/* Loader: merges every sector subsector file into one registry.
 * Consumed by kicc:register-pipelines and the validation suite. */

$merged = [];
foreach (glob(__DIR__ . '/*.php') as $file) {
    if (basename($file) === 'index.php') continue;
    $merged = array_merge($merged, require $file);
}

return $merged;
