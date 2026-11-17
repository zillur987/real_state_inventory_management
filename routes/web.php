<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Vue SPA catch-all (API and Sanctum routes are excluded)
Route::view('/{any?}', 'app')->where('any', '^(?!api|sanctum|up).*$');
