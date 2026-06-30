<?php

use Illuminate\Support\Facades\Route;

// No public welcome page — the site root redirects straight to the Filament
// admin login. The Next.js frontend is the public-facing site.
Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});
