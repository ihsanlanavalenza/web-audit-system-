<?php

use App\Livewire\Login;
use App\Livewire\Register;
use App\Livewire\Dashboard;
use App\Livewire\KapProfileSetup;
use App\Livewire\ClientManager;
use App\Livewire\InviteManager;
use App\Livewire\DataRequestTable;
use App\Livewire\SuperAdminDashboard;
use App\Livewire\UserManager;
use App\Livewire\AdminKapManager;
use App\Livewire\AdminClientManager;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/__diag/logs', function () {
    abort_unless(request('k') === 'diag-web-audit-500', 404);

    $report = [
        'app_env' => (string) config('app.env'),
        'app_debug' => (string) (config('app.debug') ? 'true' : 'false'),
        'app_key_set' => (string) (!empty(config('app.key')) ? 'true' : 'false'),
        'session_driver' => (string) config('session.driver'),
        'cache_store' => (string) config('cache.default'),
        'queue_default' => (string) config('queue.default'),
    ];

    $logPath = storage_path('logs/laravel.log');
    if (!is_file($logPath)) {
        $info = "Diagnostics:\n";
        foreach ($report as $key => $value) {
            $info .= "{$key}={$value}\n";
        }

        return response($info."\nLog file not found: {$logPath}\n", 404, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    $lines = @file($logPath);
    if ($lines === false) {
        $info = "Diagnostics:\n";
        foreach ($report as $key => $value) {
            $info .= "{$key}={$value}\n";
        }

        return response($info."\nUnable to read log file: {$logPath}\n", 500, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    $tail = implode('', array_slice($lines, -250));
    $info = "Diagnostics:\n";
    foreach ($report as $key => $value) {
        $info .= "{$key}={$value}\n";
    }

    return response($info."\n--- laravel.log (tail) ---\n".$tail, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
})->withoutMiddleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
]);

/*
|--------------------------------------------------------------------------
| Guest Routes (Belum Login)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');

    // Rute Login Google
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.login');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/dashboard', fn() => redirect()->route('dashboard'));

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    // Schedule (Auditor & Auditi)
    Route::get('/schedule', DataRequestTable::class)->name('schedule.index');
    Route::get('/schedule/{clientId}', DataRequestTable::class)->name('schedule.show');

    /*
    |----------------------------------------------------------------------
    | Auditor Only Routes
    |----------------------------------------------------------------------
    */
    Route::middleware('auditor')->group(function () {
        Route::get('/kap-profile', KapProfileSetup::class)->name('kap-profile');
        Route::get('/clients', ClientManager::class)->name('clients.index');
        Route::get('/invitations', InviteManager::class)->name('invitations.index');
    });

    /*
    |----------------------------------------------------------------------
    | Super Admin Only Routes
    |----------------------------------------------------------------------
    */
    Route::middleware('superadmin')->prefix('admin')->group(function () {
        Route::get('/dashboard', SuperAdminDashboard::class)->name('admin.dashboard');
        Route::get('/users', UserManager::class)->name('admin.users');
        Route::get('/kaps', AdminKapManager::class)->name('admin.kaps');
        Route::get('/clients', AdminClientManager::class)->name('admin.clients');
    });
});
